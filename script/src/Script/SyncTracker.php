<?php

namespace src\Script;

use src\Script;
use src\System\Config;
use src\System\Key;
use src\System\Tracker;
use src\Util\Logger;
use src\Util\RestCall;

class SyncTracker extends Script
{
    private $rest;

    private $nodes = [];
    private $collected_nodes = [];

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _process()
    {
        $validators = Tracker::GetAdmittedValidator();
        $supervisors = Tracker::GetSupervisor();
        $arbiters = Tracker::GetArbiter();

        $this->nodes = array_merge($validators, $supervisors, $arbiters);

        $this->RefreshHost();

        $try_count = 0;
        $max_count = 100;

        while (count($this->nodes) > 0) {
            $this->GetTracker();
            $this->MergeTracker();

            $try_count = $try_count + 1;

            if ($try_count >= $max_count) {
                break;
            }
        }

        $this->GetObservedStatus();
    }

    public function GetTracker()
    {
        $this->collected_nodes = [];

        $request = [
            'type' => 'GetTracker',
            'version' => Config::$version,
            'from' => Config::$node_address,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request));
        $public_key = Config::$node_public_key;
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $ssl = false;
        $data = [
            'request' => json_encode($request),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        foreach ($this->nodes as $node) {
            $host = $node['host'];
            $url = "http://{$host}/request";
            $result = $this->rest->POST($url, $data, $ssl, $header);
            $result = json_decode($result, true);

            if (!isset($result['data'])) {
                continue;
            }

            foreach ($result['data'] as $item) {
                $this->collected_nodes[] = $item;
            }
        }
    }

    public function GetObservedStatus()
    {
        $validators = Tracker::GetAdmittedValidator();
        $supervisors = Tracker::GetSupervisor();
        $arbiters = Tracker::GetArbiter();

        $nodes = array_merge($validators, $supervisors, $arbiters);
        $request = [
            'type' => 'GetTracker',
            'version' => Config::$version,
            'from' => Config::$node_address,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request));
        $public_key = Config::$node_public_key;
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $ssl = false;
        $data = [
            'request' => json_encode($request),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        foreach ($nodes as $node) {
            $host = $node['host'];
            $url = "http://{$host}/request";
            $result = $this->rest->POST($url, $data, $ssl, $header);
            $result = json_decode($result, true);

            if (!isset($result['data'])) {
                continue;
            }

            foreach ($result['data'] as $item) {
                if (isset($item['address']) && $item['address'] === Config::$node_address) {
                    if (isset($item['status'])) {
                        Tracker::SetObservedStatus($host, $item['status']);
                    }
                }
            }
        }
    }

    public function MergeTracker()
    {
        $nodes = Tracker::GetNode([]);
        $address_with_host = [];
        $address_without_host = [];

        $new_nodes = [];

        foreach ($nodes as $node) {
            if (empty($node['address'])) {
                continue;
            }

            if (!empty($node['host'])) {
                $address_with_host[] = $node['address'];
            } else {
                $address_without_host[] = $node['address'];
            }
        }

        foreach ($this->collected_nodes as $collected_node) {
            if (in_array($collected_node['address'], $address_with_host)) {
                continue;
            }

            if (in_array($collected_node['address'], $address_without_host)) {
                if (!empty($collected_node['host'])) {
                    if ($this->ValidTracker($collected_node['host'], $collected_node['address'])) {
                        Tracker::SetHost($collected_node['address'], $collected_node['host']);
                        $new_nodes[] = $collected_node;
                    }
                }

                continue;
            }

            switch ($collected_node['rank']) {
                case 'validator':
                    Tracker::SetValidator($collected_node['address']);

                    break;
                case 'supervisor':
                    Tracker::SetSupervisor($collected_node['address']);

                    break;
                case 'arbiter':
                    Tracker::SetArbiter($collected_node['address']);

                    break;
                default:
                    Tracker::SetLightNode($collected_node['address']);

                    break;
            }

            if (!empty($collected_node['host'])) {
                if ($this->ValidTracker($collected_node['host'], $collected_node['address'])) {
                    Tracker::SetHost($collected_node['address'], $collected_node['host']);
                    $new_nodes[] = $collected_node;
                }
            }
        }

        $this->nodes = $new_nodes;
    }

    public function RefreshHost()
    {
        foreach ($this->nodes as $node) {
            $host = $node['host'];
            $address = $node['address'];

            if (!$this->ValidTracker($host, $address)) {
                Tracker::SetHost($address, '');
            }
        }
    }

    public function ValidTracker($host, $tracker_address)
    {
        $string = bin2hex(random_bytes(16));
        $url = "http://{$host}/vrequest/getsign";

        $data = [
            'string' => $string,
        ];

        $rs = $this->rest->POST($url, $data);
        $rs = json_decode($rs, true);

        if (!isset($rs['data'])) {
            return false;
        }

        $data = $rs['data'];

        if (!isset($data['string'])) {
            return false;
        }

        if (!isset($data['public_key'])) {
            return false;
        }

        if (!isset($data['address'])) {
            return false;
        }

        if (!isset($data['signature'])) {
            return false;
        }

        $string = $data['string'];
        $public_key = $data['public_key'];
        $address = $data['address'];
        $signature = $data['signature'];

        $valid_signature = Key::ValidSignature($string, $public_key, $signature);
        $valid_address = (Key::MakeAddress($public_key) === $address && Key::MakeAddress($public_key) === $tracker_address);

        if (!$valid_signature || !$valid_address) {
            return false;
        }

        return true;
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
