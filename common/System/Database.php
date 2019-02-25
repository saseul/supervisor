<?php

namespace src\System;

use src\Util\MongoDB;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 */
class Database extends MongoDB
{
    protected static $instance = null;

    /**
     * Initialize the DB.
     */
    public function Init()
    {
        $this->db_host = Config::$database_mongodb_host;
    }

    /**
     * Return the singleton Database Instance.
     *
     * @return Database The singleton Databse instance.
     */
    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
