<?php

use Blockera\Utils\Utils;
use Blockera\Utils\View;
use BlockeraAI\SiteToolkit\Setup;
use BlockeraAI\SiteToolkit\Repositories\OrderRepository;

// Register query variable.
add_filter('query_vars', 'registerVars');

/**
 * Register authorize page query variable.
 *
 * @param array $vars The query variables.
 * @return void
 */
function registerVars(array $vars): array
{
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

            if (!is_user_logged_in()) {
                wp_safe_redirect(home_url('/wp-login.php/?redirect_to=' . urlencode($wp->request . '/?' . $_SERVER['QUERY_STRING'])));
                exit;
            }

			// If the product is set, then we need to add it to the client credentials.
			if (!empty($_GET['product'])) {
				$userId = wp_get_current_user()->ID;
				$clientCredentials = get_user_meta($userId, 'blockera_api_client_info', true);

				if (!empty($clientCredentials) && !empty($clientCredentials['products']) && !in_array($_GET['product'], $clientCredentials['products'])) {
					$clientCredentials['products'][] = $_GET['product'];

					update_user_meta($userId, 'blockera_api_client_info', $clientCredentials);
				}
			}

            // Redirect to license manager page.
            wp_redirect(add_query_arg($_GET, home_url('/consent-form')));

            // Stop further WordPress execution for this request.
            exit;
        }

        if (get_query_var('consent-form')) {

            wp_head();

			$setupInstance = Setup::getInstance();
			$mappedLicenses = $setupInstance->make(OrderRepository::class)->getLicenses();
			
			$user = wp_get_current_user();
			$userId = $user->ID;

			if (!$userId) {
				echo '<script>window.location.href = "' . esc_url(home_url('/my-account')) . '";</script>';
				exit;
			}

			$currentUrl = Utils::getCurrentPageURL();
			$transientKey = 'blockera-site-toolkit-user' . $userId . '__redirect_uri';
			$transient = get_transient($transientKey);

			if (!empty($transient)) {
				delete_transient($transientKey);

				echo '<script>window.location.href = "' . esc_url(urldecode($transient)) . '";</script>';
				exit;
			} elseif (empty($mappedLicenses)) {
				set_transient($transientKey, urlencode($currentUrl), 60 * 60 * 24);

				echo '<script>window.location.href = "' . esc_url(home_url('/shop')) . '";</script>';
				exit;
			}

			$clientInfo = get_user_meta(get_current_user_id(), 'blockera_api_client_info', true);
			$clientId = $clientInfo['client_id'] ?? '';
			$rawUrl = parse_url(urldecode($_GET['redirect_uri']));
			$domain = '<div class="client-website"><span class="client-website-scheme">' . $rawUrl['scheme'] . '://' . '</span> ' . $rawUrl['host'] . '</div>';


            $templateFile = 'build.src.SiteToolkit.Views.consent-form';

			if (!file_exists($templateFile)) {
				$templateFile = 'site-toolkit.php.Views.consent-form';
			}

			View::load($templateFile, compact('mappedLicenses', 'clientId', 'rawUrl', 'domain'), [
				'root-path' => $setupInstance->getPath() . '/vendor/blockera/',
			]);

            wp_footer();

            exit;
        }

        // FIXME: add powerful logic here to validate this request.
        $fromClientSettings = false !== strpos(Utils::getCurrentPageURL(), 'connect-with-account');

        if (!empty($_GET['state']) && !empty($_GET['response_type']) && !empty($_GET['approval_prompt']) && is_user_logged_in() && $fromClientSettings) {
            $params = bsaGetRegisterClientParams();
            $params['event'] = 'auto-connect';

            $userCredentials = bsaGetUserAccessToken();
            bsaDoStoreClient($params, $userCredentials['token_type'] . ' ' . $userCredentials['access_token']);
        }
    }
);
