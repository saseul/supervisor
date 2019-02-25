<?php

namespace src\Script\Patch;

use src\Script;
use src\System\Config;
use src\System\Database;
use src\Util\Logger;

class Contract extends Script
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

        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'contract']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'contract',
            'indexes' => [
                ['key' => ['cid' => 1], 'name' => 'cid_asc'],
                ['key' => ['chash' => 1], 'name' => 'chash_asc'],
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'chash' => 1], 'name' => 'timestamp_chash_asc'],
            ]
        ]);
    }
}
