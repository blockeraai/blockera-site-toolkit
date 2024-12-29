<?php

/**
 * My Account Subscriptions Section of Blockera Site Toolkit WooCommerce Subscription
 *
 * @package Blockera\Site\Toolkit
 * @since   1.0.0
 * @version 2.0.0
 * @author Blockera
 *
 * @var array $subscriptions Subscription List.
 * @var $max_pages
 * @var $current_page
 */

defined('YITH_YWSBS_INIT') || exit; // Exit if accessed directly.

do_action('ywsbs_my_subscriptions_view_before');
$subscription_status_list = ywsbs_get_status();
$downloads = WC()->customer->get_downloadable_products();

foreach ($subscriptions as $subscription_post) {

	$subscription_id       = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;
	$subscription          = ywsbs_get_subscription($subscription_id);
	$name_parts            = explode(' - ', $subscription->get('product_name'));
	$subscription_name     = count($name_parts) > 1 ? $name_parts[1] : $subscription->get('product_name');
	$subscription_status   = $subscription_status_list[$subscription->get_status()];
	$next_payment_due_date = (! in_array($subscription_status, array('paused', 'cancelled'), true) && $subscription->get('payment_due_date')) ? date_i18n(wc_date_format(), $subscription->get('payment_due_date')) : '<span class="empty-date">-</span>';
	$start_date            = ($subscription->get('start_date')) ? date_i18n(wc_date_format(), $subscription->get('start_date')) : '<div class="empty-date">-</div>';
	$end_date              = ($subscription->get('end_date')) ? date_i18n(wc_date_format(), $subscription->get('end_date')) : false;
	$end_date              = ! $end_date && ($subscription->get('expired_date')) ? date_i18n(wc_date_format(), $subscription->get('expired_date')) : '<div class="empty-date">-</div>';

	$product_id = $subscription->get('product_id');
	$product = wc_get_product($product_id);

	$license = (new BlockeraAI\SiteToolkit\Repositories\LicenseRepository())->getBy(
		'subscription_id',
		$subscription_id
	);

	$mappedSubscriptions[] = [
		'id' => $subscription->get('variation_id'),
		'downloads' => get_post_meta($subscription->get('variation_id'), '_downloadable_files', true),
		'productTitle' => get_the_title($product_id),
		'productLogo' => get_the_post_thumbnail_url($product_id),
		'productColor' => get_post_meta($product_id, 'product_color', true),
		'productVersion' => get_post_meta($product_id, 'product_version', true),
		'updatedOn' => date_i18n(wc_date_format(), $subscription_post->post_modified),
		'plan' => $subscription_name,
		'subscriptionId' => $subscription_id,
		'maxDomains' => get_post_meta($subscription->get('variation_id'), 'max_domains', true),
		'upgradable' => $subscription->get('upgradable'),
		'isAutoRenew' => $subscription->get('is_auto_renew'),
		'activeWebsites' => array_combine(array_column($license, 'id'), array_column($license, 'domain')),
		'startDate' => $start_date,
		'expiryDate' => wp_kses_post(date_i18n(wc_date_format(), strtotime('+1 year', strtotime($start_date)))),
	];
}

?>
<?php if (empty($subscriptions)) : ?>
	<?php $no_subscription_text = apply_filters('ywsbs_no_subscription_text', __('There is no active subscription for your account.', 'blockera-site-toolkit')); ?>
	<p class="ywsbs-my-subscriptions"><?php esc_html_e($no_subscription_text); ?></p>
<?php else : ?>
	<script>
		window.blockeraaiNonce = '<?php echo wp_create_nonce('blockera-site-toolkit'); ?>';
		window.restURL = '<?php echo rest_url('/auth/v1/licenses/create'); ?>';
		window.blockeraSiteToolkitSubscriptions = <?php echo json_encode($mappedSubscriptions); ?>;
	</script>
	<div id="blockera-site-toolkit-subscription-manager"></div>
<?php endif; ?>