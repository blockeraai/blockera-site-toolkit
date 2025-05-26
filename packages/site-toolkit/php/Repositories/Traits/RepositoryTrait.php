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

	/**
	 * The constructor.
	 *
	 * @return void
	 */
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
	 * @param string $prefix_type The prefix type.
	 *
	 * @return array|null
	 */
	public function first(string $prefix_type = 'default'): ?array
	{
		if('default' === $prefix_type) {
			$this->api_table_prefix = $this->wpdb->prefix;
		}

		$where = implode(' ', $this->where);

		return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"), ARRAY_A);
	}

	/**
	 * Get all results.
	 *
	 * @param string $prefix_type The prefix type.
	 *
	 * @return array
	 */
	public function get(string $prefix_type = 'default'): array
	{
		if('default' === $prefix_type) {
			$this->api_table_prefix = $this->wpdb->prefix;
		}

		$where = implode(' ', $this->where);

		$results = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"), ARRAY_A);

		// Reset the WHERE clause.
		$this->resetWhere();

		return $results;
	}

	/**
	 * Get the count of results.
	 *
	 * @param string $prefix_type The prefix type.
	 *
	 * @return int
	 */
	public function count(string $prefix_type = 'default'): int
	{
		if('default' === $prefix_type) {
			$this->api_table_prefix = $this->wpdb->prefix;
		}

		$where = implode(' ', $this->where);

		return $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$this->api_table_prefix}{$this->table_name} WHERE {$where}"));
	}

	/**
	 * Reset the WHERE clause.
	 *
	 * @return self
	 */
	public function resetWhere(): self
	{
		$this->where = [];

		return $this;
	}
}
