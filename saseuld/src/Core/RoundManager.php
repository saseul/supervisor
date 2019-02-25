<?php

namespace src\Core;

use src\System\Cache;
use src\System\Chunk;
use src\System\Config;
use src\System\Key;
use src\Util\Logger;
use src\Util\RestCall;
use src\Util\TypeChecker;

class RoundManager
{
    private static $instance = null;

    private $cache;
    private $rest;

    private $nodes = [];
    private $last_blockinfo = [];

    private $structure_round = [
        'decision' => [
            'round_number' => 0,
            'last_blockhash' => '',
            'last_s_timestamp' => 0,
            'timestamp' => 0,
            'round_key' => '',
            'expect_s_timestamp' => 1
        ],
        'public_key' => '',
        'hash' => '',
        'signature' => ''
    ];

    private $rounds = [];

    // decision
    private $my_round_number = 0;
    private $net_round_number = 0;
    private $net_round_leader = '';
    private $net_s_timestamp = 0;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
        $this->rest = RestCall::GetInstance();
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

        $this->my_round_number = 0;
        $this->net_round_number = 0;
        $this->net_round_leader = '';
        $this->net_s_timestamp = 0;

        $this->rounds = [];
    }

    public function ReadyRound()
    {
        $my_address = Config::$node_address;
        $my_round = $this->MakeRound($this->last_blockinfo);

        $this->rounds[$my_address] = $my_round;
        $this->SaveRound();

        $this->SetMyRoundNumber();
    }

    public function CollectRound()
    {
        $structure = $this->structure_round;

        foreach ($this->nodes as $node) {
            $host = $node['host'];
            $address = $node['address'];

            if ($host === Config::$node_host) {
                continue;
            }

            $round = $this->RequestRound($node['host']);

            if (TypeChecker::StructureCheck($structure, $round) === false) {
                continue;
            }

            if ($this->CheckRequest($address, $round) === false) {
                continue;
            }

            $this->rounds[$address] = $round;
        }

        $this->SaveRound();

        $this->SetNetRoundNumber();

        $this->SetNetStandardTimestamp();
    }

    public function SetMyRoundNumber()
    {
        $my_address = Config::$node_address;

        if (!isset($this->rounds[$my_address])) {
            return;
        }

        $my_round = $this->rounds[$my_address];
        $this->my_round_number = $my_round['decision']['round_number'];
        $this->net_round_number = $this->my_round_number;
        $this->net_round_leader = $my_address;
        $this->net_s_timestamp = $my_round['decision']['expect_s_timestamp'];
    }

    public function SetNetRoundNumber()
    {
        foreach ($this->rounds as $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];

            if ($round_number > $this->net_round_number) {
                $this->net_round_number = $round_number;
            }
        }
    }

    public function SetNetStandardTimestamp()
    {
        $last_s_timestamp = $this->last_blockinfo['s_timestamp'];

        foreach ($this->rounds as $address => $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];
            $expect_s_timestamp = $decision['expect_s_timestamp'];

            if ($round_number === $this->net_round_number) {
                if ($this->net_s_timestamp === 0 || ($this->net_s_timestamp > $expect_s_timestamp && $expect_s_timestamp > $last_s_timestamp)) {
                    $this->net_s_timestamp = $expect_s_timestamp;
                    $this->net_round_leader = $address;
                }
            }
        }
    }

    public function GetMyRoundNumber()
    {
        return $this->my_round_number;
    }

    public function GetNetRoundNumber()
    {
        return $this->net_round_number;
    }

    public function GetNetRoundLeader()
    {
        return $this->net_round_leader;
    }

    public function GetNetStandardTimestamp()
    {
        return $this->net_s_timestamp;
    }

    public function SaveRound()
    {
        $this->cache->set('rounds', $this->rounds);
    }

    public function RequestRound(string $host)
    {
        $url = "http://{$host}/vrequest/getround";

        $rs = $this->rest->POST($url);
        $rs = json_decode($rs, true);

        if (!isset($rs['data'])) {
            return null;
        }

        return $rs['data'];
    }

    public function GetRounds()
    {
        return $this->rounds;
    }

    public function MakeRound($last_blockinfo)
    {
        $my_private_key = Config::$node_private_key;
        $my_public_key = Config::$node_public_key;

        $round_number = $last_blockinfo['block_number'] + 1;
        $last_blockhash = $last_blockinfo['blockhash'];
        $last_s_timestamp = $last_blockinfo['s_timestamp'];
        $timestamp = Logger::Microtime();
        $round_key = $this->MakeRoundKey($last_blockhash, $round_number);
        $expect_s_timestamp = Chunk::GetExpectStandardTimestamp($last_s_timestamp);

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'last_s_timestamp' => $last_s_timestamp,
            'timestamp' => $timestamp,
            'round_key' => $round_key,
            'expect_s_timestamp' => $expect_s_timestamp,
        ];

        $public_key = $my_public_key;
        $hash = hash('sha256', json_encode($decision));
        $signature = Key::MakeSignature($hash, $my_private_key, $my_public_key);

        return [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];
    }

    public function MakeRoundKey($block, $round_number)
    {
        return hash('ripemd160', $block) . $round_number;
    }

    public function CheckRequest($address, $value)
    {
        $decision = $value['decision'];
        $round_number = $value['decision']['round_number'];
        $last_blockhash = $value['decision']['last_blockhash'];
        $round_key = $value['decision']['round_key'];
        $public_key = $value['public_key'];
        $signature = $value['signature'];

        $hash = hash('sha256', json_encode($decision));
        $validation = Key::ValidSignature($hash, $public_key, $signature);
        $address_from_key = Key::MakeAddress($public_key);
        $round_key_from_hash = $this->MakeRoundKey($last_blockhash, $round_number);

        if ($validation == false) {
            return false;
        }
        if ($address_from_key !== $address) {
            return false;
        }
        if ($round_key !== $round_key_from_hash) {
            return false;
        }

        return true;
    }
}
