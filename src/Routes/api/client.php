<?php

use BlockeraAI\SiteToolkit\Http\Controller\ClientController;

$clientController = new ClientController();

register_rest_route('auth/v1', '/client/register', [
    'methods' => 'POST',
    'callback' => [$clientController, 'register'],
    'permission_callback' => [$clientController, 'permission'],
]);

register_rest_route('auth/v1', '/client/update', [
    'methods' => 'POST',
    'callback' => [$clientController, 'update'],
    'permission_callback' => [$clientController, 'permission'],
]);
