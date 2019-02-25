<?php

namespace src\Transaction;

use src\Status\Attributes;
use src\Status\Coin;
use src\System\Block;
use src\System\Config;
use src\System\Key;
use src\System\Transaction;

class Genesis extends Transaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $amount;
    private $transactional_data;
    private $timestamp;

    private $from_balance;
    private $from_role;
    private $block_count;

    public function _Init($transaction, $thash, $public_key, $signature)
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        if (isset($this->transaction['type'])) {
            $this->type = $this->transaction['type'];
        }
        if (isset($this->transaction['version'])) {
            $this->version = $this->transaction['version'];
        }
        if (isset($this->transaction['from'])) {
            $this->from = $this->transaction['from'];
        }
        if (isset($this->transaction['amount'])) {
            $this->amount = $this->transaction['amount'];
        }
        if (isset($this->transaction['transactional_data'])) {
            $this->transactional_data = $this->transaction['transactional_data'];
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }
    }

    public function _GetValidity()
    {
        $verification = true;
        $verification = $verification && (is_string($this->type));
        $verification = $verification && (is_string($this->version));
        $verification = $verification && (is_string($this->from));
        $verification = $verification && (is_numeric($this->amount));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'Genesis');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);
        $verification = $verification && ($this->amount <= Config::$genesis_coin_value);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _LoadStatus()
    {
        Coin::LoadBalance($this->from);
        Attributes::LoadRole($this->from);
    }

    public function _GetStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_role = Attributes::GetRole($this->from);
        $this->block_count = Block::GetCount();
    }

    public function _MakeDecision()
    {
        if ($this->from !== Config::$genesis_address || (int) $this->block_count > 0) {
            return 'denied';
        }

        return 'accept';
    }

    public function _SetStatus()
    {
        $this->from_balance = (int) $this->from_balance + (int) $this->amount;
        $this->from_role = 'validator';

        Coin::SetBalance($this->from, $this->from_balance);
        Attributes::SetRole($this->from, $this->from_role);
    }
}
