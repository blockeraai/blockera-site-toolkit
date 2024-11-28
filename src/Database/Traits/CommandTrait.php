<?php

namespace BlockeraAI\SiteToolkit\Database\Traits;

use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

trait CommandTrait
{
    public function fresh(): void
    {
        global $wpdb;

        if (!$this instanceof Migration) {
            \WP_CLI::error("🚨 ERROR: Table was not valid!");

            return;
        }

        $table_name = $wpdb->prefix . $this->getTableName();
        $wpdb->query("TRUNCATE TABLE $table_name");

        \WP_CLI::success("Table {$table_name} has been reset ✅");
    }
}
