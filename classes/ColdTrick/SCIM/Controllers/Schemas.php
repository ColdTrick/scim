<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Schemas
 */
class Schemas {
	
	/**
	 * Handle the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	public function __invoke(\Elgg\Request $request): ResponseBuilder {
		$resources = [
			[
				'id' => 'urn:ietf:params:scim:schemas:core:2.0:User',
				'schemas' => [
					'urn:ietf:params:scim:schemas:core:2.0:User',
				],
				'name' => 'User',
				'attributes' => [
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
				],
			],
		];
		
		$result = [
			'schemas' => [
				'urn:ietf:params:scim:api:messages:2.0:ListResponse',
			],
			'totalResults' => count($resources),
			'itemsPerPage' => count($resources),
			'startIndex' => 1,
			'Resources' => $resources,
		];
		
		$response = elgg_ok_response(json_encode($result));
		$headers = $response->getHeaders();
		$headers['Content-Type'] = 'application/scim+json';
		
		$response->setHeaders($headers);
		
		return $response;
	}
}
