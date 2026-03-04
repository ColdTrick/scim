<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Service Provider Config
 */
class ServiceProviderConfig extends Result {
	
	/**
	 * {@inheritdoc}
	 */
	protected function handleRequest(\Elgg\Request $request): ResponseBuilder {
		$result = [
			'schemas' => [
				'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig',
			],
			'patch' => [
				'supported' => true,
			],
			'bulk' => [
				'supported' => false,
			],
			'filter' => [
				'supported' => false,
				'maxResults' => self::LIST_MAX_RESULTS,
			],
			'changePassword' => [
				'supported' => true,
			],
			'sort' => [
				'supported' => false,
			],
			'etag' => [
				'supported' => false,
			],
			'authenticationSchemes' => [
				[
					'type' => 'oauthbearertoken',
					'name' => 'OAuth Bearer Token',
					'description' => 'Authentication scheme using the OAuth Bearer Token Standard',
				],
			],
		];
		
		return $this->respondFromResult($result);
	}
}
