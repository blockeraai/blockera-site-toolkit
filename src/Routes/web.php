<?php

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Repositories\ClientRepository;

$setup = $this;

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
    $vars[] = 'license-action';
    $vars[] = 'register-license';

    return $vars;
}

// Check if custom page is requested.
add_action(
    'template_redirect',
    function () use ($setup): void {
        if (get_query_var('authorize')) {
            global $wp;

            if (!is_user_logged_in()) {
                wp_safe_redirect(home_url('/wp-login.php/?redirect_to=' . urlencode($wp->request . '/?' . $_SERVER['QUERY_STRING'])));
                exit;
            }

            // Redirect to license manager page.
            wp_redirect(add_query_arg($_GET, home_url('/my-account/license-manager/')));

            // Stop further WordPress execution for this request.
            exit;
        }

        if (get_query_var('register-license')) {
            $request = new \WP_REST_Request('POST', '/auth/v1/licenses/create');
            $request->set_body_params($_POST);
            $request->set_header('referer', home_url());

            $responseObject = rest_do_request($request);

            bsaValidateResponse($responseObject);

            $responseObject->get_data();

            // Redirect back to the client application after successful license registration.
            wp_redirect(add_query_arg(['connectedWithYourAccount' => 'true'], $_POST['redirect_uri']));

            exit;
        }

        if ('add' === get_query_var('license-action')) {
            $clientRepository = $setup->make(ClientRepository::class);

            $client = $clientRepository->getClientBy('domain', $_POST['domain']);

            if (!$client) {
                $_POST['redirect_uri'] = $_SERVER['HTTP_REFERER'];

                $_POST = array_merge(
                    $_POST,
                    bsaDoRegisterClientRequest($setup, false)
                );
            }

            $request = new \WP_REST_Request('POST', '/auth/v1/licenses/create');
            $request->set_body_params($client ? array_merge($_POST, (array)$client) : $_POST);
            $request->set_header('referer', home_url());

            $responseObject = rest_do_request($request);

            bsaValidateResponse($responseObject);

            $data = $responseObject->get_data();

            if ($data['success']) {
                wp_redirect($_POST['redirect_uri'] . '?registered-client=true');
            }
            // TODO: implements view
        }

        $fromClientSettings = str_ends_with(Utils::getCurrentPageURL(), 'connect-with-account');

        if (!empty($_GET['state']) && !empty($_GET['response_type']) && !empty($_GET['approval_prompt']) && is_user_logged_in() && $fromClientSettings) {
            bsaDoRegisterClientRequest($setup);
        }
    }
);
