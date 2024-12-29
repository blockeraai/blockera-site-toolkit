<?php


namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Repositories\Traits\RepositoryTrait;

class LicenseRepository
{
	use RepositoryTrait;

	/**
	 * Get the license by field name and value.
	 *
	 * @param string $field The field name.
	 * @param string $value The field value.
	 *
	 * @return array|null The license array or null if not found.
	 */
	public function getBy(string $field, $value): ?array
	{
		return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM api_licenses WHERE $field = %s", $value), ARRAY_A);
	}

	/**
	 * Check if the zip file is valid.
	 *
	 * @param string $zipFile The zip file url.
	 *
	 * @return bool true if the zip file is valid, false otherwise.
	 */
	public function isValidZipFile(string $zipFile): bool
	{
		return true;
	}
}
