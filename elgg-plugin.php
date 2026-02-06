<?php

use ColdTrick\SCIM\Controllers\ResourceTypes;
use ColdTrick\SCIM\Controllers\Schemas;
use ColdTrick\SCIM\Controllers\ServiceProviderConfig;
use ColdTrick\SCIM\Controllers\Users\Entity;
use ColdTrick\SCIM\Controllers\Users\Listing;

return [
	'plugin' => [
		'name' => 'SCIM Service Provider',
		'version' => '0.1',
		'dependencies' => [
			'web_services' => [],
		],
	],
	'routes' => [
		'default:scim:discovery:service_provider_config' => [
			'path' => '/scim/ServiceProviderConfig',
			'controller' => ServiceProviderConfig::class,
			'methods' => ['GET'],
			'walled' => false,
		],
		'default:scim:discovery:resource_types' => [
			'path' => '/scim/ResourceTypes',
			'controller' => ResourceTypes::class,
			'methods' => ['GET'],
			'walled' => false,
		],
		'default:scim:discovery:schemas' => [
			'path' => '/scim/Schemas',
			'controller' => Schemas::class,
			'methods' => ['GET'],
			'walled' => false,
		],
		'default:scim:users:list' => [
			'path' => '/scim/Users',
			'controller' => Listing::class,
			'methods' => ['GET'],
			'walled' => false,
		],
		'default:scim:users:entity' => [
			'path' => '/scim/Users/{id}',
			'controller' => Entity::class,
			'walled' => false,
		],
	],
];
