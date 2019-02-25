<?php

namespace src\Script;

use src\Script;
use src\System\Config;
use src\Util\Logger;

class MyAddress extends Script
{
    public function _process()
    {
        echo PHP_EOL;
        Logger::EchoLog('ip : ' . Config::$node_host);
        Logger::EchoLog('address : ' . Config::$node_address);
        Logger::EchoLog('public_key : ' . Config::$node_public_key);
        Logger::EchoLog('private_key : ' . Config::$node_private_key);
        echo PHP_EOL;
    }
}
