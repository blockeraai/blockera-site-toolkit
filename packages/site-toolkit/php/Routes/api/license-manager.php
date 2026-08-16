<?php

use BlockeraAI\SiteToolkit\Http\Controller\LicenseManagerController;

$licensesController = new LicenseManagerController($this);

register_rest_route(
    'auth/v1',
    '/licenses/create',
    [
		'methods' => 'POST',
		'callback' => [ $licensesController, 'create' ],
		'permission_callback' => [ $licensesController, 'permission' ],
	]
);

register_rest_route(
    'auth/v1',
    '/licenses',
    [
		'methods' => 'GET',
		'callback' => [ $licensesController, 'index' ],
		'permission_callback' => [ $licensesController, 'permission' ],
	]
);

register_rest_route(
    'auth/v1',
    '/license/delete',
    [
		'methods' => 'POST',
		'callback' => [ $licensesController, 'delete' ],
		'permission_callback' => [ $licensesController, 'permission' ],
	]
);

register_rest_route(
    'auth/v1',
    '/license/renew',
    [
		'methods' => 'POST',
		'callback' => [ $licensesController, 'renew' ],
		'permission_callback' => [ $licensesController, 'permission' ],
	]
);

register_rest_route(
    'auth/v1',
    'license/upgrade',
    [
		'methods' => 'POST',
		'callback' => [ $licensesController, 'upgrade' ],
		'permission_callback' => [ $licensesController, 'permission' ],
	]
);
