<?php

namespace BlockeraAI\SiteToolkit\Commands;

class Database
{
    public function migrate(array $migrations): void
    {
        \WP_CLI::add_command('migrate', function () use ($migrations) {
            foreach ($migrations as $migration) {
                $migration->up();
            }
        });

        \WP_CLI::add_command('migrate:reset', function () use ($migrations) {
            foreach ($migrations as $migration) {
                $migration->down();
            }
        });
    }

    public function fresh(array $migrations): void
    {
        \WP_CLI::add_command('migrate:fresh', function () use ($migrations) {
            foreach ($migrations as $migration) {
                $migration->fresh();
            }
        });
    }
}
