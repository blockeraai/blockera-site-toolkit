<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class ClientRepository
{
	use RepositoryTrait;

	/**
	 * Get a client by a field.
	 *
	 * @param string $field The field name.
	 * @param mixed $value The field value.
	 *
	 * @return array|null The client data.
	 */
	public function getBy(string $field, $value): ?array
	{
		return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM api_oauth_clients WHERE $field = %s", $value), ARRAY_A);
	}
}
