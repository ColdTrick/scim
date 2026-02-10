<?php

namespace ColdTrick\SCIM\Controllers;

use Elgg\Exceptions\Http\BadRequestException;
use Elgg\Exceptions\Http\UnauthorizedException;
use Elgg\Exceptions\HttpException;
use Elgg\Http\ErrorResponse;
use Elgg\Http\OkResponse;
use Elgg\Http\ResponseBuilder;
use Elgg\Values;
use Psr\Log\LogLevel;

/**
 * Base SCIM controller
 */
abstract class Result {
	
	public const LIST_MAX_RESULTS = 100;
	
	protected ?array $user_attributes;
	
	/**
	 * Constructs a new controller
	 */
	public function __construct() {
		elgg_set_viewtype('json');
	}
	
	/**
	 * Handle a request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return void
	 */
	final public function __invoke(\Elgg\Request $request): void {
		try {
			$response = $this->handleRequest($request);
		} catch (HttpException $e) {
			$response = $this->errorResponse($e->getCode(), $e->getMessage());
		} catch (\Throwable $t) {
			$response = $this->errorResponse(ELGG_HTTP_INTERNAL_SERVER_ERROR, $t->getMessage());
		}
		
		if ($response->isRedirection()) {
			$symfony = _elgg_services()->responseFactory->prepareRedirectResponse(
				$response->getForwardURL(),
				$response->getStatusCode(),
				$response->getHeaders()
			);
			$symfony->setContent($response->getContent());
		} else {
			$symfony = _elgg_services()->responseFactory->prepareResponse(
				$response->getContent(),
				$response->getStatusCode(),
				$response->getHeaders(),
			);
		}
		
		$symfony->headers->set('Content-Type', 'application/scim+json');
		$symfony->headers->set('Content-Disposition', 'attachment; filename="result.json"');
		
		_elgg_services()->responseFactory->send($symfony);
		exit();
	}
	
	/**
	 * Internal handling of the request
	 *
	 * @param \Elgg\Request $request Request
	 *
	 * @return ResponseBuilder
	 */
	abstract protected function handleRequest(\Elgg\Request $request): ResponseBuilder;
	
	/**
	 * Create a response from a result array
	 *
	 * @param array       $result      contents of the result
	 * @param string|null $forward_url forward URL
	 * @param int         $http_code   HTTP response code
	 *
	 * @return OkResponse
	 */
	protected function respondFromResult(array $result, ?string $forward_url = null, int $http_code = ELGG_HTTP_OK): OkResponse {
		return elgg_ok_response(json_encode($result), '', $forward_url, $http_code);
	}
	
	/**
	 * Create a list response from a resources array
	 *
	 * @param array    $resources contents of the resources
	 * @param int|null $total     total number of results available
	 * @param int|null $offset    current offset
	 *
	 * @return OkResponse
	 */
	protected function listResponse(array $resources, ?int $total = null, ?int $offset = null): OkResponse {
		return $this->respondFromResult([
			'schemas' => [
				'urn:ietf:params:scim:api:messages:2.0:ListResponse',
			],
			'totalResults' => $total ?? count($resources),
			'itemsPerPage' => count($resources),
			'startIndex' => $offset ? $offset + 1 : 1,
			'Resources' => $resources,
		]);
	}
	
	/**
	 * Create an error response
	 *
	 * @param int         $http_code HTTP status code
	 * @param string|null $message   (optional) error message
	 * @param string|null $scim_type (optional) additional SCIM error message type
	 *
	 * @return ErrorResponse
	 */
	protected function errorResponse(int $http_code, ?string $message = null, ?string $scim_type = null): ErrorResponse {
		$error = elgg_error_response('', REFERRER, $http_code);
		
		$contents = [
			'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
			'status' => (string) $http_code,
		];
		
		if (!empty($message)) {
			$contents['detail'] = $message;
		}
		
		if (!empty($scim_type)) {
			$contents['scimType'] = $scim_type;
		}
		
		$error->setContent(json_encode($contents));
		
		return $error;
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
		$auth_header = (string) $request->getHttpRequest()->headers->get('Authorization');
		$token = str_replace('Bearer ', '', $auth_header);

		// check that an API key is present
		if (empty($token)) {
			throw new UnauthorizedException(elgg_echo('APIException:MissingAPIKey'));
		}

		// check that it is active
		$api_user = _elgg_services()->apiUsersTable->getApiUser($token);
		if (!$api_user) {
			// key is not active or does not exist
			throw new UnauthorizedException(elgg_echo('APIException:BadAPIKey'));
		}
	}
	
	/**
	 * Get all the user attributes
	 *
	 * @return array[]
	 */
	protected function getUserAttributes(): array {
		if (isset($this->user_attributes)) {
			return $this->user_attributes;
		}
		
		$attributes = [
			[
				'name' => 'userName',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
				'caseExact' => true,
				'uniqueness' => 'server',
			],
			[
				'name' => 'displayName',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
				'caseExact' => true,
				'uniqueness' => 'none',
			],
			[
				'name' => 'active',
				'type' => 'boolean',
				'multiValued' => false,
				'required' => false,
				'mutability' => 'readWrite',
			],
			[
				'name' => 'password',
				'type' => 'string',
				'multiValued' => false,
				'required' => false,
				'mutability' => 'writeOnly',
				'returned' => 'never',
			],
			[
				'name' => 'email',
				'type' => 'string',
				'multiValued' => false,
				'required' => true,
				'mutability' => 'readWrite',
			],
		];
		
		$result = elgg_trigger_event_results('user:attributes', 'scim', ['attributes' => $attributes], $attributes);
		if (!is_array($result)) {
			elgg_log("The results of the 'user:attributes', 'scim' event should be an array", LogLevel::ERROR);
			
			$this->user_attributes = $attributes;
			return $this->user_attributes;
		}
		
		$this->user_attributes = $result;
		return $this->user_attributes;
	}
	
	/**
	 * Transform an ELgg user to an SCIM result
	 *
	 * @param \ElggUser $user the user
	 *
	 * @return array
	 */
	protected function getUserInformation(\ElggUser $user): array {
		$result = [
			'id' => (string) $user->guid,
			'schemas' => [
				'urn:ietf:params:scim:schemas:core:2.0:User',
			],
			'meta' => [
				'resourceType' => 'User',
				'created' => Values::normalizeTime($user->time_created)->format(\DateTimeInterface::ATOM),
				'lastModified' => Values::normalizeTime($user->time_updated)->format(\DateTimeInterface::ATOM),
				'location' => elgg_generate_url('default:scim:users:entity', [
					'guid' => $user->guid,
				]),
			],
		];
		
		$attributes = $this->getUserAttributes();
		foreach ($attributes as $attribute) {
			if (elgg_extract('returned', $attribute) === 'never' || elgg_extract('mutability', $attribute) === 'writeOnly') {
				continue;
			}
			
			$name = elgg_extract('name', $attribute);
			if (empty($name)) {
				continue;
			}
			
			$value = null;
			switch ($name) {
				case 'displayName':
					$value = $user->getDisplayName();
					break;
				case 'userName':
					$value = $user->username;
					break;
				case 'active':
					$value = !$user->isBanned();
					break;
				case 'email':
					$value = $user->email;
					break;
			}
			
			$result[$name] = $value;
		}
		
		return $result;
	}
	
	/**
	 * Transform a request body to an array with user attributes
	 *
	 * @param \Elgg\Request $request                  Request
	 * @param bool          $include_empty_attributes include non submitted attributes (default: false)
	 *
	 * @return array
	 * @throws BadRequestException
	 */
	protected function requestBodyToUserAttributes(\Elgg\Request $request, bool $include_empty_attributes = false): array {
		$raw_body = $request->getHttpRequest()->getContent();
		if (empty($raw_body)) {
			throw new BadRequestException();
		}
		
		$body = json_decode($raw_body, true);
		if (!is_array($body)) {
			throw new BadRequestException();
		}
		
		$result = [];
		
		$attributes = $this->getUserAttributes();
		foreach ($attributes as $attribute) {
			$name = elgg_extract('name', $attribute);
			if (empty($name)) {
				continue;
			}
			
			if (!isset($body[$name]) && !$include_empty_attributes) {
				continue;
			}
			
			$result[$name] = $body[$name] ?? null;
		}
		
		return $result;
	}
}
