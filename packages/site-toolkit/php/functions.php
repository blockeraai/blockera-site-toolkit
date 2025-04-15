<?php

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Setup;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use BlockeraAI\SiteToolkit\Http\Controller\ClientController;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;

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
        $parsed_referer = parse_url($_SERVER['HTTP_REFERER'] ?? '');

        if ($requestHasReferer && !empty($parsed_referer['query'])) {

            parse_str(parse_url($_SERVER['HTTP_REFERER'])['query'], $params);

            $internal_redirect = str_starts_with($params['redirect_to'] ?? '', home_url()) ? $params['redirect_to'] : home_url('/' . $params['redirect_to'] ?? '');
            $parsed_internal_redirect = parse_url(home_url($internal_redirect));

            if (empty($parsed_internal_redirect['query'])) {
                return [];
            }

            parse_str($parsed_internal_redirect['query'], $params);

            $parsed_redirect_uri = parse_url($params['redirect_uri'] ?? '');
            $scheme = $parsed_redirect_uri['scheme'] ?? '';
            $host = $parsed_redirect_uri['host'] ?? '';
            $domain = "{$scheme}://{$host}";

            // Sanitize and get form data.
            $redirect_uri = esc_url_raw($params['redirect_uri'] ?? '');
        } elseif (!$requestHasReferer) {

            $domain = $_POST['domain'] ?? '';
            $redirect_uri = $_POST['redirect_uri'] ?? '';
            $internal_redirect = $_POST['redirect_uri'] ?? '';
        } else {

            $redirect_uri = $_GET['redirect_uri'] ?? '';
            $internal_redirect = Utils::getCurrentPageURL();
            $domain = Utils::extractDomainName($redirect_uri, true);
        }

        $grant_types = 'authorization_code';
        // Get the current logged in user identifier.
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $username = $user->user_email;
        // Generate client_id and client_secret.
        $client_id = wp_generate_uuid4();
        $client_secret = wp_generate_password(32, false);

        return compact('user_id', 'username', 'client_id', 'client_secret', 'grant_types', 'domain', 'redirect_uri', 'internal_redirect');
    }
}

// FIXME: remove this function and move to api website while implementing the resource server.
if (!function_exists('bsaValidateAccessToken')) {
    /**
     * Validate the access token.
     *
     * @param string $accessToken The access token.
     * @param AuthorizationServer $server The authorization server.
     * @param WP_REST_Request $request The request object.
     *
     * @return bool true on success, false on otherwise!
     */
    function bsaValidateAccessToken(WP_REST_Request $request): bool
    {
        $clientController = new ClientController();

        try {
            $resourceServer = new ResourceServer(
                new AccessTokenRepository(),
                new CryptKey(Setup::getInstance()->getPath() . 'public.key', null, false)
            );

            // Convert WP request to PSR-7 request.
            $psr7Request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
            $resourceServer->validateAuthenticatedRequest($psr7Request);
            new ResourceServerMiddleware($resourceServer);

            return $clientController->permission($request);
        } catch (Exception $e) {
            return false;
        }
    }
}
if (!function_exists('bsaGetAccessTokenIdentifier')) {
    /**
     * Get the access token identifier.
     *
     * @param string $accessToken The access token.
     *
     * @return string
     */
    function bsaGetAccessTokenIdentifier(string $accessToken): string
    {
        // Decode the JWT without verifying to extract the identifier
        $tokenParts = explode('.', $accessToken);
        $payload = json_decode(base64_decode($tokenParts[1]), true);

        return $payload['jti'] ?? '';
    }
}

// ============================================== New Implementation ==============================================

if (!function_exists('bsaGetEnv')) {
    /**
     * Get the environment variable.
     *
     * @param string $key The key of the environment variable.
     *
     * @return string
     */
    function bsaGetEnv(string $key): string
    {
		// FIXME: remove this statement because we need to sure about .env file to be loaded or not. it seems that it's not loaded in the production environment.
        if ('BSA_API_BASE_URL' === $key && 'dev' !== BSA_PLUGIN_MODE) {
            return 'https://api.blockera.ai';
        }

        return $_ENV[$key] ?? '';
    }
}

if (!function_exists('bsaGetConfig')) {
    /**
     * Get the configuration variable.
     *
     * @param string $key The key of the configuration variable.
     *
     * @return string
     */
    function bsaGetConfig(string $key): string
    {
        $env = bsaGetEnv($key);

        if (!empty($env)) {
            return $env;
        }

        if (defined(strtoupper($key))) {
            try {
                return constant(strtoupper($key));
            } catch (Exception $e) {
                return '';
            }
        }

        return '';
    }
}

if (!function_exists('bsaGetUserAccessToken')) {
    /**
     * Get the user access token.
     *
     * @param \WP_User $user The user object.
	 * @param bool $redirect The flag to determine if the user should be redirected to the redirect uri. Default is true.
     *
     * @return array
     */
    function bsaGetUserAccessToken(\WP_User $user = null, bool $redirect = true): array
    {
        $user = $user ?? wp_get_current_user();
        $metaKey = 'blockera_api_user_info';
        $metadata = get_user_meta($user->ID, $metaKey, true);

        // If the user info is already cached, return it.
        if (!empty($metadata) && 'dev' === BSA_PLUGIN_MODE) {
            return $metadata;
        }

        $response = wp_remote_post(bsaGetEnv('BSA_API_BASE_URL') . '/auth/v1/token', [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            // TODO: remove it after testing.
            'sslverify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
				'user_id' => $user->ID,
                'email' => $user->user_email,
				'username' => $user->user_login,
				'nonce' => md5('blockera-site-toolkit'),
            ]),
        ]);

        $query = http_build_query([
            'access_denied' => 'true',
            'error_description' => 'The user denied the request.',
        ]);
        $redirectURI = empty($_GET['redirect_uri']) ? home_url() : $_GET['redirect_uri'] . '&' . $query;

        if (is_wp_error($response)) {
            if ($redirect) {
				wp_redirect($redirectURI, 302);
                exit;
			}

			return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            if ($redirect) {
				wp_redirect($redirectURI, 302);
                exit;
			}

			return [];
        }

        // Cache the user info in the user meta.
        update_user_meta($user->ID, $metaKey, $body['data']);

        return $body['data'];
    }
}

if (!function_exists('bsaDoStoreClient')) {
    /**
     * Do the client store request.
     *
     * @param array $params The parameters to pass to the request.
     * @param string $authorization The authorization header.
     *
     * @return array
     */
    function bsaDoStoreClient(array $params, string $authorization): array
    {
        $user = wp_get_current_user();
        $metaKey = 'blockera_api_client_info';
        $metadata = get_user_meta($user->ID, $metaKey, true);

        // If the client info is already cached, return it.
        if (!empty($metadata) && 'dev' === BSA_PLUGIN_MODE) {
            return $metadata;
        }

        $response = wp_remote_post(bsaGetEnv('BSA_API_BASE_URL') . '/clients-manager/v1/clients', [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify' => false,
            'body' => $params,
            'headers' => [
                'Authorization' => $authorization,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success']) || empty($body['data'])) {
            return [];
        }

        // Cache the client info in the user meta.
        update_user_meta($user->ID, $metaKey, $body['data']);

        return $body['data'];
    }
}

if (!function_exists('bsaDoTerminateClient')) {
    /**
     * Do the client terminate request.
	 * 
	 * @param string $authorization The authorization header.
     *
     * @return bool true on success, false on otherwise.
     */
    function bsaDoTerminateClient(string $authorization): bool
    {
        $user = wp_get_current_user();
        $metaKey = 'blockera_api_client_info';
        $metadata = get_user_meta($user->ID, $metaKey, true);

        if (empty($metadata)) {
            return false;
        }

        $response = wp_remote_request(bsaGetEnv('BSA_API_BASE_URL') . '/clients-manager/v1/clients/' . $metadata['client_id'], [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify' => false,
            'method' => 'DELETE',
            'headers' => [
                'Authorization' => $authorization,
            ],
			'body' => [
				'client_id' => $metadata['client_id'],
			]
        ]);

        if (is_wp_error($response)) {
			dd($response);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            return false;
        }
        
        return delete_user_meta($user->ID, $metaKey);
    }
}

if (!function_exists('bsaDoAuthorization')) {
    /**
     * Do the authorization request.
     *
     * @param string $clientId The client id.
     * @param string $clientSecret The client secret.
     * @param array $args The extra arguments.
     *
     * @return array
     */
    function bsaDoAuthorization(string $clientId, string $clientSecret, array $args): array
    {
        $params = $args['params'] ?? [];
        $authorization = $args['authorization'] ?? '';

        if (!empty($params['event']) && in_array($params['event'], ['registration-client'], true)) {
            $params = array_merge(
                $params,
                [
                    'client_id' => $clientId,
                    'response_type' => 'code',
                    'client_secret' => $clientSecret,
                    'state' => bin2hex(random_bytes(16)),
                    // 'redirect_uri' => $_SERVER['HTTP_REFERER'] ?? '',
                ]
            );
        }

        $response = wp_remote_post(bsaGetEnv('BSA_API_BASE_URL') . '/auth/v1/authorize', [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify' => false,
            'headers' => [
                'Authorization' => $authorization,
            ],
            'body' => empty($_GET['response_type']) ? $params : array_merge($_GET, $params),
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            return [];
        }

        return $body['data'] ?? [];
    }
}


/**
 * Processing renewal subscription ...
 *
 * @param int $subscription_id The ID of the subscription to renew.
 *
 * @return string the checkout url or empty string.
 */
function bsaRenewalSubscription(int $subscription_id): string
{
    $subscription = ywsbs_get_subscription($subscription_id);

    if (!$subscription || !is_object($subscription)) {
        return '';
    }

    try {
        if (is_null(WC()->cart)) {
            WC()->initialize_session();
            WC()->cart = new WC_Cart();
        }

        if (is_null(WC()->customer)) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }

        YITH_WC_Subscription()->renew_the_subscription($subscription);

        $checkoutUrl = wc_get_checkout_url();
    } catch (Exception $e) {
        throw new Exception('Error creating subscription renewal link: ' . $e->getMessage());
    }

    return $checkoutUrl;
}

/**
 * Upgrading subscription ...
 *
 * @param int $subscriptionId The ID of the subscription to renew.
 *
 * @return string the checkout url.
 */
function bsaUpgradingSubscription(int $subscriptionId): string
{
    if (!function_exists('YITH_WC_Subscription')) {
        throw new Exception('The YITH WooCommerce Subscription plugin is required but not installed.');
    }

    $subscription = ywsbs_get_subscription($subscriptionId);

    if (!$subscription || !is_object($subscription)) {
        throw new Exception('Invalid or missing subscription');
    }

    try {
        // Get the current variation ID and target variation ID.
        $from = $subscription->get_variation_id();
        $to = $subscription->get('variation_id');

        // Get the variation products.
        $fromVariation = wc_get_product($from);
        $toVariation = wc_get_product($to);

        if (!$fromVariation || !$toVariation) {
            throw new Exception('Invalid variation products');
        }

        // Calculate price gap between variations
        $fromPrice = $fromVariation->get_price();
        $toPrice = $toVariation->get_price();
        $gapAmount = $toPrice - $fromPrice;


        add_user_meta(
            $subscription->get_user_id(),
            'ywsbs_upgrade_' . $to,
            array(
                'subscription_id' => $subscription->get_id(),
                'pay_gap'         => $gapAmount,
            ),
            true
        );

        $variation = wc_get_product($to);

        if (!$variation instanceof \WC_Product) {
            throw new Exception("Variation: '$to' not found!");
        }

        if (! apply_filters('woocommerce_add_to_cart_validation', true, $subscription->get('product_id'), $subscription->get('quantity'), $to, $variation->get_variation_attributes())) {
            throw new Exception(esc_html__('This subscription cannot be switched. Contact us for more information.', 'blockera'), 500);
        }

        if (is_null(WC()->cart)) {
            WC()->initialize_session();
            WC()->cart = new WC_Cart();
        }

        if (is_null(WC()->customer)) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }

        WC()->cart->add_to_cart($subscription->get('product_id'), $subscription->get('quantity'), $to, $variation->get_variation_attributes());

        $checkoutUrl = wc_get_checkout_url();

        do_action('ywsbs_subscription_upgrade_process', $subscription->get_variation_id(), $to, $subscription, $gapAmount);
    } catch (Exception $e) {
        throw new Exception('Error creating subscription renewal link: ' . $e->getMessage());
    }

    return $checkoutUrl;
}

if (!function_exists('bsaFilterActiveLicenses')) {
    /**
     * Filter the active licenses.
     *
     * @param array $licenses The licenses to filter.
     *
     * @return array The filtered licenses.
     */
    function bsaFilterActiveLicenses(array $licenses): array
    {
        return array_filter($licenses, function (array $license): bool {
            return 'deleted' !== $license['status'];
        });
    }
}

if (!function_exists('bsaGetDownloadableFiles')) {
    /**
     * Get the downloadable files.
     *
	 * @param int $variationId The ID of the variation.
	 * @param array $args The extra arguments. includes 
     *
     * @return array The downloadable files.
     */
    function bsaGetDownloadableFiles(int $variationId, array $args): array
    {
		$downloads = [];
		$downloadableFiles = get_post_meta($variationId, '_downloadable_files', true);

		if (!empty($downloadableFiles)) {

			foreach ($downloadableFiles as $downloadableFileId => $downloadableFile) {

				$downloads[$downloadableFileId] = [
					'resource' => 'api',
                    'name' => $downloadableFile['name'],
                    'filename' => basename($downloadableFile['file']),
                    'file' => bsaGetEnv('BSA_API_BASE_URL') . '/files/v1/download/' . $downloadableFileId,
                    'enabled' => $downloadableFile['enabled'] ?? true,
                    'id' => $downloadableFileId,
                ];
			}
		}

		if(empty($downloads)) {
			$downloads = array_map(function (array $downloadableFile, string $downloadableFilename):array {
				return [
					'resource' => 'api',
					'name' => $downloadableFilename,
					'filename' => basename($downloadableFile['file']),
					'file' => bsaGetEnv('BSA_API_BASE_URL') . '/files/v1/download/' . $downloadableFile['hash'],
					'enabled' => true,
					'id' => $downloadableFile['hash'],
				];
			}, $args['fallbackDownloadableFiles'], array_keys($args['fallbackDownloadableFiles']));
		}
		
		if ($args['isActivatedFreeDownload']) {
			$freeVersion = [
				'resource' => 'wp', 
				'name' => 'Blockera Free',
				'filename' => 'blockera.latest.zip',
				'enabled' => true,
				'id' => wp_generate_uuid4(),
				'file' => sprintf('https://downloads.wordpress.org/plugin/%s.latest-stable.zip', $args['freeSlug']),
			];

			if(!empty($downloads)) {
				array_unshift($downloads, $freeVersion);
			} else {
				$downloads[] = $freeVersion;
			}
		}

		return $downloads;
    }
}
