<?php

namespace ColdTrick\SCIM\Controllers\Users;

use ColdTrick\SCIM\Controllers\Result;
use Elgg\Exceptions\Configuration\RegistrationException;
use Elgg\Exceptions\Http\BadRequestException;
use Elgg\Exceptions\Http\EntityNotFoundException;
use Elgg\Exceptions\Http\InternalServerErrorException;
use Elgg\Exceptions\Http\NotImplementedException;
use Elgg\Exceptions\HttpException;
use Elgg\Http\ResponseBuilder;

/**
 * Actions on a single user
 */
class Entity extends Result {
	
	/**
	 * {@inheritdoc}
	 */
	protected function handleRequest(\Elgg\Request $request): ResponseBuilder {
		$this->assertAuthenticated($request);
		
		switch ($request->getMethod()) {
			case 'GET':
				return $this->getUser($request);
			case 'POST':
				return $this->createUser($request);
			case 'DELETE':
				return $this->deleteUser($request);
			case 'PUT':
				return $this->updateUser($request, true);
		}
		
		throw new NotImplementedException();
	}
	
	/**
	 * Get the user from the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return \ElggUser
	 * @throws HttpException
	 */
	protected function getUserFromRequest(\Elgg\Request $request): \ElggUser {
		$guid = (int) $request->getParam('guid');
		if ($guid < 1) {
			throw new BadRequestException();
		}
		
		$user = elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function() use ($guid) {
			return get_user($guid);
		});
		if (!$user instanceof \ElggUser) {
			throw new EntityNotFoundException();
		}
		
		return $user;
	}
	
	/**
	 * Get the information of one user
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws HttpException
	 */
	protected function getUser(\Elgg\Request $request): ResponseBuilder {
		$user = $this->getUserFromRequest($request);
		
		$info = $this->getUserInformation($user);
		
		return $this->respondFromResult($info);
	}
	
	/**
	 * Create a new user
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws HttpException
	 */
	protected function createUser(\Elgg\Request $request): ResponseBuilder {
		$user_body = $this->requestBodyToUserAttributes($request);
		
		$username = elgg_extract('userName', $user_body);
		$name = elgg_extract('displayName', $user_body);
		$email = elgg_extract('email', $user_body);
		$password = elgg_extract('password', $user_body) ?? elgg_generate_password();
		
		try {
			$user = elgg_register_user([
				'username' => $username,
				'name' => $name,
				'email' => $email,
				'password' => $password,
				'allow_multiple_emails' => true,
				'validated' => true,
			]);
			
			unset($user_body['userName']);
			unset($user_body['displayName']);
			unset($user_body['email']);
			unset($user_body['password']);
			
			elgg_call(ELGG_IGNORE_ACCESS, function() use ($user_body, $user) {
				foreach ($user_body as $name => $value) {
					$result = elgg_trigger_event_results('user:set_data', 'scim', [
						'entity' => $user,
						'name' => $name,
						'value' => $value,
					], null);
					
					if ($result === false) {
						throw new InternalServerErrorException("Unable to save '{$name}' for user '{$user->getDisplayName()}'");
					}
				}
			});
			
			$user_info = $this->getUserInformation($user);
			$user_url = elgg_generate_url('default:scim:users:entity', [
				'guid' => $user->guid,
			]);
			
			return $this->respondFromResult($user_info, $user_url, ELGG_HTTP_CREATED);
		} catch (RegistrationException $e) {
			throw new BadRequestException($e->getMessage());
		}
	}
	
	/**
	 * Delete a user from the system
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws HttpException
	 */
	protected function deleteUser(\Elgg\Request $request): ResponseBuilder {
		$user = $this->getUserFromRequest($request);
		
		return elgg_call(ELGG_IGNORE_ACCESS, function() use ($user) {
			if (!$user->delete(true, true)) {
				throw new InternalServerErrorException();
			}
			
			return elgg_ok_response('', '', null, ELGG_HTTP_NO_CONTENT);
		});
	}
	
	/**
	 * Update a user
	 *
	 * @param \Elgg\Request $request     Request
	 * @param bool          $full_update Update all information (default: false)
	 *
	 * @return ResponseBuilder
	 * @throws HttpException
	 */
	protected function updateUser(\Elgg\Request $request, bool $full_update = false): ResponseBuilder {
		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function() use ($request, $full_update) {
			$user = $this->getUserFromRequest($request);
			
			$user_body = $this->requestBodyToUserAttributes($request, $full_update);
			foreach ($user_body as $name => $value) {
				switch ($name) {
					case 'userName':
						if ($user->username === $value) {
							continue(2);
						}
						
						if (elgg_get_user_by_username($value)) {
							throw new BadRequestException('duplicate username');
						}
						
						try {
							_elgg_services()->accounts->assertValidUsername($value);
							
							$user->username = $value;
						} catch (RegistrationException $e) {
							throw new BadRequestException($e->getMessage());
						}
						break;
					case 'displayName':
						if ($user->username === $value) {
							continue(2);
						}
						
						$user->name = $value;
						break;
					case 'email':
						if ($user->email === $value) {
							continue(2);
						}
						
						try {
							_elgg_services()->accounts->assertValidEmail($value);
							
							$user->email = $value;
						} catch (RegistrationException $e) {
							throw new BadRequestException($e->getMessage());
						}
						break;
					case 'password':
						try {
							_elgg_services()->accounts->assertCurrentPassword($user, $value);
							
							continue(2);
						} catch (RegistrationException $e) {
							// new password
						}
						
						try {
							_elgg_services()->accounts->assertValidPassword($value);
							
							$user->setPassword($value);
						} catch (RegistrationException $e) {
							throw new BadRequestException($e->getMessage());
						}
						break;
					case 'active':
						$user_is_active = !$user->isBanned();
						
						if ($user_is_active === $value) {
							continue(2);
						}
						
						$result = $value ? $user->unban() : $user->ban();
						if (!$result) {
							throw new InternalServerErrorException('active failed');
						}
						break;
					default:
						$result = elgg_trigger_event_results('user:set_data', 'scim', [
							'entity' => $user,
							'name' => $name,
							'value' => $value,
						], null);
						
						if ($result === false) {
							throw new InternalServerErrorException("Unable to save '{$name}' for user '{$user->getDisplayName()}'");
						}
						break;
				}
			}
			
			if (!$user->save()) {
				throw new InternalServerErrorException();
			}
			
			$user_info = $this->getUserInformation($user);
			
			return $this->respondFromResult($user_info);
		});
	}
}
