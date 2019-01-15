<?php

namespace Sidraw\GoogleAnalytics\App;

abstract class AbstractSet
{
    protected static $instance = [];

    private function __construct(){}

    public static function getInstance() {
        $class = get_called_class();

        if ( ! isset(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }

        return self::$instance[$class];
    }

    abstract public function getCollection(): \Google_Model;
    abstract public function set(array $expressions);
    abstract public function get(): array;
}