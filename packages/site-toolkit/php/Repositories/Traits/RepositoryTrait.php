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
	 * The where array.
	 *
	 * @var array
	 */
	protected $where = [];

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

	/**
	 * Add a WHERE clause to the query.
	 *
	 * @param string $field The field name.
	 * @param mixed $value The field value.
	 *
	 * @return self
	 */
	public function where(string $field, $value): self
	{
		if (empty($this->where)) {
			$this->where[] = "$field = $value";
		} else {
			$this->where[] = "AND $field = $value"; 
		}

		return $this;
	}

	/**
	 * Add an OR WHERE clause to the query.
	 *
	 * @param string $field The field name.
	 * @param mixed $value The field value.
	 *
	 * @return self
	 */
	public function orWhere(string $field, $value): self
	{
		$this->where[] = "OR $field = $value";

		return $this;
	}

	/**
	 * Get the first result.
	 *
	 * @return array|null
	 */
	public function first(): ?array
	{
		$where = implode(' ', $this->where);

		return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"), ARRAY_A);
	}

	/**
	 * Get all results.
	 *
	 * @return array
	 */
	public function get(): array
	{
		$where = implode(' ', $this->where);

		return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"), ARRAY_A);
	}

	/**
	 * Get the count of results.
	 *
	 * @return int
	 */
	public function count(): int
	{
		$where = implode(' ', $this->where);

		return $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"));
	}
}
