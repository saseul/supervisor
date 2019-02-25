<?php

namespace src\Script\Transaction;

use src\Script;
use src\System\Config;
use src\System\Key;
use src\System\Tracker;
use src\Util\Logger;
use src\Util\RestCall;

class Exchange extends Script
{
    private $rest;

    private $m_result;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _process()
    {
        Logger::EchoLog('Type from currency name. (coin or token name) ');
        $type = trim(fgets(STDIN));

        if ($type === 'coin') {
            $from_type = 'coin';
            $from_currency_name = 'coin';
        } else {
            $from_type = 'token';
            $from_currency_name = $type;
        }

        Logger::EchoLog('Type to currency name. (only token) ');
        $type = trim(fgets(STDIN));

        if ($type === 'coin') {
            Logger::EchoLog('Please type only token name. ');
            exit();
        }

        if ($type === $from_currency_name) {
            Logger::EchoLog('Exchange currency must be different. ');
            exit();
        }

        $to_type = 'token';
        $to_currency_name = $type;

        Logger::EchoLog('Buy? or sell? (buy | sell) ');
        $exchange_type = trim(fgets(STDIN));

        if (!in_array($exchange_type, ['buy', 'sell'])) {
            Logger::EchoLog('Exchange type must be buy or sell. ');
            exit();
        }

        Logger::EchoLog('Please enter the price to exchange. ');
        $exchange_rate = trim(fgets(STDIN));

        if (!is_numeric($exchange_rate)) {
            Logger::EchoLog('Exchange rate must be numeric. ');
            exit();
        }

        Logger::EchoLog('Please enter the amount to exchange. ');
        $to_amount = trim(fgets(STDIN));

        if (!is_numeric($to_amount)) {
            Logger::EchoLog('Amount must be numeric. ');
            exit();
        }

        $to_amount = (float) $to_amount;
        $from_amount = (float) $to_amount * $exchange_rate;

        $validator = Tracker::GetRandomValidator();
        $host = $validator['host'];

        $transaction = [
            'type' => 'Exchange',
            'version' => Config::$version,
            'from' => Config::$node_address,
            'from_type' => $from_type,
            'from_currency_name' => $from_currency_name,
            'from_amount' => $from_amount,
            'to_type' => $to_type,
            'to_currency_name' => $to_currency_name,
            'to_amount' => $to_amount,
            'exchange_rate' => (float) $exchange_rate,
            'exchange_type' => $exchange_type,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($transaction));
        $public_key = Config::$node_public_key;
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $url = "http://{$host}/transaction";
        $ssl = false;
        $data = [
            'transaction' => json_encode($transaction),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        $result = $this->rest->POST($url, $data, $ssl, $header);
        $this->m_result = json_decode($result, true);
    }

    public function _end()
    {
        $this->data['result'] = $this->m_result;
    }
}
