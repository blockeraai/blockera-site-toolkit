<?php

namespace BlockeraAI\SiteToolkit\Database\Migrations;

use BlockeraAI\SiteToolkit\Database\Traits\CommandTrait;
use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

class AccessTokenTable implements Migration
{
    use CommandTrait;

    /**
     * Up method.
     *
     * @return void
     */
    public function up(): void
    {
        global $wpdb;

        $result = $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . $this->getTableName() . ' (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    access_token VARCHAR(255) NOT NULL,
    client_id VARCHAR(80) NOT NULL,
    scopes TEXT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY access_token (access_token),
    FOREIGN KEY (client_id) REFERENCES ' . $wpdb->prefix . 'auth_clients(client_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        if ($result === false) {
            $error = $wpdb->last_error;
            \WP_CLI::success("Failed to create table: " . $error);
            // throw new \Exception("Failed to create table: " . $error);
        } elseif (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("The {$this->getTableName()} table has been create ✅");
        }
    }

    /**
     * Down method.
     *
     * @return void
     */
    public function down(): void
    {
        global $wpdb;

        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . $this->getTableName());


        if (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("The {$this->getTableName()} table has been drop ✅");
        }
    }

    public function getTableName(): string
    {
        return 'auth_access_tokens';
    }
}
