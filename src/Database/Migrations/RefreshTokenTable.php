<?php

namespace BlockeraAI\SiteToolkit\Database\Migrations;

use BlockeraAI\SiteToolkit\Database\Traits\CommandTrait;
use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

class RefreshTokenTable implements Migration
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
    refresh_token VARCHAR(255) NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY refresh_token (refresh_token),
    INDEX access_token_idx (access_token),
    FOREIGN KEY (access_token) REFERENCES ' . $wpdb->prefix . 'auth_access_tokens(access_token) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        if (class_exists(\WP_CLI::class)) {
            if (false === $result) {
                $error = $wpdb->last_error;
                \WP_CLI::success("Failed to create table: " . $error);
            } else {
                \WP_CLI::success("The {$this->getTableName()} table has been created ✅");
            }
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
        return 'auth_refresh_tokens';
    }
}
