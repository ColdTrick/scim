<?php

namespace ColdTrick\SCIM\Controllers\Users;

use Elgg\Exceptions\Http\NotImplementedException;
use Elgg\Http\ResponseBuilder;

/**
 * Give a list of all users
 */
class Listing {
	
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
