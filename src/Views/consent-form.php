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

use Blockera\Utils\Utils;

defined('YITH_YWSBS_INIT') || exit; // Exit if accessed directly.

do_action('ywsbs_my_subscriptions_view_before');
$subscription_status_list = ywsbs_get_status();

$subscriptions = YITH_WC_Subscription()->get_user_subscriptions(get_current_user_id());

$user = wp_get_current_user();
$userId = $user->ID;

if (!$user->ID) {
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
}elseif (empty($subscriptions)) {
	$user = wp_get_current_user();
	$userId = $user->ID;

	set_transient($transientKey, urlencode($currentUrl), 60 * 60 * 24);

	echo '<script>window.location.href = "' . esc_url(home_url('/shop')) . '";</script>';
	exit;
}

foreach ($subscriptions as $subscription_post) {

	$subscription_post = is_numeric($subscription_post) ? get_post($subscription_post) : $subscription_post;
	$subscription_id       = $subscription_post->ID;
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

	$mappedSubscriptions[] = [
		'productTitle' => get_the_title($product_id),
		'productLogo' => get_the_post_thumbnail_url($product_id),
		'productColor' => get_post_meta($product_id, 'product_color', true),
		'plan' => $subscription_name,
		'subscriptionId' => $subscription_id,
		'maxDomains' => get_post_meta($subscription->get('variation_id'), 'max_domains', true),
		'activeWebsites' => $subscription->get('active_websites') ? $subscription->get('active_websites') : [],
		'expiryDate' => wp_kses_post(date_i18n(wc_date_format(), strtotime('+1 year', strtotime($start_date)))),
	];
}

?>
<?php if (empty($subscriptions)) : ?>
	<?php $no_subscription_text = apply_filters('ywsbs_no_subscription_text', __('There is no active subscription for your account.', 'blockera-site-toolkit')); ?>
	<p class="ywsbs-my-subscriptions"><?php esc_html_e($no_subscription_text); ?></p>
<?php else : ?>

	<div id="blockera-site-toolkit-consent-form"></div>
	<script>
		<?php
		$clientId = get_user_meta(get_current_user_id(), 'blockera_api_client_info', true)['client_id'];
		$rawUrl = parse_url(urldecode($_GET['redirect_uri']));
		$domain = '<div class="client-website"><span class="client-website-scheme">' . $rawUrl['scheme'] . '://' . '</span> ' . $rawUrl['host'] . '</div>';

		?>
		window.clientId = '<?php echo $clientId; ?>';
		window.isConsentForm = true;
		window.clientUrl = '<?php echo $rawUrl['scheme'] . '://' . $rawUrl['host']; ?>';
		window.clientWebsite = '<?php echo $domain; ?>';
		window.redirectUrl = '<?php echo add_query_arg('registered-client', 'true', Utils::extractParamFromURL(Utils::getCurrentPageURL(), 'redirect_uri')); ?>';
		window.consentNonce = '<?php echo wp_create_nonce('blockera-site-toolkit'); ?>';
		window.blockeraSiteToolkitSubscriptions = <?php echo json_encode($mappedSubscriptions); ?>;
	</script>
<?php endif; ?>