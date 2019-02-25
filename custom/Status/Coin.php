<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;

class Coin extends Status
{
    protected static $namespace = 'saseul_committed.coin';

    protected static $addresses = [];
    protected static $balances = [];
    protected static $deposits = [];

    public static function LoadBalance($address)
    {
        self::$addresses[] = $address;
    }

    public static function LoadDeposit($address)
    {
        self::$addresses[] = $address;
    }

    public static function GetBalance($address)
    {
        if (isset(self::$balances[$address])) {
            return self::$balances[$address];
        }

        return 0;
    }

    public static function GetDeposit($address)
    {
        if (isset(self::$deposits[$address])) {
            return self::$deposits[$address];
        }

        return 0;
    }

    public static function SetBalance($address, int $value)
    {
        self::$balances[$address] = $value;
    }

    public static function SetDeposit($address, int $value)
    {
        self::$deposits[$address] = $value;
    }

    public static function _Reset()
    {
        self::$addresses = [];
        self::$balances = [];
        self::$deposits = [];
    }

    public static function _Load()
    {
        self::$addresses = array_values(array_unique(self::$addresses));

        if (count(self::$addresses) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['address' => ['$in' => self::$addresses]];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                self::$balances[$item->address] = $item->balance;
            }

            if (isset($item->deposit)) {
                self::$deposits[$item->address] = $item->deposit;
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$balances as $k => $v) {
            $filter = ['address' => $k];
            $row = [
                '$set' => ['balance' => $v],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        foreach (self::$deposits as $k => $v) {
            $filter = ['address' => $k];
            $row = [
                '$set' => ['deposit' => $v],
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
