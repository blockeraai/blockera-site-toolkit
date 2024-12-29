<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class SubscriptionRepository
{
	use RepositoryTrait;

	/**
	 * Get the subscriptions by user id.
	 *
	 * @param int $user_id The user id.
	 *
	 * @return array
	 */
	public function getSubscription(int $user_id): array
	{
		// Compatible with the yith-woocommerce-subscription-premium plugin.
		if (!function_exists('ywsbs_get_status') || !function_exists('YWSBS_Subscription_Helper')) {

			throw new \Exception(__('YITH WooCommerce Subscription Premium plugin is required.', 'blockera-site-toolkit'), 500);
		}

		return YWSBS_Subscription_Helper()->get_subscriptions_by_user($user_id, []);
	}

	/**
	 * Get the subscription id by product id.
	 *
	 * @param int $product_id The product id.
	 *
	 * @return int|null The subscription id or null if not found.
	 */
	public function getSubscriptionIdByProductId(int $product_id): ?int
	{
		return $this->wpdb->get_var($this->wpdb->prepare("SELECT subscription_id FROM {$this->wpdb->prefix}yith_ywsbs_stats WHERE product_id = %s", $product_id));
	}

	/**
	 * Get the subscription info.
	 *
	 * @param int $subscription_id The subscription id.
	 *
	 * @return array
	 */
	public function getSubscriptionInfo(int $subscription_id): array
	{
		$subscription_statuses = ywsbs_get_status();
		$subscription = ywsbs_get_subscription($subscription_id);
		$subscription_name = sprintf('%s - %s', $subscription->get_number(), $subscription->get('product_name'));
		$subscription_status = $subscription_statuses[$subscription->get_status()];
		$next_payment_due_date = (! in_array($subscription_status, array('paused', 'cancelled'), true) && $subscription->get('payment_due_date')) ? date_i18n(wc_date_format(), $subscription->get('payment_due_date')) : '<span class="empty-date">-</span>';
		$start_date = ($subscription->get('start_date')) ? date_i18n(wc_date_format(), $subscription->get('start_date')) : '<div class="empty-date">-</div>';
		$end_date = ($subscription->get('end_date')) ? date_i18n(wc_date_format(), $subscription->get('end_date')) : false;
		$end_date = ! $end_date && ($subscription->get('expired_date')) ? date_i18n(wc_date_format(), $subscription->get('expired_date')) : '<div class="empty-date">-</div>';
		$description = empty($subscription->get('post_content')) ? $subscription->get('post_content') : $subscription->get('post_excerpt');
		$downloads = get_post_meta($subscription->get('variation_id'), '_downloadable_files', true);
		$productId = $subscription->get('product_id');
		$productVersion = get_post_meta($productId, 'product_version', true);

		$file = is_array($downloads) ? $this->getLatestVersionFile($downloads): [];
		$versionId = $file['id'] ?? null;

		return compact('subscription_name', 'subscription_status', 'next_payment_due_date', 'start_date', 'end_date', 'description', 'productId', 'productVersion', 'versionId');
	}

	/**
	 * Get the latest version file.
	 *
	 * @param array $files The files.
	 *
	 * @return array|null The latest version file.
	 */
	private function getLatestVersionFile(array $files): ?array
	{
		$latest = null;
		$highestVersion = null;

		foreach ($files as $file) {
			// Skip if file name is not set
			if (!isset($file['name'])) {
				continue;
			}

			// Extract version number using regex
			if (preg_match('/v(\d+(?:\.\d+)*)/i', $file['name'], $matches)) {
				$version = $matches[1];
				$versionParts = array_map('intval', explode('.', $version));

				// If this is the first valid version or higher than current highest
				if ($highestVersion === null || $this->compareVersions($versionParts, $highestVersion) > 0) {
					$highestVersion = $versionParts;
					$latest = $file;
				}
			}
		}

		return $latest;
	}

	/**
	 * Compare version arrays.
	 *
	 * @param array $version1 The first version.
	 * @param array $version2 The second version.
	 *
	 * @return int The comparison result.
	 */
	private function compareVersions(array $version1, array $version2): int
	{
		$maxLength = max(count($version1), count($version2));

		// Pad arrays with zeros if needed
		$version1 = array_pad($version1, $maxLength, 0);
		$version2 = array_pad($version2, $maxLength, 0);

		for ($i = 0; $i < $maxLength; $i++) {
			if ($version1[$i] > $version2[$i]) {
				return 1;
			}
			if ($version1[$i] < $version2[$i]) {
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Get the subscription client id.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @return string|null The subscription client id or null if not found.
	 */
	public function getSubscriptionClientId(int $subscriptionId): ?string
	{
		return $this->wpdb->get_var($this->wpdb->prepare("SELECT client_id FROM api_licenses WHERE subscription_id = %s", $subscriptionId));
	}

	/**
	 * Check if the subscription is active.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @return bool true if the subscription is active, false otherwise.
	 */
	public function isActiveSubscription(int $subscriptionId): bool
	{
		// Compatible with the yith-woocommerce-subscription-premium plugin.
		if (!function_exists('ywsbs_get_subscription')) {

			throw new \Exception(__('YITH WooCommerce Subscription Premium plugin is required.', 'blockera-site-toolkit'), 500);
		}

		$subscription = ywsbs_get_subscription($subscriptionId);

		return 'active' === $subscription->get_status();
	}

	/**
	 * Check if the subscription is downloadable.
	 *
	 * @param string $domain The domain.
	 * @param int $userId The user id.
	 *
	 * @return bool true if the subscription is downloadable, false otherwise.
	 */
	// public function isDownloadable(string $domain, int $userId): bool
	// {
	//     WC()->customer = new \WC_Customer($userId);
	//     $customer = WC()->customer;

	//     if (!$customer) {
	//         return false;
	//     }

	//     $subscription_id = $this->clientRepository->getSubscriptionId($domain);

	//     if (!$subscription_id) {
	//         return false;
	//     }

	//     $downloads = $customer->get_downloadable_products();

	//     $productId = $this->getProductId($subscription_id);

	//     return count(
	//         array_filter($downloads, function (array $download) use ($productId): bool {
	//             if ($productId === $download['product_id']) {
	//                 return true;
	//             }

	//             return false;
	//         })
	//     ) > 0;
	// }

	/**
	 * Get the product id.
	 *
	 * @param int $subscriptionId The subscription id.
	 *
	 * @return int|null int if the product id is found, null otherwise.
	 */
	public function getProductId(int $subscriptionId): ?int
	{
		if (!function_exists('ywsbs_get_subscription')) {
			return null;
		}

		$subscription = ywsbs_get_subscription($subscriptionId);

		return $subscription->get_product_id();
	}
}
