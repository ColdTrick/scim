<?php

namespace ColdTrick\SCIM\Controllers\Users;

use ColdTrick\SCIM\Controllers\Result;
use Elgg\Http\ResponseBuilder;

/**
 * Give a list of all users
 */
class Listing extends Result {
	
	/**
	 * Handle the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	public function __invoke(\Elgg\Request $request): ResponseBuilder {
		$this->assertAuthenticated($request);
		
		$offset = (int) $request->getParam('startIndex', 1) - 1;
		$limit = (int) $request->getParam('count', self::LIST_MAX_RESULTS);
		
		$user_count = elgg_count_entities([
			'type' => 'user',
		]);
		
		$users = elgg_get_entities([
			'type' => 'user',
			'limit' => $limit,
			'offset' => $offset,
			'batch' => true,
		]);
		
		$resources = [];
		foreach ($users as $user) {
			$resources[] = $this->getUserInformation($user);
		}
		
		return $this->respondFromResources($resources, $user_count, $offset);
	}
}
