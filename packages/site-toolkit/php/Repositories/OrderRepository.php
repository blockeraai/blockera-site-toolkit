<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use Blockera\Bootstrap\Application;

class OrderRepository
{
    /**
     * Store the application instance.
     *
     * @var Application $app
     */
    protected $app;

    /**
     * Store the orders.
     *
     * @var array $orders
     */
    protected $orders = [];

    /**
     * Store the mapped licenses.
     *
     * @var array $licenses
     */
    protected $licenses = [];

    /**
     * Create a new order repository instance.
     *
     * @param Application $app The application instance.
     * @param array $orders The orders.
     */
    public function __construct(Application $app, array $orders)
    {
        $this->app = $app;
        $this->orders = $orders;

        array_map([$this, 'prepareLicense'], $this->orders);
    }

    /**
     * Get the licenses.
     *
     * @return array
     */
    public function getLicenses(): array
    {
        return $this->licenses;
    }

    /**
     * Set the licenses.
     *
     * @param array $licenses The licenses.
     *
     * @return void
     */
    public function setLicenses(array $licenses): void
    {
        $this->licenses = $licenses;
    }

    /**
     * Prepare the license.
     *
     * @param \WC_Order $order The order.
     *
     * @return void
     */
    public function prepareLicense(\WC_Order $order): void
    {
        foreach ($order->get_items() as $item) {
            $licenses = $this->processOrderItemProduct($order, $item);
            $this->setLicenses(array_merge($this->licenses, $licenses));
        }
    }

    /**
     * Process the order item product.
     *
     * @param \WC_Order $order The order.
     * @param \WC_Order_Item_Product $item The item.
     *
     * @return array
     */
    protected function processOrderItemProduct(\WC_Order $order, \WC_Order_Item_Product $item): array
    {
        $mappedLicenses = [];
        $productId = $item->get_product_id();
        $product = wc_get_product($productId);
        $variationId = $item->get_variation_id();

        // If the product is not a variation or product is not found, skip it.
        if (! $variationId || !$product) {
            return $mappedLicenses;
        }

        $itemSubscriptions = $order->get_meta('subscriptions');

        if(!empty($itemSubscriptions)) {
			$mappedSubscriptionLicenses = $this->processOrderItemSubscriptions($itemSubscriptions);

			if (!empty($mappedSubscriptionLicenses)) {
				$mappedLicenses = array_merge($mappedLicenses, $mappedSubscriptionLicenses);
			}
		}

		// If the license already exists, return the mapped licenses.
		if ((!empty($mappedLicenses) && in_array($variationId, array_column($mappedLicenses, 'id'))) || $order->get_meta('_subscription_id') ) {
			return $mappedLicenses;
		}

        /**
         * @var LicenseRepository $licenseRepository
         */
        $licenseRepository = $this->app->make(LicenseRepository::class);

        $licenses = bsaFilterActiveLicenses(
            $licenseRepository
                ->where('license_id', $productId)
                ->orWhere('license_id', $variationId)
                ->get()
        );
		$developmentWebsites = $this->getNormalizedWebsites($licenses, 'development');
		$productionWebsites = $this->getNormalizedWebsites($licenses, 'production');

        $fallbackDownloadableFiles = get_post_meta($productId, 'product_downloadable_files', true);
        $isActivatedFreeDownload = get_post_meta($productId, 'product_is_activated_free_download', true);
        $freeSlug = get_post_meta($productId, 'product_free_slug', true);

        $mappedLicenses[] = [
            'expiryDate' => '',
			'id' => $variationId,
            'upgradable' => false,
            'isAutoRenew' => false,
			'productId' => $productId,
			'licenseId' => $variationId,
            'type' => 'non-subscription',
			'variationId' => $variationId,
			'orderId' => $order->get_id(),
            'status' => $order->get_status(),
            'subscriptionId' => $order->get_id(),
            'productTitle' => $product->get_name(),
            'productLogo' => get_the_post_thumbnail_url($productId),
            'plan' => get_post_meta($variationId, 'attribute_plan', true),
            'maxDomains' => get_post_meta($variationId, 'max_domains', true),
            'productColor' => get_post_meta($productId, 'product_color', true),
            'productVersion' => get_post_meta($productId, 'product_version', true),
            'updatedOn' => date_i18n(wc_date_format(), $order->get_date_modified()->getTimestamp()),
            'startDate' => date_i18n(wc_date_format(), $order->get_date_created()->getTimestamp()),
			'developmentWebsites' => $developmentWebsites,
            'activeWebsites' => $productionWebsites,
			'downloads' => bsaGetDownloadableFiles($variationId, compact('fallbackDownloadableFiles', 'isActivatedFreeDownload', 'freeSlug')),
        ];

        return $mappedLicenses;
    }

    /**
     * Process the order item subscription.
     *
     * @param array $subscriptions The subscriptions array of post ids or objects.
     *
     * @return array
     */
    protected function processOrderItemSubscriptions(array $subscriptions): array
    {
        return array_map([$this, 'processOrderItemSubscription'], $subscriptions);
    }

    /**
     * Process the order item subscription.
     *
     * @param \WP_Post|int $subscriptionPost The subscription post.
     *
     * @return array
     */
    protected function processOrderItemSubscription($subscriptionPost): array
    {
        $subscriptionStatusList = ywsbs_get_status();
        $subscriptionId       = is_numeric($subscriptionPost) ? $subscriptionPost : $subscriptionPost->ID;
        $subscription          = ywsbs_get_subscription($subscriptionId);

		$productId = (int) $subscription->get('product_id');
        $product = wc_get_product($productId);
		$variationId = (int) $subscription->get('variation_id');

        $nameParts            = explode(' - ', $subscription->get('product_name'));
        $subscriptionName     = count($nameParts) > 1 ? $nameParts[1] : $subscription->get('product_name');
        $subscriptionStatus   = $subscriptionStatusList[$subscription->get_status()];
        $nextPaymentDueDate = (! in_array($subscriptionStatus, array('paused', 'cancelled'), true) && $subscription->get('payment_due_date')) ? date_i18n(wc_date_format(), $subscription->get('payment_due_date')) : '<span class="empty-date">-</span>';
        $endDate              = ($subscription->get('end_date')) ? date_i18n(wc_date_format(), $subscription->get('end_date')) : false;
        $endDate              = ! $endDate && ($subscription->get('expired_date')) ? date_i18n(wc_date_format(), $subscription->get('expired_date')) : '<div class="empty-date">-</div>';
        $startDate            = ($subscription->get('start_date')) ? date_i18n(wc_date_format(), $subscription->get('start_date')) : date_i18n(wc_date_format(), strtotime('-1 year', strtotime($endDate)));

        /**
         * @var LicenseRepository $licenseRepository
         */
        $licenseRepository = $this->app->make(LicenseRepository::class);

        $licenses = bsaFilterActiveLicenses(
            $licenseRepository->getBy(
                'license_id',
                $subscriptionId
            )
        );
		$developmentWebsites = $this->getNormalizedWebsites($licenses, 'development');
		$productionWebsites = $this->getNormalizedWebsites($licenses, 'production');

        $fallbackDownloadableFiles = get_post_meta($productId, 'product_downloadable_files', true);
        $isActivatedFreeDownload = get_post_meta($productId, 'product_is_activated_free_download', true);
        $freeSlug = get_post_meta($productId, 'product_free_slug', true);

        return [
            'id' => $variationId,
            'type' => 'subscription',
			'productId' => $productId,
            'startDate' => $startDate,
			'variationId' => $variationId,
			'licenseId' => $subscriptionId,
            'status' => $subscriptionStatus,
            'subscriptionId' => $subscriptionId,
			'orderId' => $subscription->get_order()->id,
            'productTitle' => get_the_title($productId),
            'upgradable' => $subscription->get('upgradable'),
            'isAutoRenew' => $subscription->get('is_auto_renew'),
            'productLogo' => get_the_post_thumbnail_url($productId),
            'plan' => get_post_meta($variationId, 'attribute_plan', true),
            'maxDomains' => get_post_meta($variationId, 'max_domains', true),
            'productColor' => get_post_meta($productId, 'product_color', true),
            'productVersion' => get_post_meta($productId, 'product_version', true),
            'updatedOn' => date_i18n(wc_date_format(), $subscription->post_modified),
            'developmentWebsites' => $developmentWebsites,
            'activeWebsites' => $productionWebsites,
            'expiryDate' => wp_kses_post(date_i18n(wc_date_format(), strtotime('+1 year', strtotime($startDate)))),
            'downloads' => bsaGetDownloadableFiles($variationId, compact('fallbackDownloadableFiles', 'isActivatedFreeDownload', 'freeSlug')),
        ];
    }

	/**
	 * Get the subscription info.
	 *
	 * @param array $license The license array info.
	 *
	 * @return array
	 */
	public function getLicenseInfo(array $license): array
	{
		$licenseId = $license['id'];

		if('subscription' === $license['type']) {	
			$subscription_statuses = ywsbs_get_status();
			$subscription = ywsbs_get_subscription($licenseId);
			$name = sprintf('%s - %s', $subscription->get_number(), $subscription->get('product_name'));
			$status = $subscription_statuses[$subscription->get_status()];
			$nextPaymentDueDate = (! in_array($status, array('paused', 'cancelled'), true) && $subscription->get('payment_due_date')) ? date_i18n(wc_date_format(), $subscription->get('payment_due_date')) : '<span class="empty-date">-</span>';
			$startDate = ($subscription->get('start_date')) ? date_i18n(wc_date_format(), $subscription->get('start_date')) : '<div class="empty-date">-</div>';
			$endDate = ($subscription->get('end_date')) ? date_i18n(wc_date_format(), $subscription->get('end_date')) : false;
			$endDate = ! $endDate && ($subscription->get('expired_date')) ? date_i18n(wc_date_format(), $subscription->get('expired_date')) : '<div class="empty-date">-</div>';
			$description = empty($subscription->get('post_content')) ? $subscription->get('post_content') : $subscription->get('post_excerpt');
			$downloads = get_post_meta($subscription->get('variation_id'), '_downloadable_files', true);
			$productId = $subscription->get('product_id');
			$productVersion = get_post_meta($productId, 'product_version', true);
			$thumbnail = get_the_post_thumbnail_url($productId);
			$file = is_array($downloads) ? $this->getLatestVersionFile($downloads) : [];
			$versionId = $file['id'] ?? null;
			$productName = get_post_meta($productId, 'product_id', true);
			$id = $subscription->get('id');
			$type = 'subscription';

			return compact('id', 'type', 'name', 'description', 'status', 'thumbnail', 'nextPaymentDueDate', 'startDate', 'endDate', 'productName', 'productId', 'productVersion', 'versionId');
		}

		$product = wc_get_product($license['product_id']);

		return [
			'type' => 'non-subscription',
			'id' => $licenseId,
			'name' => sprintf('%s - %s', $license['order_id'], $product->get_name()),
			'description' => $product->get_description(),
			'status' => 'active',
			'thumbnail' => get_the_post_thumbnail_url($license['product_id']),
			'nextPaymentDueDate' => '<span class="empty-date">-</span>',
			'startDate' => '<span class="empty-date">-</span>',
			'endDate' => '<span class="empty-date">-</span>',
			'productName' => $product->get_name(),
			'productId' => $license['product_id'],
			'productVersion' => get_post_meta($license['product_id'], 'product_version', true),
			'versionId' => null,
		];
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
	 * Get the normalized websites.
	 *
	 * @param array $licenses The licenses.
	 * @param string $type The domain type. default is production.
	 *
	 * @return array
	 */
	protected function getNormalizedWebsites(array $licenses, string $type = 'production'): array
	{
		$websites = [];
		$filteredLicenses = array_filter($licenses, function(array $license)use($type):bool{
			return $type === $license['domain_type'];
		});

		foreach ($filteredLicenses as $license) {
			$websites[$license['id']] = [
				'website' => $license['domain'],
				'mode' => $license['domain_type'],
			];
		}

		return $websites;
	}
}
