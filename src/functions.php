<?php

use Blockera\Utils\Utils;
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
				new CryptKey(BSA_PLUGIN_DIR . 'public.key', null, false)
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
	 * 
	 * @return array
	 */
	function bsaGetUserAccessToken(\WP_User $user = null): array
	{
		$user = $user ?? wp_get_current_user();
		$metaKey = 'blockera_api_user_info';
		$metadata = get_user_meta($user->ID, $metaKey, true);

		// If the user info is already cached, return it.
		if (!empty($metadata) && 'false' === bsaGetConfig('DEBUG')) {
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
				'email' => $user->user_email,
			]),
		]);

		$query = http_build_query([
			'access_denied' => 'true',
			'error_description' => 'The user denied the request.',
		]);
		$redirectURI = empty($_GET['redirect_uri']) ? home_url() : $_GET['redirect_uri'] . '&' . $query;

		if (is_wp_error($response)) {
			wp_redirect($redirectURI, 302);
			exit;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (empty($body['success'])) {
			wp_redirect($redirectURI, 302);
			exit;
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
		if (!empty($metadata) && 'false' === bsaGetConfig('DEBUG')) {
			return $metadata;
		}

		$response = wp_remote_post(bsaGetEnv('BSA_API_BASE_URL') . '/license-manager/v1/clients', [
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
			dd($response->get_error_message());
			return [];
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (empty($body['success'])) {
			return [];
		}

		return $body['data'] ?? [];
	}
}
