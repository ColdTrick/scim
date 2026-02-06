<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Exceptions\Http\UnauthorizedException;
use Elgg\Http\OkResponse;
use Elgg\Values;

/**
 * Base SCIM controller
 */
abstract class Result {
	
	public const LIST_MAX_RESULTS = 100;
	
	/**
	 * Constructs a new controller
	 */
	public function __construct() {
		elgg_set_viewtype('json');
	}
	
	/**
	 * Create a response from a result array
	 *
	 * @param array $result contents of the result
	 *
	 * @return OkResponse
	 */
	protected function respondFromResult(array $result): OkResponse {
		$response = elgg_ok_response(json_encode($result));
		$headers = $response->getHeaders();
		$headers['Content-Type'] = 'application/scim+json';
		
		$response->setHeaders($headers);
		
		return $response;
	}
	
	/**
	 * Create a response from a resources array
	 *
	 * @param array    $resources contents of the resources
	 * @param int|null $total     total number of results available
	 * @param int|null $offset    current offset
	 *
	 * @return OkResponse
	 */
	protected function respondFromResources(array $resources, ?int $total = null, ?int $offset = null): OkResponse {
		return $this->respondFromResult([
			'schemas' => [
				'urn:ietf:params:scim:api:messages:2.0:ListResponse',
			],
			'totalResults' => $total ?? count($resources),
			'itemsPerPage' => count($resources),
			'startIndex' => $offset ? $offset + 1 : 1,
			'Resources' => $resources,
		]);
	}
	
	/**
	 * Check the authentication for a request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return void
	 * @throws UnauthorizedException
	 */
	protected function assertAuthenticated(\Elgg\Request $request): void {
		if ($request->getParam('token') === '31o@L3aTWG@xEoPk') {
			return;
		}
		
		throw new UnauthorizedException();
	}
	
	/**
	 * Get all the user attributes
	 *
	 * @return array[]
	 */
	protected function getUserAttributes(): array {
		return [
			[
				'name' => 'userName',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
				'caseExact' => true,
				'uniqueness' => 'server',
			],
			[
				'name' => 'displayName',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
				'caseExact' => true,
				'uniqueness' => 'none',
			],
			[
				'name' => 'active',
				'type' => 'boolean',
				'multiValued' => false,
				'required' => false,
				'mutability' => 'readWrite',
			],
			[
				'name' => 'password',
				'type' => 'string',
				'multiValued' => false,
				'required' => false,
				'mutability' => 'writeOnly',
				'returned' => 'never',
			],
			[
				'name' => 'email',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
			],
		];
	}
	
	/**
	 * Transform an ELgg user to an SCIM result
	 *
	 * @param \ElggUser $user the user
	 *
	 * @return array
	 */
	protected function getUserInformation(\ElggUser $user): array {
		$result = [
			'id' => (string) $user->guid,
			'schemas' => [
				'urn:ietf:params:scim:schemas:core:2.0:User',
			],
			'meta' => [
				'resourceType' => 'User',
				'created' => Values::normalizeTime($user->time_created)->format(\DateTimeInterface::ATOM),
				'lastModified' => Values::normalizeTime($user->time_updated)->format(\DateTimeInterface::ATOM),
				'location' => elgg_generate_url('default:scim:users:entity', [
					'id' => $user->guid,
				]),
			],
		];
		
		$attributes = $this->getUserAttributes();
		foreach ($attributes as $attribute) {
			if (elgg_extract('returned', $attribute) === 'never' || elgg_extract('mutability', $attribute) === 'writeOnly') {
				continue;
			}
			
			$name = elgg_extract('name', $attribute);
			if (empty($name)) {
				continue;
			}
			
			$value = null;
			switch ($name) {
				case 'displayName':
					$value = $user->getDisplayName();
					break;
				case 'userName':
					$value = $user->username;
					break;
				case 'active':
					$value = !$user->isBanned();
					break;
				case 'email':
					$value = $user->email;
					break;
			}
			
			$result[$name] = $value;
		}
		
		return $result;
	}
}
