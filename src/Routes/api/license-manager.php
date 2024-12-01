<?php

use BlockeraAI\SiteToolkit\Repositories\LicenseRepository;
use BlockeraAI\SiteToolkit\Http\Controller\LicenseManagerController;

$licensesController = new LicenseManagerController(new LicenseRepository());

register_rest_route('auth/v1', '/licenses/create', [
    'methods' => 'POST',
    'callback' => [$licensesController, 'create'],
    'permission_callback' => [$licensesController, 'permission'],
]);

register_rest_route('auth/v1', '/licenses', [
    'methods' => 'GET',
    'callback' => [$licensesController, 'index'],
    'permission_callback' => [$licensesController, 'permission'],
]);
