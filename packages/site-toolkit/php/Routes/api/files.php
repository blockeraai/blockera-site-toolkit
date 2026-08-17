<?php

use BlockeraAI\SiteToolkit\Http\Controller\FileController;

$fileController = new FileController($this);

register_rest_route(
    'auth/v1',
    '/download',
    [
		'methods' => \WP_REST_Server::CREATABLE,
		'callback' => [ $fileController, 'download' ],
		'permission_callback' => [ $fileController, 'permission' ],
	]
);
