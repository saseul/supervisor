<?php

namespace src\Method;

use src\System\Database;

class Attributes
{
    private static $namespace_attributes = 'saseul_committed.attributes';
    private static $collection_attributes = 'attributes';
    private static $db_attributes = 'saseul_committed';

    public static function GetRole($address)
    {
        $db = Database::GetInstance();
        $query = ['address' => $address, 'key' => 'role'];
        $rs = $db->Query(self::$namespace_attributes, $query);
        $node = [
            'address' => $address,
            'role' => 'light'
        ];

        foreach ($rs as $item) {
            if (isset($item->address)) {
                $node['role'] = $item->value;
            }
        }

        return $node;
    }

    public static function GetFullNode($query = ['key' => 'role', 'value' => ['$in' => ['supervisor', 'validator', 'arbiter']]])
    {
        $db = Database::GetInstance();
        $rs = $db->Query(self::$namespace_attributes, $query);
        $nodes = [];

        foreach ($rs as $item) {
            if (isset($item->address)) {
                $nodes[] = $item->address;
            }
        }

        return $nodes;
    }

    public static function IsFullNode($address, $query = ['key' => 'role', 'value' => ['$in' => ['supervisor', 'validator', 'arbiter']]])
    {
        $db = Database::GetInstance();
        $query = array_merge(['address' => $address], $query);
        $command = [
            'count' => self::$collection_attributes,
            'query' => $query,
        ];

        $rs = $db->Command(self::$db_attributes, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        if ($count > 0) {
            return true;
        }

        return false;
    }

    public static function GetValidator()
    {
        return self::GetFullNode(['key' => 'role', 'value' => 'validator']);
    }

    public static function GetSupervisor()
    {
        return self::GetFullNode(['key' => 'role', 'value' => 'supervisor']);
    }

    public static function GetArbiter()
    {
        return self::GetFullNode(['key' => 'role', 'value' => 'arbiter']);
    }

    public static function IsValidator($address)
    {
        return self::IsFullNode($address, ['key' => 'role', 'value' => 'validator']);
    }

    public static function IsSupervisor($address)
    {
        return self::IsFullNode($address, ['key' => 'role', 'value' => 'supervisor']);
    }

    public static function IsArbiter($address)
    {
        return self::IsFullNode($address, ['key' => 'role', 'value' => 'arbiter']);
    }
}
