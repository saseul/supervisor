<?php

namespace src\Script;

use src\Script;
use src\System\Config;
use src\System\Database;
use src\System\Tracker;
use src\Util\RestCall;

class ResetTracker extends Script
{
    private $rest;
    private $db;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
        $this->db = Database::GetInstance();
    }

    public function _process()
    {
        $this->db->bulk->delete([]);

        $this->db->BulkWrite(Config::$database_mongodb_name_tracker . '.tracker');

        $this->db->bulk->insert([
            'host' => Config::$genesis_host,
            'address' => Config::$genesis_address,
            'rank' => 'validator',
            'status' => 'admitted',
        ]);

        $this->db->BulkWrite(Config::$database_mongodb_name_tracker . '.tracker');
    }

    public function _end()
    {
        $nodes = Tracker::GetFullNode();

        echo PHP_EOL;

        foreach ($nodes as $node) {
            echo $node['address'] . PHP_EOL;
            echo ' - host : ' . $node['host'] . PHP_EOL;
            echo ' - rank : ' . $node['rank'] . PHP_EOL;
            echo ' - status : ' . $node['status'] . PHP_EOL;
            echo ' - my observed status : ' . $node['my_observed_status'] . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
