<?php

namespace src\Transaction;

use src\Status\Attributes;
use src\Status\Coin;
use src\System\Key;
use src\System\Transaction;

class ChangeRole extends Transaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $role;
    private $transactional_data;
    private $timestamp;

    private $from_deposit;

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
        if (isset($this->transaction['role'])) {
            $this->role = $this->transaction['role'];
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
        $verification = $verification && (is_string($this->role));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'ChangeRole');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _LoadStatus()
    {
        Coin::LoadDeposit($this->from);
    }

    public function _GetStatus()
    {
        $this->from_deposit = Coin::GetDeposit($this->from);
    }

    public function _MakeDecision()
    {
        if (!in_array($this->role, ['validator', 'supervisor', 'arbiter', 'light'])) {
            return 'denied';
        }

        if ($this->role === 'supervisor' && (int) $this->from_deposit < 100000000) {
            return 'denied';
        }

        if ($this->role === 'validator' && (int) $this->from_deposit < 100000000000) {
            return 'denied';
        }

        if ($this->role === 'arbiter' && (int) $this->from_deposit < 100000000000) {
            return 'denied';
        }

        return 'accept';
    }

    public function _SetStatus()
    {
        Attributes::SetRole($this->from, $this->role);
    }
}
