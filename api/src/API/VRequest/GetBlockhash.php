<?php

namespace src\API\VRequest;

use src\API;
use src\System\Cache;
use src\System\Config;

class GetBlockhash extends API
{
    private $cache;

    private $p_key;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
    }

    public function _init()
    {
        if (!isset($_REQUEST['key'])) {
            $this->Error('There is no parameter : key ');
        }

        $this->p_key = $_REQUEST['key'];
    }

    public function _process()
    {
        $broadcast = $this->cache->get($this->p_key . 'blockhash');
        $address = Config::$node_address;

        if ($broadcast == false) {
            $this->data = [];

            return;
        }

        if (!isset($broadcast[$address])) {
            $this->data = [];

            return;
        }

        $this->data[$address] = $broadcast[$address];
    }
}
