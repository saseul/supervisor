<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;

class Token extends Status
{
    protected static $namespace = 'saseul_committed.token';

    protected static $addresses = [];
    protected static $token_names = [];
    protected static $balances = [];

    public static function LoadToken($address, $token_name)
    {
        self::$addresses[] = $address;
        self::$token_names[] = $token_name;
    }

    public static function GetBalance($address, $token_name)
    {
        if (isset(self::$balances[$address][$token_name])) {
            return self::$balances[$address][$token_name];
        }

        return 0;
    }

    public static function SetBalance($address, $token_name, int $value)
    {
        self::$balances[$address][$token_name] = (int) $value;
    }

    public static function _Reset()
    {
        self::$addresses = [];
        self::$token_names = [];
        self::$balances = [];
    }

    public static function _Load()
    {
        self::$addresses = array_values(array_unique(self::$addresses));
        self::$token_names = array_values(array_unique(self::$token_names));

        if (count(self::$addresses) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['address' => ['$in' => self::$addresses], 'token_name' => ['$in' => self::$token_names]];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                self::$balances[$item->address][$item->token_name] = $item->balance;
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$balances as $address => $item) {
            foreach ($item as $token_name => $balance) {
                $filter = ['address' => $address, 'token_name' => $token_name];
                $row = [
                    '$set' => [
                        'balance' => $balance,
                    ],
                ];
                $opt = ['upsert' => true];
                $db->bulk->update($filter, $row, $opt);
            }
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(self::$namespace);
        }

        self::_Reset();
    }
}
