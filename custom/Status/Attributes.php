<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;

class Attributes extends Status
{
    protected static $namespace = 'saseul_committed.attributes';

    protected static $addresses_role = [];
    protected static $roles = [];

    public static function LoadRole($address)
    {
        self::$addresses_role[] = $address;
    }

    public static function GetRole($address)
    {
        if (isset(self::$roles[$address])) {
            return self::$roles[$address];
        }

        return 'light';
    }

    public static function SetRole($address, string $value)
    {
        self::$roles[$address] = $value;
    }

    public static function _Reset()
    {
        self::$addresses_role = [];
        self::$roles = [];
    }

    public static function _Load()
    {
        self::$addresses_role = array_values(array_unique(self::$addresses_role));

        if (count(self::$addresses_role) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['address' => ['$in' => self::$addresses_role], 'key' => 'role'];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->value)) {
                self::$roles[$item->address] = $item->value;
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$roles as $k => $v) {
            $filter = ['address' => $k, 'key' => 'role'];
            $row = [
                '$set' => [
                    'key' => 'role',
                    'value' => $v,
                ],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(self::$namespace);
        }

        self::_Reset();
    }
}
