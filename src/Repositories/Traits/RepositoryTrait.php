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

	public function __construct()
	{
		global $wpdb;

		$this->wpdb = $wpdb;
	}
}
