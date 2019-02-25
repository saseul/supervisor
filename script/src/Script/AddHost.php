<?php

namespace src\Script;

use src\Script;
use src\System\Config;
use src\System\Key;
use src\System\Tracker;
use src\Util\Logger;
use src\Util\RestCall;

class AddHost extends Script
{
    private $rest;
    private $sync_tracker;

    private $m_result;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
        $this->sync_tracker = new SyncTracker();
    }

    public function _process()
    {
        $fullnodes = Tracker::GetFullNode();

        $request = [
            'type' => 'AddTracker',
            'version' => Config::$version,
            'from' => Config::$node_address,
            'ip' => Config::$node_host,
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

        foreach ($fullnodes as $node) {
            if (empty($node['host'])) {
                continue;
            }

            $host = $node['host'];
            $url = "http://{$host}/request";
            $result = $this->rest->POST($url, $data, $ssl, $header);
            $this->m_result[] = json_decode($result, true);
        }
    }

    public function _end()
    {
        $this->sync_tracker->Exec();
    }
}
