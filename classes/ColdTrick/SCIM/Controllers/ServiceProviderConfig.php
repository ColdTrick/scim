<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Http\ResponseBuilder;

/**
 * Handle the discovery of the Service Provider Config
 */
class ServiceProviderConfig {
	
	/**
	 * Handle the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	public function __invoke(\Elgg\Request $request): ResponseBuilder {
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
				'maxResults' => 100,
			],
			'changePassword' => [
				'supported' => false,
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
		
		$response = elgg_ok_response(json_encode($result));
		$headers = $response->getHeaders();
		$headers['Content-Type'] = 'application/scim+json';
		
		$response->setHeaders($headers);
		
		return $response;
	}
}
