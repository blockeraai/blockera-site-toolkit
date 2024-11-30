<?php

namespace BlockeraAI\SiteToolkit\Database;

use BlockeraAI\SiteToolkit\Database\Contracts\Migration;

class Migrations implements Contracts\Command
{
    /**
     * Migrations list.
     *
     * @var Migration[]
     */
    protected $migrations = [];

    /**
     * Migrations constructor.
     *
     * @param array $migrations
     */
    public function __construct(array $migrations)
    {
        // Set migrations.
        $this->migrations = $migrations;
    }

    /**
     * Executing the Migrations command.
     *
     * @return void
     */
    public function execute(): void
    {
        array_map(static function (Migration $migration): void {
            $migration->up();
        }, $this->migrations);
    }

    /**
     * Undoing the Migrations command.
     *
     * @return void
     */
    public function undo(): void
    {
        array_map(static function (Migration $migration): void {
            $migration->down();
        }, array_reverse($this->migrations));
    }
}
