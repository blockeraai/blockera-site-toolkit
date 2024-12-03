<?php

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Setup;
use Psr\Http\Message\ResponseInterface;
use BlockeraAI\SiteToolkit\Http\Controller\ClientController;
use BlockeraAI\SiteToolkit\Http\Middlewares\RefererMiddleware;
use BlockeraAI\SiteToolkit\Http\Middlewares\MiddlewarePipeline;

if (!function_exists('bsaValidateResponse')) {
    /**
     * Validate the response from the REST API.
     *
     * @param \WP_REST_Response $response The response object to validate.
     * 
     * @return void
     */
    function bsaValidateResponse(\WP_REST_Response $response): void
    {
        if (is_wp_error($response)) {
            wp_die($response->get_error_message());
            exit;
        }

        $data = $response->get_data();

        if (200 !== $response->get_status()) {
            if (!empty($data['errors'])) {
                foreach ($data['errors'] as $error) {
                    wp_die($error);
                }

                exit;
            } elseif (!empty($data['message'])) {
                wp_die($data['message']);

                exit;
            }

            dd($data, 'From validate response!');
        }
    }
}

if (!function_exists('bsaDoRegisterClientRequest')) {
    /**
     * Register a client request for Blockera authentication.
     * 
     * @param Setup $app The application setup instance.
     * @param bool $requestHasReferer Whether the request has a referer header.
     *
     * @return array|null The client id and client secret if the request is successful, empty array otherwise.
     */
    function bsaDoRegisterClientRequest(Setup $app, bool $requestHasReferer = true): ?array
    {
        $middlewarePipeline = $app->make(MiddlewarePipeline::class);

        if ($requestHasReferer) {
            $middlewarePipeline->pipe($app->make(RefererMiddleware::class));
        }

        $result = $middlewarePipeline->process($_SERVER);

        if (!$result && $requestHasReferer) {
            return [];
        }

        $params = bsaGetRegisterClientParams($requestHasReferer);

        if (empty($params)) {
            return [];
        }

        $request = new WP_REST_Request('POST', '/auth/v1/client/register');

        $request->set_body_params($params);

        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $registrationClientResponse = rest_do_request($request);

        // Validate the request and handle any errors.
        bsaValidateResponse($registrationClientResponse);

        $response = $registrationClientResponse->get_data();

        $authRequest = new WP_REST_Request('POST', '/auth/v1/authorize');

        $client_id = $response['data']['client_id'];
        $client_secret = $response['data']['client_secret'];

        if (!$requestHasReferer) {
            $params = array_merge(
                $params,
                array_merge(
                    $response['data'],
                    [
                        'redirect_uri' => $_SERVER['HTTP_REFERER'],
                        'response_type' => 'code',
                        'state' => bin2hex(random_bytes(16))
                    ]
                )
            );
        }

        $authRequest->set_query_params(array_merge(empty($_GET) ? $params : $_GET, compact('client_id')));

        $authRequest->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $authResponse = rest_do_request($authRequest);

        // Validate the request and handle any errors.
        bsaValidateResponse($authResponse);

        $data = $authResponse->get_data();

        if (302 !== $data->getStatusCode()) {
            return false;
        }

        if ($requestHasReferer) {
            $redirect_uri = $data->getHeader('Location')[0];
            $internal_redirect = str_starts_with($params['internal_redirect'], home_url()) ? $params['internal_redirect'] : home_url($params['internal_redirect']);

            // Redirect to the client page.
            wp_redirect($redirect_uri . "&client_id=$client_id&client_secret=$client_secret&redirect_to=" . urlencode($internal_redirect), 302);

            // Stop further WordPress execution for this request.
            exit;
        }

        return compact('client_id', 'client_secret');
    }
}

if (!function_exists('bsaGetRegisterClientParams')) {
    /**
     * Get the register client request parameters.
     * 
     * @param bool $requestHasReferer The flag to determine is request has referer address or not.
     *
     * @return array
     */
    function bsaGetRegisterClientParams(bool $requestHasReferer = true): array
    {
        $parsed_referer = parse_url($_SERVER['HTTP_REFERER']);

        if ($requestHasReferer && !empty($parsed_referer['query'])) {

            parse_str(parse_url($_SERVER['HTTP_REFERER'])['query'], $params);

            $internal_redirect = $params['redirect_to'];
            $parsed_internal_redirect = parse_url(home_url($internal_redirect));

            if (empty($parsed_internal_redirect['query'])) {
                return [];
            }

            parse_str($parsed_internal_redirect['query'], $params);

            $parsed_redirect_uri = parse_url($params['redirect_uri']);
            $domain = "{$parsed_redirect_uri['scheme']}://{$parsed_redirect_uri['host']}";

            // Sanitize and get form data.
            $redirect_uri = esc_url_raw($params['redirect_uri']);
        } elseif (!$requestHasReferer) {

            $internal_redirect = null;
            $domain = $_POST['domain'];
            $redirect_uri = $_POST['redirect_uri'];
        } else {

            $redirect_uri = $_GET['redirect_uri'];
            $internal_redirect = Utils::getCurrentPageURL();
            $domain = Utils::extractDomainName($redirect_uri, true);
        }

        $grant_types = 'authorization_code';
        // Get the current logged in user identifier.
        $user_id = wp_get_current_user()->ID;
        // Generate client_id and client_secret.
        $client_id = wp_generate_uuid4();
        $client_secret = wp_generate_password(32, false);

        return compact('user_id', 'client_id', 'client_secret', 'grant_types', 'domain', 'redirect_uri', 'internal_redirect');
    }
}

if (!function_exists('bsaDoUpdateClient')) {
    /**
     * Do the client update request.
     *
     * @param ResponseInterface $responseInterface The response object.
     * @param string $grantType The grant type.
     * @param string $clientId The client id.
     * 
     * @return void
     */
    function bsaDoUpdateClient(ResponseInterface $responseInterface, string $grantType, string $clientId): void
    {
        parse_str(parse_url($responseInterface->getHeader('Location')[0])['query'], $params);

        $request = new \WP_REST_Request('POST', 'auth/v1/client/update');

        $params['client_id'] = $clientId;
        $params['grant_type'] = $grantType;

        $request->set_body_params($params);

        $response = (new ClientController())->update($request);

        bsaValidateResponse($response);
    }
}

if (!function_exists('bsaAddLicenseManagerPage')) {
    
}
