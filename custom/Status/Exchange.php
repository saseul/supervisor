<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;
use src\Util\Parser;

class Exchange extends Status
{
    protected static $namespace_order = 'saseul_committed.exchange_order';
    protected static $namespace_result = 'saseul_committed.exchange_result';

    protected static $exchange_keys = [];
    protected static $eids = [];
    protected static $eid_order_keys = [];

    protected static $exchange_orders = [];
    protected static $exchange_result = [];

    protected static $delete_orders = [];

    public static function LoadOrder($from_type, $from_name, $to_type, $to_name)
    {
        self::$exchange_keys[] = json_encode([$from_type, $from_name, $to_type, $to_name]);
    }

    public static function LoadOrderByEID($eid)
    {
        self::$eids[] = $eid;
    }

    public static function GetOrder($from_type, $from_name, $to_type, $to_name, $eid = null)
    {
        $order_key = self::MakeOrderKey($from_type, $from_name, $to_type, $to_name);

        if ($eid === null) {
            if (isset(self::$exchange_orders[$order_key])) {
                return self::$exchange_orders[$order_key];
            }
        } else {
            if (isset(self::$exchange_orders[$order_key][$eid])) {
                return self::$exchange_orders[$order_key][$eid];
            }
        }

        return [];
    }

    public static function GetOrderByEID($eid)
    {
        $order_key = null;

        if (isset(self::$eid_order_keys[$eid])) {
            $order_key = self::$eid_order_keys[$eid];
        }

        if (isset(self::$exchange_orders[$order_key][$eid])) {
            return self::$exchange_orders[$order_key][$eid];
        }

        return [];
    }

    public static function SetOrder($from_type, $from_name, $to_type, $to_name, $eid, $item)
    {
        $order_key = self::MakeOrderKey($from_type, $from_name, $to_type, $to_name);

        self::$exchange_orders[$order_key][$eid] = $item;
    }

    public static function SetDeleteOrder($eid)
    {
        if (isset(self::$eid_order_keys[$eid])) {
            $order_key = self::$eid_order_keys[$eid];

            if (isset(self::$exchange_orders[$order_key][$eid])) {
                unset(self::$exchange_orders[$order_key][$eid]);
            }
        }

        self::$delete_orders[] = $eid;
    }

    public static function SetResult($result)
    {
        self::$exchange_result[] = $result;
    }

    public static function MakeOrderKey($from_type, $from_name, $to_type, $to_name)
    {
        return hash('ripemd160', json_encode([$from_type, $from_name, $to_type, $to_name]));
    }

    public static function _Reset()
    {
        self::$exchange_keys = [];
        self::$eids = [];
        self::$eid_order_keys = [];
        self::$exchange_orders = [];
        self::$delete_orders = [];
        self::$exchange_result = [];
    }

    public static function _Load()
    {
        self::$exchange_keys = array_values(array_unique(self::$exchange_keys));

        foreach (self::$exchange_keys as $item) {
            $exchange_key = json_decode($item, true);
            $order_key = self::MakeOrderKey($exchange_key[0], $exchange_key[1], $exchange_key[2], $exchange_key[3]);

            $db = Database::GetInstance();
            $filter = [
                'from_type' => $exchange_key[0],
                'from_currency_name' => $exchange_key[1],
                'to_type' => $exchange_key[2],
                'to_currency_name' => $exchange_key[3],
            ];
            $rs = $db->Query(self::$namespace_order, $filter);

            foreach ($rs as $order) {
                $order = Parser::obj2array($order);
                $eid = $order['eid'];
                unset($order['_id']);
                self::$exchange_orders[$order_key][$eid] = $order;
            }
        }

        foreach (self::$eids as $eid) {
            $db = Database::GetInstance();
            $filter = ['eid' => $eid];
            $rs = $db->Query(self::$namespace_order, $filter);
            $order_key = null;

            foreach ($rs as $order) {
                $order = Parser::obj2array($order);
                unset($order['_id']);
                $order_key = self::MakeOrderKey($order['from_type'], $order['from_currency_name'], $order['to_type'], $order['to_currency_name']);
                self::$exchange_orders[$order_key][$eid] = $order;
            }

            self::$eid_order_keys[$eid] = $order_key;
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$exchange_orders as $k => $item) {
            foreach ($item as $eid => $order) {
                $filter = ['eid' => $eid];
                $row = [
                    '$set' => $order,
                ];
                $opt = ['upsert' => true];
                $db->bulk->update($filter, $row, $opt);
            }
        }

        foreach (self::$delete_orders as $eid) {
            $filter = ['eid' => $eid];
            $db->bulk->delete($filter);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(self::$namespace_order);
        }
    }

    public static function _Postprocess()
    {
        $db = Database::GetInstance();

        $addresses = [];
        $token_names = [];

        foreach (self::$exchange_result as $result) {
            $addresses[] = $result['from'];
            $addresses[] = $result['to'];

            if ($result['from_type'] === 'token') {
                $token_names[] = $result['from_currency_name'];
            }

            if ($result['to_type'] === 'token') {
                $token_names[] = $result['to_currency_name'];
            }

            $db->bulk->insert($result);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(self::$namespace_result);
        }

        $addresses = array_values(array_unique($addresses));
        $token_names = array_values(array_unique($token_names));

        $coin = \src\Method\Coin::GetAll($addresses);
        $token = \src\Method\Token::GetAll($addresses, $token_names);

        foreach (self::$exchange_result as $result) {
            if ($result['exchange_type'] === 'buy') {
                if ($result['to_type'] === 'coin') {
                    if (!isset($coin[$result['from']]['balance'])) {
                        $coin[$result['from']]['balance'] = 0;
                    }

                    $coin[$result['from']]['balance'] = (float) $coin[$result['from']]['balance'] + (float) $result['to_amount'];
                }

                if ($result['to_type'] === 'token') {
                    if (!isset($token[$result['from']][$result['to_currency_name']])) {
                        $token[$result['from']][$result['to_currency_name']] = 0;
                    }

                    $token[$result['from']][$result['to_currency_name']] = (float) $token[$result['from']][$result['to_currency_name']] + (float) $result['to_amount'];
                }

                if ($result['from_type'] === 'coin') {
                    if (!isset($coin[$result['to']]['balance'])) {
                        $coin[$result['to']]['balance'] = 0;
                    }

                    $coin[$result['to']]['balance'] = (float) $coin[$result['to']]['balance'] + (float) $result['from_amount'];
                }

                if ($result['from_type'] === 'token') {
                    if (!isset($token[$result['to']][$result['from_currency_name']])) {
                        $token[$result['to']][$result['from_currency_name']] = 0;
                    }

                    $token[$result['to']][$result['from_currency_name']] = (float) $token[$result['to']][$result['from_currency_name']] + (float) $result['from_amount'];
                }
            }

            if ($result['exchange_type'] === 'sell') {
                if ($result['to_type'] === 'coin') {
                    if (!isset($coin[$result['to']]['balance'])) {
                        $coin[$result['to']]['balance'] = 0;
                    }

                    $coin[$result['to']]['balance'] = (float) $coin[$result['to']]['balance'] + (float) $result['to_amount'];
                }

                if ($result['to_type'] === 'token') {
                    if (!isset($token[$result['to']][$result['to_currency_name']])) {
                        $token[$result['to']][$result['to_currency_name']] = 0;
                    }

                    $token[$result['to']][$result['to_currency_name']] = (float) $token[$result['to']][$result['to_currency_name']] + (float) $result['to_amount'];
                }

                if ($result['from_type'] === 'coin') {
                    if (!isset($coin[$result['from']]['balance'])) {
                        $coin[$result['from']]['balance'] = 0;
                    }

                    $coin[$result['from']]['balance'] = (float) $coin[$result['from']]['balance'] + (float) $result['from_amount'];
                }

                if ($result['from_type'] === 'token') {
                    if (!isset($token[$result['from']][$result['from_currency_name']])) {
                        $token[$result['from']][$result['from_currency_name']] = 0;
                    }

                    $token[$result['from']][$result['from_currency_name']] = (float) $token[$result['from']][$result['from_currency_name']] + (float) $result['from_amount'];
                }
            }
        }

        \src\Method\Coin::SetAll($coin);
        \src\Method\Token::SetAll($token);

        self::_Reset();
    }
}
