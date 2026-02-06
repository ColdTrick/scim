<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Resource Types
 */
class ResourceTypes {
	
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
				'id' => 'User',
				'name' => 'User',
				'endpoint' => '/Users',
				'schema' => 'urn:ietf:params:scim:schemas:core:2.0:User',
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
