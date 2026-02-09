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
		}
		
		throw new NotImplementedException();
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
		$guid = (int) $request->getParam('guid');
		if ($guid < 1) {
			throw new BadRequestException();
		}
		
		$user = get_user($guid);
		if (!$user instanceof \ElggUser) {
			throw new EntityNotFoundException();
		}
		
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
		
		$existing_user = elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES | ELGG_SHOW_DELETED_ENTITIES, function() use ($username) {
			return elgg_get_user_by_username($username);
		});
		if ($existing_user instanceof \ElggUser) {
			throw new BadRequestException('duplicate user');
		}
		
		if (empty($email) || !elgg_is_valid_email($email)) {
			throw new BadRequestException('bad email');
		}
		
		try {
			$user = elgg_register_user([
				'username' => $username,
				'name' => $name,
				'email' => $email,
				'password' => $password,
				'allow_multiple_emails' => true,
				'validated' => true,
			]);
			
			$user_info = $this->getUserInformation($user);
			$user_url = elgg_generate_url('default:scim:users:entity', [
				'guid' => $user->guid,
			]);
			
			return $this->respondFromResult($user_info, $user_url, ELGG_HTTP_CREATED);
		} catch (RegistrationException $e) {
			throw new BadRequestException($e->getMessage());
		}
		
		throw new InternalServerErrorException();
	}
}
