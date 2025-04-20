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
?>
<div id="blockera-site-toolkit-consent-form"></div>
<script>
	window.blockeraProductId = '<?php echo $_GET['product'] ?? ''; ?>';
	window.clientId = '<?php echo $clientId; ?>';
	window.shopUrl = '<?php echo home_url('/shop'); ?>';
	window.isConsentForm = true;
	window.clientUrl = '<?php echo $rawUrl['scheme'] . '://' . $rawUrl['host']; ?>';
	window.clientWebsite = '<?php echo $domain; ?>';
	window.redirectUrl = '<?php echo add_query_arg('registered-client', 'true', Utils::extractParamFromURL(Utils::getCurrentPageURL(), 'redirect_uri')); ?>';
	window.consentNonce = '<?php echo wp_create_nonce('blockera-site-toolkit'); ?>';
	window.blockeraSiteToolkitLicenses = <?php echo json_encode($mappedLicenses); ?>;
</script>
