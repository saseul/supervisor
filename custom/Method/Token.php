<?php

namespace src\Method;

use src\System\Database;

class Token
{
    public static function GetAll($addresses, $token_names = null)
    {
        $db = Database::GetInstance();

        $all = [];

        foreach ($addresses as $address) {
            $all[$address] = [];
        }

        $filter = ['address' => ['$in' => $addresses]];

        if ($token_names !== null) {
            $filter = [
                'address' => ['$in' => $addresses],
                'token_name' => ['$in' => $token_names],
            ];
        }

        $rs = $db->Query('saseul_committed.token', $filter);

        foreach ($rs as $item) {
            $all[$item->address][$item->token_name] = (int) $item->balance;
        }

        return $all;
    }

    public static function SetAll($all)
    {
        $db = Database::GetInstance();

        foreach ($all as $address => $item) {
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
            $db->BulkWrite('saseul_committed.token');
        }
    }
}
