<?php

namespace src\Script;

use src\Script;
use src\System\Cache;
use src\System\Config;
use src\System\Database;
use src\Util\Logger;

class Reset extends Script
{
    private $db;
    private $cache;
    private $patch_contract;
    private $patch_exchange;
    private $patch_token;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->cache = Cache::GetInstance();
        $this->patch_contract = new Script\Patch\Contract();
        $this->patch_exchange = new Script\Patch\Exchange();
        $this->patch_token = new Script\Patch\Token();
    }

    public function _process()
    {
        Logger::EchoLog('Reset? [y/n] ');
        $stdin = trim(fgets(STDIN));

        if ($stdin !== 'y') {
            return;
        }

        $this->DeleteFiles();
        $this->FlushCache();
        $this->DropDatabase();
        $this->CreateDatabase();
        $this->CreateIndex();
        $this->CreateGenesisTracker();
        $this->patch_contract->Exec();
        $this->patch_exchange->Exec();
        $this->patch_token->Exec();

        Logger::EchoLog('Success');
    }

    public function DeleteFiles()
    {
        Logger::EchoLog('Delete Files : API Chunk ');
        shell_exec('rm -rf ' . Config::$directory_apichunks);
        shell_exec('mkdir ' . Config::$directory_apichunks);
        shell_exec('chmod g+w ' . Config::$directory_apichunks);

        Logger::EchoLog('Delete Files : Broadcast Chunk ');
        shell_exec('rm -rf ' . Config::$directory_broadcastchunks);
        shell_exec('mkdir ' . Config::$directory_broadcastchunks);
        shell_exec('chmod g+w ' . Config::$directory_broadcastchunks);

        Logger::EchoLog('Delete Files : Transaction Chunk ');
        shell_exec('rm -rf ' . Config::$directory_transactions);
        shell_exec('mkdir ' . Config::$directory_transactions);
        shell_exec('chmod g+w ' . Config::$directory_transactions);
    }

    public function FlushCache()
    {
        $this->cache->flush();
    }

    public function DropDatabase()
    {
        Logger::EchoLog('Drop Database');

        $this->db->Command(Config::$database_mongodb_name_precommit, ['dropDatabase' => 1]);
        $this->db->Command(Config::$database_mongodb_name_committed, ['dropDatabase' => 1]);
        $this->db->Command(Config::$database_mongodb_name_tracker, ['dropDatabase' => 1]);
    }

    public function CreateDatabase()
    {
        Logger::EchoLog('Create Database');

        $this->db->Command(Config::$database_mongodb_name_precommit, ['create' => 'transactions']);
        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'transactions']);
        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'blocks']);

        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'coin']);
        $this->db->Command(Config::$database_mongodb_name_committed, ['create' => 'attributes']);

        $this->db->Command(Config::$database_mongodb_name_tracker, ['create' => 'tracker']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(Config::$database_mongodb_name_precommit, [
            'createIndexes' => 'transactions',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'thash' => 1], 'name' => 'timestamp_thash_asc'],
                ['key' => ['thash' => 1, 'timestamp' => 1], 'name' => 'thash_timestamp_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'transactions',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'thash' => 1], 'name' => 'timestamp_thash_asc'],
                ['key' => ['thash' => 1, 'timestamp' => 1], 'name' => 'thash_timestamp_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'blocks',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['block_number' => 1], 'name' => 'block_number_asc'],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'coin',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_committed, [
            'createIndexes' => 'attributes',
            'indexes' => [
                ['key' => ['address' => 1, 'key' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(Config::$database_mongodb_name_tracker, [
            'createIndexes' => 'tracker',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);
    }

    public function CreateGenesisTracker()
    {
        Logger::EchoLog('CreateGenesisTracker');

        $this->db->bulk->insert([
            'host' => Config::$genesis_host,
            'address' => Config::$genesis_address,
            'rank' => 'validator',
            'status' => 'admitted',
        ]);

        $this->db->BulkWrite(Config::$database_mongodb_name_tracker . '.tracker');
    }
}
