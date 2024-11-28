<?php

namespace BlockeraAI\SiteToolkit\Database\Contracts;

interface Migration
{
    /**
     * Up method.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Down method.
     *
     * @return void
     */
    public function down(): void;

    /**
     * Retrieve the database table name.
     *
     * @return string
     */
    public function getTableName(): string;

    /**
     * Fresh method.
     *
     * @return void
     */
    public function fresh(): void;
}
