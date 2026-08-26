<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class SubscriptionRepository {

	use RepositoryTrait;

	/**
	 * Store the table name.
	 *
	 * @var string $table_name The table name property.
	 */
	protected $table_name = 'yith_ywsbs_stats';

	/**
	 * Get the subscriptions by user id.
	 *
	 * @param int $user_id The user id.
	 *
	 * @throws \Exception When YITH WooCommerce Subscription Premium is missing.
	 *
	 * @return array
	 */
	public function getSubscription( int $user_id ): array {
		// Compatible with the yith-woocommerce-subscription-premium plugin.
		if ( ! function_exists( 'ywsbs_get_status' ) || ! function_exists( 'YWSBS_Subscription_Helper' ) ) {

			throw new \Exception( __( 'YITH WooCommerce Subscription Premium plugin is required.', 'blockera-site-toolkit' ), 500 );
		}

		return YWSBS_Subscription_Helper()->get_subscriptions_by_user( $user_id, [] );
	}

	/**
	 * Get the subscription id by product id.
	 *
	 * @param int $product_id The product id.
	 *
	 * @return int|null The subscription id or null if not found.
	 */
	public function getSubscriptionIdByProductId( int $product_id ): ?int {
		$table = $this->wpdb->prefix . 'yith_ywsbs_stats';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Table name uses trusted wpdb prefix.
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT subscription_id FROM {$table} WHERE product_id = %s",
				$product_id
			)
		);
		// phpcs:enable
	}

	/**
	 * Get the subscription client id.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @return string|null The subscription client id or null if not found.
	 */
	public function getSubscriptionClientId( int $subscriptionId ): ?string {
		$table = $this->api_table_prefix . 'licenses';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Table name uses trusted API prefix.
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT client_id FROM {$table} WHERE subscription_id = %s",
				$subscriptionId
			)
		);
		// phpcs:enable
	}

	/**
	 * Check if the subscription is active.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @throws \Exception When YITH WooCommerce Subscription Premium is missing.
	 *
	 * @return bool true if the subscription is active, false otherwise.
	 */
	public function isActiveSubscription( int $subscriptionId ): bool {
		// Compatible with the yith-woocommerce-subscription-premium plugin.
		if ( ! function_exists( 'ywsbs_get_subscription' ) ) {

			throw new \Exception( __( 'YITH WooCommerce Subscription Premium plugin is required.', 'blockera-site-toolkit' ), 500 );
		}

		$subscription = ywsbs_get_subscription( $subscriptionId );

		return 'active' === $subscription->get_status();
	}

	/**
	 * Get the product id.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @return int|null int if the product id is found, null otherwise.
	 */
	public function getProductId( int $subscriptionId): ?int {
		if (! function_exists('ywsbs_get_subscription')) {
			return null;
		}

		$subscription = ywsbs_get_subscription($subscriptionId);

		return $subscription->get_product_id();
	}
}
