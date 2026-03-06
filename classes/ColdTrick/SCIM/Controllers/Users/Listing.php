<?php

namespace ColdTrick\SCIM\Controllers\Users;

use ColdTrick\SCIM\Controllers\Result;
use Elgg\Http\ResponseBuilder;

/**
 * Give a list of all users
 */
class Listing extends Result {
	
	/**
	 * {@inheritdoc}
	 */
	protected function handleRequest(\Elgg\Request $request): ResponseBuilder {
		$this->assertAuthenticated($request);
		
		return elgg_call(ELGG_SHOW_DISABLED_ENTITIES, function() use ($request) {
			$offset = (int) $request->getParam('startIndex', 1) - 1;
			$limit = (int) $request->getParam('count');
			$limit = max($limit, 1); // >= 1 (no negative values)
			$limit = min($limit, self::LIST_MAX_RESULTS); // max as defined
			
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
			
			return $this->listResponse($resources, $user_count, $offset);
		});
	}
}
