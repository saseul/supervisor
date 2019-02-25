<?php

namespace src\Script;

use src\Script;
use src\System\Cache;
use src\System\Config;
use src\System\Database;
use src\System\Key;
use src\Util\Logger;

class Genesis extends Script
{
    private $db;
    private $cache;

    private $m_stdin;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->cache = Cache::GetInstance();
    }

    public function _process()
    {
        $this->CheckYes();

        $this->CheckGenesis();
        $this->CreateKey();
        $this->CreateGenesisTransaction();

        Logger::EchoLog('Success ');
    }

    public function CheckYes()
    {
        Logger::EchoLog('Genesis? [y/n] ');
        $this->m_stdin = trim(fgets(STDIN));

        if ($this->m_stdin !== 'y') {
            exit();
        }
    }

    public function AskFirstMessage()
    {
        Logger::EchoLog('Please write first message : ');
        $this->m_stdin = trim(fgets(STDIN));
    }

    public function CheckGenesis()
    {
        Logger::EchoLog('CheckGenesis');
        $v = $this->cache->get('CheckGenesis');

        if ($v === false) {
            $this->cache->set('CheckGenesis', 'inProcess', 15);
        } else {
            $this->Error('There is genesis block already ');
        }

        $rs = $this->db->Command(Config::$database_mongodb_name_committed, ['count' => 'blocks']);

        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        if ($count > 0) {
            $this->Error('There is genesis block already ');
        }
    }

    public function CreateKey()
    {
        Logger::EchoLog('CreateKey');

        $this->data['node_key'] = [
            'private_key' => Config::$node_private_key,
            'public_key' => Config::$node_public_key,
            'address' => Config::$node_address,
        ];
    }

    public function CreateGenesisTransaction()
    {
        Logger::EchoLog('CreateGenesisTransaction');
        $transaction_genesis = [
            'version' => Config::$version,
            'type' => 'Genesis',
            'from' => Config::$node_address,
            'amount' => Config::$genesis_coin_value,
            'transactional_data' => Config::$genesis_key,
            'timestamp' => Logger::Microtime(),
        ];

        $transaction_deposit = [
            'version' => Config::$version,
            'type' => 'Deposit',
            'from' => Config::$node_address,
            'amount' => Config::$genesis_deposit_value,
            'fee' => 0,
            'transactional_data' => 'Genesis Deposit',
            'timestamp' => Logger::Microtime(),
        ];

        $thash_genesis = hash('sha256', json_encode($transaction_genesis));
        $public_key_genesis = Config::$node_public_key;
        $signature_genesis = Key::MakeSignature($thash_genesis, Config::$node_private_key, Config::$node_public_key);

        $this->AddAPIChunk([
            'transaction' => $transaction_genesis,
            'public_key' => $public_key_genesis,
            'signature' => $signature_genesis,
        ], $transaction_genesis['timestamp']);

        $thash_deposit = hash('sha256', json_encode($transaction_deposit));
        $public_key_deposit = Config::$node_public_key;
        $signature_deposit = Key::MakeSignature($thash_deposit, Config::$node_private_key, Config::$node_public_key);

        $this->AddAPIChunk([
            'transaction' => $transaction_deposit,
            'public_key' => $public_key_deposit,
            'signature' => $signature_deposit,
        ], $transaction_deposit['timestamp']);
    }

    public function AddAPIChunk($transaction, $timestamp)
    {
        $filename = Config::$directory_apichunks . '/' . Config::$prefix_chunks . $this->GetID($timestamp) . '.json';

        $file = fopen($filename, 'a');
        fwrite($file, json_encode($transaction) . ",\n");
        fclose($file);
    }

    public function GetID($timestamp)
    {
        $tid = $timestamp - ($timestamp % Config::$microinterval_chunk) + Config::$microinterval_chunk;

        return preg_replace('/0{6}$/', '', $tid);
    }
}
