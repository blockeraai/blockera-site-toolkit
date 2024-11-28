<?php

namespace BlockeraAI\SiteToolkit\Core\Traits;

trait Singleton
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
