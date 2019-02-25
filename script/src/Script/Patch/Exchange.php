<?php

namespace src\Script\Patch;

use src\Script;
use src\System\Config;
use src\System\Database;
use src\Util\Logger;

class Exchange extends Script
{
    private $db;

    public function __construct()
    {
        $this->db = Database::GetInstance();
    }

    public function _process()
    {
        $this->CreateDatabase();
        $this->CreateIndex();
    }

    public function CreateDatabase()
    {
        Logger::EchoLog('Create Database');

        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'exchange_order']);
        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'exchange_result']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'exchange_order',
            'indexes' => [
                ['key' => ['eid' => 1], 'name' => 'eid_asc', 'unique' => 1],
                ['key' => ['from_type' => 1, 'from_currency_name' => 1, 'to_type' => 1, 'to_currency_name' => 1], 'name' => 'type_asc'],
                ['key' => ['from' => 1], 'name' => 'from_asc'],
                ['key' => ['to' => 1], 'name' => 'to_asc'],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'exchange_result',
            'indexes' => [
                ['key' => ['from' => 1], 'name' => 'from_asc'],
                ['key' => ['to' => 1], 'name' => 'to_asc'],
            ]
        ]);
    }
}
