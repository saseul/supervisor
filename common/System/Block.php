<?php

namespace src\System;

use src\Util\Parser;

class Block
{
    public static function GetCount()
    {
        $db = Database::GetInstance();

        $command = [
            'count' => 'blocks',
            'query' => [],
        ];

        $rs = $db->Command('saseul_committed', $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        return $count;
    }

    public static function GetLastBlock()
    {
        $db = Database::GetInstance();

        // template
        $ret = [
            'block_number' => 0,
            'last_blockhash' => '',
            'blockhash' => '',
            'transaction_count' => 0,
            's_timestamp' => 0,
            'timestamp' => 0,
        ];

        $namespace = 'saseul_committed.blocks';
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];
        $rs = $db->Query($namespace, $query, $opt);

        foreach ($rs as $item) {
            $item = Parser::obj2array($item);

            if (isset($item['block_number'])) {
                $ret['block_number'] = $item['block_number'];
            }
            if (isset($item['last_blockhash'])) {
                $ret['last_blockhash'] = $item['last_blockhash'];
            }
            if (isset($item['blockhash'])) {
                $ret['blockhash'] = $item['blockhash'];
            }
            if (isset($item['transaction_count'])) {
                $ret['transaction_count'] = $item['transaction_count'];
            }
            if (isset($item['s_timestamp'])) {
                $ret['s_timestamp'] = $item['s_timestamp'];
            }
            if (isset($item['timestamp'])) {
                $ret['timestamp'] = $item['timestamp'];
            }

            break;
        }

        return $ret;
    }

    public static function GetBlocks($block_number = 1, $max_count = 100)
    {
        $namespace = 'saseul_committed.blocks';
        $query = ['block_number' => ['$gte' => $block_number]];
        $opt = ['sort' => ['timestamp' => 1]];

        return self::GetDatas($namespace, $max_count, $query, $opt);
    }

    public static function GetLastBlocks($max_count = 100)
    {
        $namespace = 'saseul_committed.blocks';
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        return self::GetDatas($namespace, $max_count, $query, $opt);
    }

    public static function GetLastTransactions($max_count = 100, $address = '')
    {
        $namespace = 'saseul_committed.transactions';
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        if ($address !== '') {
            $query = [
                '$or' => [
                    ['from' => $address],
                    ['to' => $address]
                ]
            ];
        }

        return self::GetDatas($namespace, $max_count, $query, $opt);
    }

    public static function GetDatas($namespace, $max_count, $query = [], $opt = [])
    {
        $db = Database::GetInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $data = Parser::obj2array($item);
            unset($data['_id']);
            $datas[] = $data;

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }
}
