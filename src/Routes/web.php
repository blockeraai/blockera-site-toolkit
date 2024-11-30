<?php

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
    }
);
