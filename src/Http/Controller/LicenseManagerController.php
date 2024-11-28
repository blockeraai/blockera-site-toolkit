<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use Blockera\Utils\View;
use Blockera\Utils\Utils;

class LicenseManagerController
{
    /**
     * Render the license manager view template.
     *
     * @return void
     */
    public function render(): void
    {
        // Compatible with the yith-woocommerce-subscription-premium plugin.
        if (!function_exists('ywsbs_get_status') || !function_exists('YWSBS_Subscription_Helper')) {
            return;
        }

        /**
         * Show the consent template for the current user's subscriptions.
         * The template will display differently based on the number of active subscriptions:
         * - If user has one subscription: Shows single subscription template.
         * - If user has multiple subscriptions: Shows multiple choice template.
         * - If user has no subscriptions: Shows empty state template.
         */

        $root_path = BSA_PLUGIN_DIR . '/src/Views/';
        $subscription_statuses = ywsbs_get_status();

        $subscriptions = array_filter(
            YWSBS_Subscription_Helper()->get_subscriptions_by_user(
                get_current_user_id(),
                []
            ),
            [$this, 'isActiveSubscription']
        );

        $whoIs = Utils::extractDomainName($_GET['redirect_uri'] ?? '');
        $url = Utils::extractDomainName($_GET['redirect_uri'] ?? '', true);

        if (empty($_GET['redirect_uri']) && count($subscriptions)) {
            View::load('license-manager.activates', [], ['root-path' => $root_path]);
        } elseif (empty($_GET['redirect_uri']) && !count($subscriptions)) {
            View::load('license-manager.404', [], ['root-path' => $root_path]);
        } elseif (1 === count($subscriptions)) {
            $subscription_post = $subscriptions[0];
            $subscription_id = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;

            View::load('license-manager.single-consent-form', compact('url', 'whoIs', 'subscription_id', 'subscription_statuses'), ['root-path' => $root_path]);
        } elseif (1 < count($subscriptions)) {
            View::load('license-manager.archive-consent-form', compact('url', 'whoIs', 'subscriptions', 'subscription_statuses'), ['root-path' => $root_path]);
        } else {
            View::load('license-manager.404', [], ['root-path' => $root_path]);
        }
    }

    /**
     * Filter subscription by checking if subscription status is active.
     *
     * @param \WP_Post $subscription_post
     * @return boolean
     */
    protected function isActiveSubscription(\WP_Post $subscription_post): bool
    {
        $subscription_statuses = ywsbs_get_status();
        $subscription_id = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;
        $subscription = ywsbs_get_subscription($subscription_id);

        return !empty($subscription_statuses[$subscription->get_status()]) && 'active' === $subscription_statuses[$subscription->get_status()];
    }
}
