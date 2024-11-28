<?php

use BlockeraAI\SiteToolkit\Http\Controller\SubscriptionController;

$subscriptionController = new SubscriptionController();

register_rest_route('auth/v1', '/subscription/register', [
    'methods' => 'POST',
    'callback' => [$subscriptionController, 'register'],
    'permission_callback' => [$subscriptionController, 'permission'],
]);

register_rest_route('auth/v1', '/subscription', [
    'methods' => 'GET',
    'callback' => [$subscriptionController, 'index'],
    'permission_callback' => [$subscriptionController, 'permission'],
]);
