<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Resource Types
 */
class ResourceTypes extends Result {
	
	/**
	 * {@inheritdoc}
	 */
	protected function handleRequest(\Elgg\Request $request): ResponseBuilder {
		$resources = [
			[
				'id' => 'User',
				'name' => 'User',
				'endpoint' => '/Users',
				'schema' => 'urn:ietf:params:scim:schemas:core:2.0:User',
			],
		];
		
		return $this->listResponse($resources);
	}
}
