<?php


namespace BlockeraAI\SiteToolkit\Repositories;

class LicenseRepository
{
    public function create(array $params): void
    {
        global $wpdb;

        $result = $wpdb->insert($wpdb->prefix . 'auth_licenses', $params);

        if (false === $result) {
            // You can throw an exception or handle the error as needed.
            throw new \Exception(__("Database error: ", 'blockera-site-toolkit') . $wpdb->last_error);
        }
    }

    public function getBy(string $field, $value): array
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}auth_licenses WHERE $field = %s", $value), ARRAY_A);
    }
}
