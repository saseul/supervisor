<?php

namespace src\Core;

use src\System\Cache;
use src\System\Chunk;
use src\System\Config;
use src\System\Database;
use src\System\Key;
use src\Util\Logger;
use src\Util\RestCall;

class SyncManager
{
    private static $instance = null;

    private $db;
    private $rest;
    private $cache;

    private $nodes;
    private $last_blockinfo;

    private $m_sync_host;
    private $m_sync_blocks;

    private $m_my_last_block;
    private $m_sync_info;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->rest = RestCall::GetInstance();
        $this->cache = Cache::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function Initialize($nodes, $last_blockinfo)
    {
        $this->nodes = $nodes;
        $this->last_blockinfo = $last_blockinfo;

        $this->m_sync_host = '';
        $this->m_sync_info = [
            's_timestamp' => 0,
            'min_timestamp' => 0,
            'max_timestamp' => 0,
            'blockhash' => '',
            'chunk_name' => '',
            'transactions_chunks' => [],
            'block_number' => 0,
        ];
    }

    public function ReadySync()
    {
        $last_block_number = $this->last_blockinfo['block_number'];

        foreach ($this->nodes as $node) {
            $host = $node['host'];
            $blocks = $this->RequestBlocks($host);

            if (isset($blocks['committed'][0]['block_number'])) {
                $target_last_block_number = $blocks['committed'][0]['block_number'];

                if ($target_last_block_number > $last_block_number) {
                    $this->m_sync_host = $host;
                    $this->m_sync_blocks = $blocks;
                    $this->m_my_last_block = $this->last_blockinfo;

                    break;
                }
            }
        }
    }

    public function SetSyncInfo()
    {
        if ($this->m_sync_host === '') {
            return;
        }

        $sync_blocks = $this->m_sync_blocks;
        $my_last_block = $this->m_my_last_block;

        $sync_block_number = $sync_blocks['committed'][0]['block_number'];
        $my_last_block_number = $my_last_block['block_number'];

        if ((int) $sync_block_number <= (int) $my_last_block_number) {
            return;
        }

        $start_block_number = $my_last_block_number + 1;
        $list_transaction_chunk = $this->ListTransactionChunk($this->m_sync_host, $start_block_number);

        $this->m_sync_info['min_timestamp'] = $my_last_block['s_timestamp'];

        foreach ($list_transaction_chunk as $list_transaction) {
            $chunk_name = $list_transaction['chunk_name'];
            $block_info = $list_transaction['block_info'];

            $this->m_sync_info['s_timestamp'] = $block_info['s_timestamp'];
            $this->m_sync_info['max_timestamp'] = $block_info['s_timestamp'];
            $this->m_sync_info['blockhash'] = $block_info['blockhash'];
            $this->m_sync_info['chunk_name'] = $chunk_name;
            $this->m_sync_info['block_number'] = $block_info['block_number'];
            $this->m_sync_info['transactions_chunks'] = $this->GetTransactionChunk($this->m_sync_host, $chunk_name);

            break;
        }
    }

    public function GetSyncInfo()
    {
        return $this->m_sync_info;
    }

    public function SyncTransactionChunk()
    {
        $block_number = $this->m_sync_info['block_number'];
        $chunkname = $this->m_sync_info['chunk_name'];
        $transactions = $this->m_sync_info['transactions_chunks'];
        $transaction_chunk = Config::$directory_transactions . '/' . $chunkname;

        $transaction_dir = Chunk::GetTransactionDirectory($block_number);
        Chunk::MakeTransactionDirectory($transaction_dir);

        if (file_exists($transaction_chunk)) {
            return;
        }

        $file = fopen($transaction_chunk, 'a');

        foreach ($transactions as $transaction) {
            fwrite($file, json_encode($transaction) . ",\n");
        }

        fclose($file);
    }

    public function ListTransactionChunk(string $host, int $block_number)
    {
        $request = [
            'version' => Config::$version,
            'type' => 'ListTransactionChunk',
            'from' => Config::$node_address,
            'value' => $block_number,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request));
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $url = "http://{$host}/request";
        $data = [
            'request' => json_encode($request),
            'public_key' => Config::$node_public_key,
            'signature' => $signature,
        ];

        $rs = $this->rest->POST($url, $data);
        $rs = json_decode($rs, true);

        if (!isset($rs['data']['chunks'])) {
            return [];
        }

        return $rs['data']['chunks'];
    }

    public function RequestBlocks($host)
    {
        $url = "http://{$host}/vrequest/getblocks";

        $rs = $this->rest->POST($url);
        $rs = json_decode($rs, true);

        if (!isset($rs['data'])) {
            return [];
        }

        return $rs['data'];
    }

    public function GetTransactionChunk($host, $chunkname)
    {
        $request = [
            'version' => Config::$version,
            'type' => 'GetTransactionChunk',
            'from' => Config::$node_address,
            'value' => $chunkname,
            'transactional_data' => '',
            'timestamp' => Logger::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request));
        $signature = Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);

        $url = "http://{$host}/request";
        $data = [
            'request' => json_encode($request),
            'public_key' => Config::$node_public_key,
            'signature' => $signature,
        ];

        $rs = $this->rest->POST($url, $data);
        $rs = json_decode($rs, true);

        if (!isset($rs['data']['chunk'])) {
            return [];
        }

        return $rs['data']['chunk'];
    }
}
