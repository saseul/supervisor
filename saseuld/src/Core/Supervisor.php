<?php

namespace src\Core;

use src\System\Block;
use src\System\Tracker;

class Supervisor extends Node
{
    private $accessible_nodes;
    private $last_blockinfo;

    private $round_manager;
    private $commit_manager;
    private $sync_manager;

    private $my_round_number;
    private $net_round_number;
    private $net_round_leader;
    private $net_s_timestamp;

    private $sync_info;
    private $expect_blockinfo;
    private $expect_blockhash;

    private $sync_fail_count = 0;

    public function __construct()
    {
        $this->round_manager = new RoundManager();
        $this->commit_manager = new CommitManager();
        $this->sync_manager = new SyncManager();
    }

    public function Initialize()
    {
        // get node infos
        $this->accessible_nodes = Tracker::GetValidator();
        $this->last_blockinfo = Block::GetLastBlock();

        // initilaize managers
        $this->round_manager->Initialize($this->accessible_nodes, $this->last_blockinfo);
        $this->commit_manager->Initialize();
        $this->sync_manager->Initialize($this->accessible_nodes, $this->last_blockinfo);
    }

    public function ProcessRound()
    {
        // decide round
        $this->round_manager->ReadyRound();
        $this->round_manager->CollectRound();

        // get round result
        $this->my_round_number = $this->round_manager->GetMyRoundNumber();
        $this->net_round_number = $this->round_manager->GetNetRoundNumber();
        $this->net_round_leader = $this->round_manager->GetNetRoundLeader();
        $this->net_s_timestamp = $this->round_manager->GetNetStandardTimestamp();
    }

    public function Sync()
    {
        $this->sync_manager->ReadySync();
        $this->sync_manager->SetSyncInfo();

        $this->sync_info = $this->sync_manager->GetSyncInfo();

        $sync_min_timestamp = $this->sync_info['min_timestamp'];
        $sync_max_timestamp = $this->sync_info['max_timestamp'];
        $sync_transactions_chunks = $this->sync_info['transactions_chunks'];

        $this->commit_manager->SetBlockInfo($this->my_round_number, $this->last_blockinfo, $sync_max_timestamp);
        $this->commit_manager->Precommit($sync_transactions_chunks, $sync_min_timestamp, $sync_max_timestamp);

        $this->commit_manager->MakeDecision();
        $this->commit_manager->SetExpectBlockhash();

        $this->expect_blockinfo = $this->commit_manager->GetExpectBlockInfo();
        $this->expect_blockhash = $this->expect_blockinfo['blockhash'];
    }

    public function Action()
    {
        $this->Initialize();
        $this->ProcessRound();
        // ready broadcast

        if ($this->my_round_number !== $this->net_round_number) {
            $this->Sync();

            if ($this->sync_info['blockhash'] === $this->expect_blockhash) {
                $this->sync_manager->SyncTransactionChunk();
                $this->commit_manager->Commit();
                $this->commit_manager->End();
                $this->sync_fail_count = 0;
            } else {
                // Banish
                if ($this->sync_fail_count >= 10) {
                    // Need add fork process
                    Tracker::Banish($this->net_round_leader);

                    \System_Daemon::info('[Banish round leader] ');
                    \System_Daemon::info('my round number : ' . $this->my_round_number);
                    \System_Daemon::info('net round number : ' . $this->net_round_number);
                    \System_Daemon::info('net round leader : ' . $this->net_round_leader);
                    \System_Daemon::info('net standard timestamp : ' . $this->net_s_timestamp);
                    \System_Daemon::info('sync info : ' . json_encode($this->sync_info));
                    \System_Daemon::info('expect info : ' . json_encode($this->expect_blockinfo));
                    \System_Daemon::info('sync blockhash : ' . $this->sync_info['blockhash']);
                    \System_Daemon::info('expect blockhash : ' . $this->expect_blockhash);

                    $this->sync_fail_count = 0;

                    return;
                }

                $this->sync_fail_count = $this->sync_fail_count + 1;
                \System_Daemon::info('[Sync fail] ');
                \System_Daemon::info('sync blockhash : ' . $this->sync_info['blockhash']);
                \System_Daemon::info('expect blockhash : ' . $this->expect_blockhash);
            }
        }
    }
}
