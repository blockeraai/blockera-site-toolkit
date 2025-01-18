<?php

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Setup;

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

            // Redirect to license manager page.
            wp_redirect(add_query_arg($_GET, home_url('/consent-form')));

            // Stop further WordPress execution for this request.
            exit;
        }

        if (get_query_var('consent-form')) {

            wp_head();

            include Setup::getInstance()->getPath() . '/packages/site-toolkit/php/Views/consent-form.php';

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
