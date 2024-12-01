<?php

namespace BlockeraAI\SiteToolkit\Database\Migrations;

use BlockeraAI\SiteToolkit\Database\Traits\CommandTrait;
use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

class ClientsTable implements Migration
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
    user_id BIGINT UNSIGNED NOT NULL,
    client_id VARCHAR(80) NOT NULL,
    client_secret VARCHAR(80) NOT NULL,
    auth_code TEXT,
    redirect_uri TEXT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    grant_type TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY domain (domain),
    UNIQUE KEY client_id (client_id),
    FOREIGN KEY (user_id) REFERENCES ' . $wpdb->prefix . 'users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        if ($result === false && class_exists(\WP_CLI::class)) {
            $error = $wpdb->last_error;
            \WP_CLI::success("Failed to create table: " . $error);
            // throw new \Exception("Failed to create table: " . $error);
        } elseif (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("The {$this->getTableName()} table has been created ✅");
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

        $result = $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . $this->getTableName());

        if ($result === false && class_exists(\WP_CLI::class)) {
            $error = $wpdb->last_error;
            \WP_CLI::success("Failed to drop table: " . $error);
        } elseif (class_exists(\WP_CLI::class)) {
            \WP_CLI::success("The {$this->getTableName()} table has been dropped ✅");
        }
    }

    public function getTableName(): string
    {
        return 'auth_clients';
    }
}
