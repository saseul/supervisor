<?php

namespace src\System;

use src\Util\Memcached;

class Cache extends Memcached
{
    protected static $instance = null;

    public function initialize()
    {
        $this->prefix = '';
        $this->host = 'localhost';
        $this->port = 11211;
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
