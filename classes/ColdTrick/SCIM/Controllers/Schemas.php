<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Schemas
 */
class Schemas extends Result {
	
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
				'attributes' => $this->getUserAttributes(),
			],
		];
		
		return $this->respondFromResources($resources);
	}
}
