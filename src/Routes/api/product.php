<?php

use BlockeraAI\SiteToolkit\Http\Controller\ProductController;

$productsController = new ProductController();

register_rest_route('auth/v1', '/products/allowed-plans', [
	'methods' => 'POST',
	'callback' => [$productsController, 'allowedPlans'],
	'permission_callback' => [$productsController, 'permission'],
]);
