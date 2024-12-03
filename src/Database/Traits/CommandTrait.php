<?php

namespace BlockeraAI\SiteToolkit\Database\Traits;

use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

trait CommandTrait
{
    /**
     * Fresh method to reset the table.
     *
     * @return void
     */
    public function fresh(): void
    {
        global $wpdb;

        if (!$this instanceof Migration) {
            if (class_exists(\WP_CLI::class)) {
                \WP_CLI::error("🚨 ERROR: Table was not valid!");
            }

            return;
        }

        $table_name = $wpdb->prefix . $this->getTableName();
        $wpdb->query("SET FOREIGN_KEY_CHECKS=0");
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");

        if (false === $result && class_exists(\WP_CLI::class)) {
            \WP_CLI::error("🚨 ERROR: Failed to truncate table {$wpdb->last_error}");
        } elseif (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("Table {$table_name} has been reset ✅");
        }
    }
}
