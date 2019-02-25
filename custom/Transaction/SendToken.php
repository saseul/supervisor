<?php

namespace src\Transaction;

use src\Status\Token;
use src\System\Key;
use src\System\Transaction;

class SendToken extends Transaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $to;
    private $token_name;
    private $amount;
    private $transactional_data;
    private $timestamp;

    private $from_token_balance;
    private $to_token_balance;

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
        if (isset($this->transaction['to'])) {
            $this->to = $this->transaction['to'];
        }
        if (isset($this->transaction['token_name'])) {
            $this->token_name = $this->transaction['token_name'];
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
        $verification = $verification && (is_string($this->to));
        $verification = $verification && (is_numeric($this->amount));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'SendToken');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);
        $verification = $verification && (mb_strlen($this->to) === 48);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _LoadStatus()
    {
        Token::LoadToken($this->from, $this->token_name);
        Token::LoadToken($this->to, $this->token_name);
    }

    public function _GetStatus()
    {
        $this->from_token_balance = Token::GetBalance($this->from, $this->token_name);
        $this->to_token_balance = Token::GetBalance($this->to, $this->token_name);
    }

    public function _MakeDecision()
    {
        if ((int) $this->amount > (int) $this->from_token_balance) {
            return 'denied';
        }

        return 'accept';
    }

    public function _SetStatus()
    {
        $this->from_token_balance = (int) $this->from_token_balance - (int) $this->amount;
        $this->to_token_balance = (int) $this->to_token_balance + (int) $this->amount;

        Token::SetBalance($this->from, $this->token_name, $this->from_token_balance);
        Token::SetBalance($this->to, $this->token_name, $this->to_token_balance);
    }
}
