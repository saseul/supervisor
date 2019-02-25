<?php

namespace src\API\VRequest;

use src\API;
use src\System\Cache;
use src\System\Config;

class GetRound extends API
{
    private $cache;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
    }

    public function _process()
    {
        $my_address = Config::$node_address;
        $rounds = $this->cache->get('rounds');

        if ($rounds == false) {
            $this->data = [];

            return;
        }

        if (!isset($rounds[$my_address])) {
            $this->data = [];

            return;
        }

        $this->data = $rounds[$my_address];
    }
}
