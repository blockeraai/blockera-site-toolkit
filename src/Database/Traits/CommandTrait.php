<?php

namespace BlockeraAI\SiteToolkit\Database\Traits;

use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

trait CommandTrait
{
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
        $wpdb->query("TRUNCATE TABLE $table_name");

        if (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("Table {$table_name} has been reset ✅");
        }
    }
}
