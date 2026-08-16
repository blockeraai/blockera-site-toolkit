<?php

use BlockeraAI\SiteToolkit\Services\UploadService;
use BlockeraAI\SiteToolkit\Http\Controller\ProductController;

$productsController = new ProductController();
$productsController->setUploadService(new UploadService());

register_rest_route(
    'auth/v1',
    '/products/allowed-plans',
    [
		'methods' => 'POST',
		'callback' => [ $productsController, 'allowedPlans' ],
		'permission_callback' => [ $productsController, 'permission' ],
	]
);

register_rest_route(
    'release/v1',
    '/product',
    [
		'methods' => 'POST',
		'callback' => [ $productsController, 'releaseVersion' ],
		'permission_callback' => [ $productsController, 'permission' ],
	]
);
