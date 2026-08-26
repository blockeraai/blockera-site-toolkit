<?php
/**
 * My Account Licenses section for Blockera Site Toolkit.
 *
 * @package BlockeraAI\SiteToolkit
 * @since   1.0.0
 * @author Blockera
 *
 * @var array $mappedLicenses Mapped license list for the current customer.
 */

defined( 'ABSPATH' ) || exit;

// Included as WC My Account content — use return (not exit) so theme header/footer still render.
if ( ! defined( 'YITH_YWSBS_INIT' ) ) {
	echo '<p class="woocommerce-info">' . esc_html__(
		'Subscriptions plugin is required to manage licenses.',
		'blockera-site-toolkit'
	) . '</p>';
	return;
}

do_action( 'blockera_site_toolkit_before_licenses_view' );

if ( empty( $mappedLicenses ) ) :
	$no_subscription_text = apply_filters(
		'ywsbs_no_subscription_text',
		__( 'There is no active subscription for your account.', 'blockera-site-toolkit' )
	);
	?>
	<p class="ywsbs-my-subscriptions"><?php echo esc_html( $no_subscription_text ); ?></p>
	<?php
else :
	?>
	<script>
		window.blockeraaiNonce = '<?php echo esc_js( wp_create_nonce( 'blockera-site-toolkit' ) ); ?>';
		window.restURL = '<?php echo esc_url_raw( rest_url( '/auth/v1/licenses/create' ) ); ?>';
		window.blockeraSiteToolkitLicenses = <?php echo wp_json_encode( $mappedLicenses ); ?>;
	</script>
	<div id="blockera-site-toolkit-subscription-manager"></div>
	<?php
endif;

do_action( 'blockera_site_toolkit_after_licenses_view' );
