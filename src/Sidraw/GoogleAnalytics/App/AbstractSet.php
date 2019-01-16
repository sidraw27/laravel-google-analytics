<?php

namespace Sidraw\GoogleAnalytics\App;

abstract class AbstractSet
{
    protected static $instance = [];

    protected $collection = [];

    private function __construct(){}

    public static function getInstance() {
        $class = get_called_class();

        if ( ! isset(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }

        return self::$instance[$class];
    }

    public function get(): array
    {
        $result = $this->collection;
        $this->collection = [];

        return $result;
    }

    abstract public function getCollection(): \Google_Model;
    abstract public function set(array $expressions);
}