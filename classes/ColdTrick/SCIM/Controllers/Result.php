<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Exceptions\Http\UnauthorizedException;
use Elgg\Http\OkResponse;

/**
 * Base SCIM controller
 */
abstract class Result {
	
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
	 * @param array $resources contents of the resources
	 *
	 * @return OkResponse
	 */
	protected function respondFromResources(array $resources): OkResponse {
		return $this->respondFromResult([
			'schemas' => [
				'urn:ietf:params:scim:api:messages:2.0:ListResponse',
			],
			'totalResults' => count($resources),
			'itemsPerPage' => count($resources),
			'startIndex' => 1,
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
}
