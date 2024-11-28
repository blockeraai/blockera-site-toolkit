<?php

use BlockeraAI\SiteToolkit\Http\Controller\AccessTokenController;
use BlockeraAI\SiteToolkit\Http\Controller\AuthController;

$authController = new AuthController($this);
$accessTokenController = new AccessTokenController($this);

// Register authorize route.
register_rest_route('auth/v1', '/authorize', [
    'methods' => 'POST',
    'callback' => [$authController, 'authorize'],
    'permission_callback' => [$authController, 'permission'],
]);

// Register access token route.
register_rest_route('auth/v1', '/token', [
    'methods' => 'POST',
    'callback' => [$accessTokenController, 'accessToken'],
    'permission_callback' => [$accessTokenController, 'permission'],
]);
