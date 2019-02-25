<?php

namespace src\Core;

use src\Status\Fee;
use src\System\Chunk;
use src\System\Config;
use src\System\Database;
use src\System\StatusManager;
use src\System\TransactionManager;
use src\Util\Logger;
use src\Util\Merkle;
use src\Util\Parser;

class CommitManager
{
    protected $transactions;
    protected $expect_blockinfo = [];
    protected $last_blockinfo = [];

    private static $instance = null;

    private $db;

    private $transaction_manager;
    private $status_manager;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->transaction_manager = new TransactionManager();
        $this->status_manager = new StatusManager();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function Initialize()
    {
        $this->expect_blockinfo = [];
        $this->last_blockinfo = [];

        $this->db->bulk->delete([]);
        $this->db->BulkWrite(Config::$database_mongodb_name_precommit . '.transactions');

        $this->status_manager->Reset();
    }

    public function Precommit($transaction_chunks, $min_timestamp, $max_timestamp)
    {
        foreach ($transaction_chunks as $transaction_chunk) {
            // Parse Transaction Chunk
            if (!isset($transaction_chunk['transaction']['type'])) {
                continue;
            }
            if (!isset($transaction_chunk['transaction']['timestamp'])) {
                continue;
            }
            if (!isset($transaction_chunk['public_key'])) {
                continue;
            }
            if (!isset($transaction_chunk['signature'])) {
                continue;
            }

            $transaction = $transaction_chunk['transaction'];
            $thash = hash('sha256', json_encode($transaction));
            $public_key = $transaction_chunk['public_key'];
            $signature = $transaction_chunk['signature'];

            $type = $transaction['type'];
            $timestamp = $transaction['timestamp'];

            if ($timestamp > $max_timestamp) {
                continue;
            }
            if ($timestamp < $min_timestamp) {
                continue;
            }

            $this->transaction_manager->InitializeTransaction($type, $transaction, $thash, $public_key, $signature);
            $validity = $this->transaction_manager->GetTransactionValidity();
            $this->transaction_manager->LoadStatus();

            if ($validity == false) {
                continue;
            }

            // Transaction To Bulk
            $filter = [
                'timestamp' => $timestamp,
                'thash' => $thash,
            ];

            $item = [
                '$set' => [
                    'transaction' => $transaction,
                    'public_key' => $public_key,
                    'signature' => $signature,
                    'result' => '',
                ]
            ];

            $opt = ['upsert' => true];
            $this->db->bulk->update($filter, $item, $opt);
        }

        if ($this->db->bulk->count() > 0) {
            // Transaction To DB

            $namespace = Config::$database_mongodb_name_precommit . '.transactions';
            $this->db->BulkWrite($namespace);
        }
    }

    public function PrecommitBroadcastChunk($chunkname)
    {
        $filename = Config::$directory_broadcastchunks . '/' . $chunkname;
        $transaction_chunks = Chunk::GetChunk($filename);

        $min_timestamp = $this->last_blockinfo['s_timestamp'];
        $max_timestamp = $this->expect_blockinfo['s_timestamp'];

        $this->Precommit($transaction_chunks, $min_timestamp, $max_timestamp);
    }

    public function MakeDecision()
    {
        $this->status_manager->Load();

        $this->transactions = [];

        // Get Precommit Transaction
        $namespace = Config::$database_mongodb_name_precommit . '.transactions';
        $filter = [];
        $opt = ['sort' => ['timestamp' => 1, 'thash' => 1]];
        $rs = $this->db->Query($namespace, $filter, $opt);

        foreach ($rs as $item) {
            $item = Parser::obj2array($item);
            unset($item['_id']);

            $this->transactions[] = [
                'transaction' => $item['transaction'],
                'timestamp' => $item['timestamp'],
                'thash' => $item['thash'],
                'public_key' => $item['public_key'],
                'signature' => $item['signature'],
                'result' => $item['result'],
            ];
        }

        foreach ($this->transactions as $key => $item) {
            $transaction = $item['transaction'];
            $thash = $item['thash'];
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];

            $this->transaction_manager->InitializeTransaction($type, $transaction, $thash, $public_key, $signature);
            $this->transaction_manager->GetStatus();
            $result = $this->transaction_manager->MakeDecision();
            $this->transactions[$key]['result'] = $result;

            if ($result === 'denied') {
                continue;
            }

            $this->transaction_manager->SetStatus();
        }
    }

    public function SetBlockInfo($net_round, $last_blockinfo, $s_timestamp)
    {
        $this->last_blockinfo = $last_blockinfo;

        $this->expect_blockinfo = [
            'block_number' => $net_round,
            'last_blockhash' => $last_blockinfo['blockhash'],
            'blockhash' => '',
            'transaction_count' => 0,
            's_timestamp' => $s_timestamp,
            'timestamp' => Logger::Microtime(),
        ];
    }

    public function GetExpectBlockInfo()
    {
        return $this->expect_blockinfo;
    }

    public function SetExpectBlockhash()
    {
        $this->expect_blockinfo['transaction_count'] = count($this->transactions);
        $transaction_hash = Merkle::MakeMerkleHash($this->transactions);
        $blockhash = Merkle::MakeBlockHash($this->expect_blockinfo['last_blockhash'], $transaction_hash);

        $this->expect_blockinfo['blockhash'] = $blockhash;
    }

    public function Commit()
    {
        if (count($this->transactions) === 0) {
            return;
        }

        $last_blockinfo = $this->last_blockinfo;
        $expect_blockinfo = $this->expect_blockinfo;

        $blockhash = $expect_blockinfo['blockhash'];
        $s_timestamp = $expect_blockinfo['s_timestamp'];

        Fee::SetBlockhash($blockhash);
        Fee::SetStandardTimestamp($s_timestamp);

        $this->status_manager->Preprocess();
        $this->status_manager->Save();
        $this->status_manager->Postprocess();

        $this->CommitTransaction();
        $this->CommitBlock();

        Chunk::RemoveAPIChunk($last_blockinfo['s_timestamp']);
        Chunk::RemoveBroadcastChunk($last_blockinfo['s_timestamp']);
    }

    public function GetCountTransaction()
    {
        return count($this->transactions);
    }

    public function End()
    {
        $this->db->bulk->delete([]);
        $this->db->BulkWrite(Config::$database_mongodb_name_precommit . '.transactions');
    }

    public function CommitTransaction()
    {
        $blockhash = $this->expect_blockinfo['blockhash'];

        foreach ($this->transactions as $transaction) {
            $transaction['block'] = $blockhash;
            $filter = ['thash' => $transaction['thash'], 'timestamp' => $transaction['timestamp']];
            $row = ['$set' => $transaction];
            $opt = ['upsert' => true];
            $this->db->bulk->update($filter, $row, $opt);
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(Config::$database_mongodb_name_committed . '.transactions');
        }
    }

    public function CommitBlock()
    {
        $this->expect_blockinfo['transaction_count'] = $this->GetCountTransaction();
        $this->db->bulk->insert($this->expect_blockinfo);

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(Config::$database_mongodb_name_committed . '.blocks');
        }
    }

    public function MakeTransactionChunk($chunks)
    {
        $blockhash = $this->expect_blockinfo['blockhash'];
        $timestamp = $this->expect_blockinfo['s_timestamp'];
        $block_number = $this->expect_blockinfo['block_number'];
        $transaction_dir = Chunk::GetTransactionDirectory($block_number);
        Chunk::MakeTransactionDirectory($transaction_dir);

        foreach ($chunks as $chunk) {
            $broadcast_chunk = Config::$directory_broadcastchunks . '/' . $chunk;
            $chunk_key = preg_replace('/\.json$/', '.key', $broadcast_chunk);
            $tx_chunk = Config::$directory_transactions . '/' . $transaction_dir . '/' . $blockhash . $timestamp . '.json';

            if (!file_exists($broadcast_chunk) || !file_exists($chunk_key)) {
                return;
            }

            shell_exec("cat {$broadcast_chunk} >> {$tx_chunk}");
        }
    }
}
