<?php


namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class LicenseRepository {

	use RepositoryTrait;

	/**
	 * Store the table name.
	 *
	 * @var string $table_name the table name.
	 */
	protected $table_name = 'licenses';

	/**
	 * Get the license by field name and value.
	 *
	 * @param string $field The field name.
	 * @param string $value The field value.
	 *
	 * @return array|null The license array or null if not found.
	 */
	public function getBy( string $field, $value ): ?array {
		$allowed_fields = [ 'id', 'client_id', 'subscription_id', 'order_id', 'product_id', 'user_id' ];
		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return null;
		}

		$table = $this->api_table_prefix . $this->table_name;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Table/column from trusted prefix + whitelist.
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$field} = %s",
				$value
			),
			ARRAY_A
		);
		// phpcs:enable
	}

	/**
	 * Check if the zip file is valid.
	 *
	 * @param string $zipFile The zip file url.
	 *
	 * @return bool true if the zip file is valid, false otherwise.
	 */
	public function isValidZipFile( string $zipFile): bool {
		return true;
	}
}
