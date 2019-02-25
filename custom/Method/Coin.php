<?php

namespace src\Method;

use src\System\Database;

/**
 * Class Coin provides functions related to the coin used by the API.
 */
class Coin
{
    /**
     * Returns account information for multiple accounts.
     *
     * The account information currently returned are balance and deposit.
     *
     * @param array $addresses Addresses that look up account information.
     *
     * @return array Account information for balance and deposit held by each account.
     */
    public static function GetAll($addresses)
    {
        $db = Database::GetInstance();

        $all = [];

        foreach ($addresses as $address) {
            $all[$address] = [
                'balance' => 0,
                'deposit' => 0,
            ];
        }

        $filter = ['address' => ['$in' => $addresses]];
        $rs = $db->Query('saseul_committed.coin', $filter);

        foreach ($rs as $item) {
            if (isset($item->address, $item->balance)) {
                $all[$item->address]['balance'] = (int) $item->balance;
            }

            if (isset($item->address, $item->deposit)) {
                $all[$item->address]['deposit'] = (int) $item->deposit;
            }
        }

        return $all;
    }

    public static function SetAll($all)
    {
        $db = Database::GetInstance();

        foreach ($all as $address => $item) {
            $filter = ['address' => $address];
            $row = [
                '$set' => [
                    'balance' => $item['balance'],
                    'deposit' => $item['deposit'],
                ],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite('saseul_committed.coin');
        }
    }
}
