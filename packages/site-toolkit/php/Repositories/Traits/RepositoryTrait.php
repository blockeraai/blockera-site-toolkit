<?php

namespace BlockeraAI\SiteToolkit\Repositories\Traits;

trait RepositoryTrait
{
	/**
	 * The wpdb instance.
	 *
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * The errors array.
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * The api table prefix.
	 *
	 * @var string
	 */
	protected string $api_table_prefix = '';

	public function __construct()
	{
		global $wpdb;

		$this->wpdb = $wpdb;
		$this->api_table_prefix = bsaGetEnv('API_TABLE_PREFIX');
	}
}
