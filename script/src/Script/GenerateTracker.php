<?php

namespace src\Script;

use src\Method\Attributes;
use src\Script;
use src\System\Config;
use src\System\Tracker;
use src\Util\RestCall;

class GenerateTracker extends Script
{
    private $rest;

    public function __construct()
    {
        $this->rest = new RestCall();
    }

    public function _process()
    {
        $this->GenerateTracker();
    }

    public function GenerateTracker()
    {
        $validators = Attributes::GetValidator();
        $supervisors = Attributes::GetSupervisor();

        $validators_in_tracker = Tracker::GetValidatorAddress();
        $supervisors_in_tracker = Tracker::GetValidatorAddress();
        $fullnodes = Tracker::GetFullNodeAddress();

        foreach ($validators as $validator) {
            if (!in_array($validator, $validators_in_tracker)) {
                Tracker::SetValidator($validator);

                if ($validator === Config::$node_address) {
                    Tracker::SetHost($validator, Config::$node_host);
                }
            }
        }

        foreach ($supervisors as $supervisor) {
            if (!in_array($supervisor, $supervisors_in_tracker)) {
                Tracker::SetSupervisor($supervisor);
            }
        }

        foreach ($fullnodes as $fullnode) {
            if (!in_array($fullnode, $validators) && !in_array($fullnode, $validators)) {
                Tracker::SetLightNode($fullnode['address']);
            }
        }
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
            echo PHP_EOL;
        }
    }
}
