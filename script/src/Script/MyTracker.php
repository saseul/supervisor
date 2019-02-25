<?php

namespace src\Script;

use src\Script;
use src\System\Tracker;

class MyTracker extends Script
{
    public function _process()
    {
        echo PHP_EOL;

        $nodes = Tracker::GetFullNode();

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
