<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class ClientRepository {

	use RepositoryTrait;

	/**
	 * Get a client by a field.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 *
	 * @return array|null The client data.
	 */
	public function getBy( string $field, $value ): ?array {
		$allowed_fields = [ 'client_id', 'user_id', 'name', 'redirect_uri' ];
		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return null;
		}

		$table = $this->api_table_prefix . 'oauth_clients';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Table/column from trusted prefix + whitelist.
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$field} = %s",
				$value
			),
			ARRAY_A
		);
		// phpcs:enable
	}
}
