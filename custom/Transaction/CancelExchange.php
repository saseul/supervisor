<?php

namespace src\Transaction;

use src\System\Key;
use src\System\Transaction;

class CancelExchange extends Transaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $eid;
    private $transactional_data;
    private $timestamp;

    private $related_order;

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
        if (isset($this->transaction['eid'])) {
            $this->eid = $this->transaction['eid'];
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
        $verification = $verification && (is_string($this->eid));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'CancelExchange');
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
        \src\Status\Exchange::LoadOrderByEID($this->eid);
    }

    public function _GetStatus()
    {
        $this->related_order = \src\Status\Exchange::GetOrderByEID($this->eid);
    }

    public function _MakeDecision()
    {
        if (isset($this->related_order['from'])) {
            if ($this->related_order['from'] === $this->from) {
                return 'accept';
            }
        }

        return 'denied';
    }

    public function _SetStatus()
    {
        \src\Status\Exchange::SetDeleteOrder($this->eid);
    }
}
