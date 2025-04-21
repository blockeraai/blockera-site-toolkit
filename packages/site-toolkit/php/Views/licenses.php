<?php

/**
 * My Account Subscriptions Section of Blockera Site Toolkit WooCommerce Subscription
 *
 * @package Blockera\Site\Toolkit
 * @since   1.0.0
 * @author Blockera
 *
 * @var array $subscriptions Subscription List.
 * @var $max_pages
 * @var $current_page
 */

defined('YITH_YWSBS_INIT') || exit; // Exit if accessed directly.

do_action('blockera_site_toolkit_before_licenses_view');

?>
<?php if (empty($mappedLicenses)) : ?>
    <?php $no_subscription_text = apply_filters('ywsbs_no_subscription_text', __('There is no active subscription for your account.', 'blockera-site-toolkit')); ?>
    <p class="ywsbs-my-subscriptions"><?php esc_html_e($no_subscription_text); ?></p>
<?php else : ?>
    <script>
        window.blockeraaiNonce = '<?php echo wp_create_nonce('blockera-site-toolkit'); ?>';
        window.restURL = '<?php echo rest_url('/auth/v1/licenses/create'); ?>';
        window.blockeraSiteToolkitLicenses = <?php echo json_encode($mappedLicenses); ?>;
    </script>
    <div id="blockera-site-toolkit-subscription-manager"></div>
<?php endif;

do_action('blockera_site_toolkit_after_licenses_view');
