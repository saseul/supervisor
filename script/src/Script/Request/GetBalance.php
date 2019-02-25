<?php

namespace src\Script\Request;

use src\Script;
use src\System\Config;
use src\System\Key;
use src\System\Tracker;
use src\Util\Logger;
use src\Util\RestCall;

class GetBalance extends Script
{
    private $rest;

    private $m_result;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _process()
    {
        $validator = Tracker::GetRandomValidator();
        $host = $validator['host'];
        $address = Config::$node_address;

        $request = [
            'type' => 'GetBalance',
            'version' => Config::$version,
            'from' => $address,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request));
        $public_key = Config::$node_public_key;
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $url = "http://{$host}/request";
        $ssl = false;
        $data = [
            'request' => json_encode($request),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        $result = $this->rest->POST($url, $data, $ssl, $header);
        $result = json_decode($result, true);

        $data = $result['data'];

        echo PHP_EOL;
        echo '[Request Info]' . PHP_EOL;
        echo 'Validator address : ' . $validator['address'] . PHP_EOL;
        echo 'Validator host : ' . $validator['host'] . PHP_EOL;
        echo PHP_EOL;
        echo '[Balance Info]' . PHP_EOL;
        echo 'My address : ' . $address . PHP_EOL;
        echo 'Balance : ' . $data['balance'] . PHP_EOL;
        echo 'Deposit : ' . $data['deposit'] . PHP_EOL;
        echo PHP_EOL;
    }
}
