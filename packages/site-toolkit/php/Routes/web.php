<?php

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Setup;
use BlockeraAI\SiteToolkit\Repositories\OrderRepository;

// Register query variable.
add_filter( 'query_vars', 'bsa_register_query_vars' );

/**
 * Register authorize page query variable.
 *
 * @param array $vars The query variables.
 * @return array
 */
function bsa_register_query_vars( array $vars ): array {
    $vars[] = 'authorize';
    $vars[] = 'consent-form';

    return $vars;
}

// Check if custom page is requested.
add_action(
    'template_redirect',
    function (): void {
        if (get_query_var('authorize')) {
            global $wp;

            if (! is_user_logged_in()) {
                wp_safe_redirect(home_url('/wp-login.php/?redirect_to=' . urlencode($wp->request . '/?' . $_SERVER['QUERY_STRING'])));
                exit;
            }

			// If the product is set, then we need to add it to the client credentials.
			if (! empty($_GET['product']) && ! empty($_COOKIE['token_key'])) {
				$userId            = wp_get_current_user()->ID;
				$clientCredentials = get_user_meta($userId, $_COOKIE['token_key'], true);

				if (! empty($clientCredentials) && ! empty($clientCredentials['products']) && ! in_array($_GET['product'], $clientCredentials['products'], true)) {
					$clientCredentials['products'][] = $_GET['product'];

					update_user_meta($userId, $_COOKIE['token_key'], $clientCredentials);
				}
			}

            // Redirect to license manager page.
            wp_redirect(add_query_arg($_GET, home_url('/consent-form')));

            // Stop further WordPress execution for this request.
            exit;
        }

        if (get_query_var('consent-form')) {

            wp_head();

			$setupInstance  = Setup::getInstance();
			$mappedLicenses = $setupInstance->make(OrderRepository::class, [ 'context' => 'consent-form' ])->getLicenses();
			
			$user   = wp_get_current_user();
			$userId = $user->ID;

			if (! $userId) {
				echo '<script>window.location.href = "' . esc_url(home_url('/my-account')) . '";</script>';
				exit;
			}

			$currentUrl   = Utils::getCurrentPageURL();
			$transientKey = 'blockera-site-toolkit-user' . $userId . '__redirect_uri';
			$transient    = get_transient($transientKey);

			if (! empty($transient)) {
				delete_transient($transientKey);

				echo '<script>window.location.href = "' . esc_url(urldecode($transient)) . '";</script>';
				exit;
			}

			$clientInfo = isset($_COOKIE['token_key']) ? get_user_meta(get_current_user_id(), $_COOKIE['token_key'] ?? '', true) : [];
			$clientId   = $clientInfo['client_id'] ?? '';
			$raw_url    = parse_url( urldecode( $_GET['redirect_uri'] ) );
			$domain     = '<div class="client-website"><span class="client-website-scheme">' . $raw_url['scheme'] . '://' . '</span> ' . $raw_url['host'] . '</div>';

            $templateFile = $setupInstance->getPath() . '/vendor/blockera/site-toolkit/php/Views/consent-form.php';

			include $templateFile;

            wp_footer();

			// Unset the token key cookie.
			unset($_COOKIE['token_key']);

            exit;
        }

        // FIXME: add powerful logic here to validate this request.
        $fromClientSettings = false !== strpos(Utils::getCurrentPageURL(), 'connect-with-account');

        if (! empty($_GET['state']) && ! empty($_GET['response_type']) && ! empty($_GET['approval_prompt']) && is_user_logged_in() && $fromClientSettings) {
            $params          = bsaGetRegisterClientParams();
            $params['event'] = 'auto-connect';

            $userCredentials = bsaGetUserAccessToken();
            bsaDoStoreClient($params, $userCredentials['token_type'] . ' ' . $userCredentials['access_token'], 'blockera_api_client_info_' . md5($params['domain']));
        }
    }
);
