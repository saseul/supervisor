<?php

namespace src\Script;

use src\Script;
use src\System\Tracker;
use src\Util\Logger;

class Admit extends Script
{
    public function _process()
    {
        echo PHP_EOL;

        Logger::EchoLog('Type address to admit. (0x6f...) ');
        $address = trim(fgets(STDIN));

        Tracker::Admit($address);
        $nodes = Tracker::GetFullNode();

        foreach ($nodes as $node) {
            echo $node['address'] . PHP_EOL;
            echo ' - host : ' . $node['host'] . PHP_EOL;
            echo ' - rank : ' . $node['rank'] . PHP_EOL;
            echo ' - status : ' . $node['status'] . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
