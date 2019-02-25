<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;
use src\Util\Parser;

class TokenList extends Status
{
    protected static $namespace = 'saseul_committed.token_list';

    protected static $token_names = [];
    protected static $token_info = [];

    public static function LoadTokenList($token_name)
    {
        self::$token_names[] = $token_name;
    }

    public static function GetInfo($token_name)
    {
        if (isset(self::$token_info[$token_name])) {
            return self::$token_info[$token_name];
        }

        return [];
    }

    public static function SetInfo($token_name, $info)
    {
        self::$token_info[$token_name] = $info;
    }

    public static function _Reset()
    {
        self::$token_names = [];
        self::$token_info = [];
    }

    public static function _Load()
    {
        self::$token_names = array_values(array_unique(self::$token_names));

        if (count(self::$token_names) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['token_name' => ['$in' => self::$token_names]];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->info)) {
                self::$token_info[$item->token_name] = Parser::obj2array($item->info);
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$token_info as $token_name => $info) {
            $filter = ['token_name' => $token_name];
            $row = [
                '$set' => [
                    'info' => $info,
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
