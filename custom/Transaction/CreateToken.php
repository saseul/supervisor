<?php

namespace src\Transaction;

use src\Status\Attributes;
use src\Status\Token;
use src\Status\TokenList;
use src\System\Config;
use src\System\Key;
use src\System\Transaction;

class CreateToken extends Transaction
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
    private $token_name;
    private $token_publisher;
    private $transactional_data;
    private $timestamp;

    private $from_role;
    private $publish_token_info;
    private $from_token_balance;

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
        if (isset($this->transaction['token_name'])) {
            $this->token_name = $this->transaction['token_name'];
        }
        if (isset($this->transaction['token_publisher'])) {
            $this->token_publisher = $this->transaction['token_publisher'];
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
        $verification = $verification && (is_string($this->token_name));
        $verification = $verification && (is_string($this->token_publisher));
        $verification = $verification && (is_numeric($this->amount));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'CreateToken');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);
        $verification = $verification && (mb_strlen($this->token_name) < 64);
        $verification = $verification && (mb_strlen($this->token_publisher) === 48);
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
        Token::LoadToken($this->from, $this->token_name);
        TokenList::LoadTokenList($this->token_name);
        Attributes::LoadRole($this->from);
    }

    public function _GetStatus()
    {
        $this->from_token_balance = Token::GetBalance($this->from, $this->token_name);
        $this->publish_token_info = TokenList::GetInfo($this->token_name);
        $this->from_role = Attributes::GetRole($this->from);
    }

    public function _MakeDecision()
    {
        if ($this->publish_token_info == []) {
            if ($this->from_role === 'validator') {
                return 'accept';
            }
        } else {
            if (isset($this->publish_token_info['publisher']) && $this->publish_token_info['publisher'] === $this->from) {
                return 'accept';
            }
        }

        return 'denied';
    }

    public function _SetStatus()
    {
        $total_amount = 0;

        if (isset($this->publish_token_info['total_amount'])) {
            $total_amount = $this->publish_token_info['total_amount'];
        }

        $total_amount = $total_amount + (int) $this->amount;
        $this->from_token_balance = (int) $this->from_token_balance + (int) $this->amount;
        $this->publish_token_info = [
            'publisher' => $this->from,
            'total_amount' => $total_amount,
        ];

        Token::SetBalance($this->from, $this->token_name, $this->from_token_balance);
        TokenList::SetInfo($this->token_name, $this->publish_token_info);
    }
}
