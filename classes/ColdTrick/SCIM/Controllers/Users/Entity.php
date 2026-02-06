<?php

namespace ColdTrick\SCIM\Controllers\Users;

use ColdTrick\SCIM\Controllers\Result;
use Elgg\Exceptions\Http\NotImplementedException;
use Elgg\Http\ResponseBuilder;

/**
 * Actions on a single user
 */
class Entity extends Result {
	
	/**
	 * Handle the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	public function __invoke(\Elgg\Request $request): ResponseBuilder {
		$this->assertAuthenticated($request);
		
		throw new NotImplementedException();
	}
}
