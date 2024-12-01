<?php

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
