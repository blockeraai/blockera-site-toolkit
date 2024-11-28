<?php

namespace BlockeraAI\SiteToolkit\Database\Contracts;

interface Command
{
    /**
     * Executing the command.
     *
     * @return void
     */
    public function execute(): void;

    /**
     * Undoing the command.
     *
     * @return void
     */
    public function undo(): void;
}
