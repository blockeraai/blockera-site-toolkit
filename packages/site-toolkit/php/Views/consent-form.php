<?php
/**
 * Consent form view for Blockera Site Toolkit OAuth.
 *
 * @package BlockeraAI\SiteToolkit
 * @since   1.0.0
 * @author Blockera
 *
 * @var string $clientId Client ID.
 * @var string $domain Client website markup.
 * @var array  $raw_url Parsed redirect URI.
 * @var array  $mappedLicenses Mapped licenses for consent.
 */

use Blockera\Utils\Utils;

defined( 'ABSPATH' ) || exit;

// Included as a routed template — use return (not exit) so surrounding chrome can finish when needed.
if ( ! defined( 'YITH_YWSBS_INIT' ) ) {
	echo '<p class="woocommerce-info">' . esc_html__(
		'Subscriptions plugin is required for the consent form.',
		'blockera-site-toolkit'
	) . '</p>';
	return;
}

$host = $raw_url['host'];

if ( isset( $raw_url['port'] ) && ! empty( $raw_url['port'] ) ) {
	$host .= ':' . $raw_url['port'];
}

$client_url = $raw_url['scheme'] . '://' . $host;

?>
<div id="blockera-site-toolkit-consent-form"></div>
<script>
	window.blockeraProductId = '<?php echo esc_js( isset( $_GET['product'] ) ? sanitize_text_field( wp_unslash( $_GET['product'] ) ) : '' ); ?>';
	window.clientId = '<?php echo esc_js( $clientId ); ?>';
	window.shopUrl = '<?php echo esc_url( home_url( '/shop' ) ); ?>';
	window.isConsentForm = true;
	window.clientUrl = '<?php echo esc_url( $client_url ); ?>';
	window.clientWebsite = <?php echo wp_json_encode( $domain ); ?>;
	window.redirectUrl = '<?php echo esc_url( add_query_arg( 'registered-client', 'true', Utils::extractParamFromURL( Utils::getCurrentPageURL(), 'redirect_uri' ) ) ); ?>';
	window.consentNonce = '<?php echo esc_js( wp_create_nonce( 'blockera-site-toolkit' ) ); ?>';
	window.blockeraSiteToolkitLicenses = <?php echo wp_json_encode( $mappedLicenses ); ?>;
</script>
