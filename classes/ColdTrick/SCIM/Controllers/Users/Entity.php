<?php

namespace ColdTrick\SCIM\Controllers\Users;

use ColdTrick\SCIM\Controllers\Result;
use Elgg\Exceptions\Http\BadRequestException;
use Elgg\Exceptions\Http\EntityNotFoundException;
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
		
		switch ($request->getMethod()) {
			case 'GET':
				return $this->getUser($request);
		}
		
		throw new NotImplementedException();
	}
	
	/**
	 * Get the information of one user
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	protected function getUser(\Elgg\Request $request): ResponseBuilder {
		$guid = (int) $request->getParam('guid');
		if ($guid < 1) {
			throw new BadRequestException();
		}
		
		$user = get_user($guid);
		if (!$user instanceof \ElggUser) {
			throw new EntityNotFoundException();
		}
		
		$info = $this->getUserInformation($user);
		
		return $this->respondFromResult($info);
	}
}
