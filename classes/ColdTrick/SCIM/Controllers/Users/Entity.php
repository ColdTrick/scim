<?php

namespace ColdTrick\SCIM\Controllers\Users;

use Elgg\Exceptions\Http\NotImplementedException;
use Elgg\Http\ResponseBuilder;

/**
 * Actions on a single user
 */
class Entity {
	
	/**
	 * Handle the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	public function __invoke(\Elgg\Request $request): ResponseBuilder {
		throw new NotImplementedException();
	}
}
