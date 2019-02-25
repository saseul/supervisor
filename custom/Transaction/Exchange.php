<?php

namespace src\Transaction;

use src\Status\Coin;
use src\Status\Token;
use src\Status\TokenList;
use src\System\Key;
use src\System\Transaction;

class Exchange extends Transaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $from_type;
    private $from_currency_name;
    private $from_amount;
    private $to_type;
    private $to_currency_name;
    private $to_amount;
    private $exchange_rate;
    private $exchange_type;
    private $transactional_data;
    private $timestamp;

    private $valid_token = false;
    private $balance;
    private $related_orders;

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
        if (isset($this->transaction['from_type'])) {
            $this->from_type = $this->transaction['from_type'];
        }
        if (isset($this->transaction['from_currency_name'])) {
            $this->from_currency_name = $this->transaction['from_currency_name'];
        }
        if (isset($this->transaction['from_amount'])) {
            $this->from_amount = $this->transaction['from_amount'];
        }
        if (isset($this->transaction['to_type'])) {
            $this->to_type = $this->transaction['to_type'];
        }
        if (isset($this->transaction['to_currency_name'])) {
            $this->to_currency_name = $this->transaction['to_currency_name'];
        }
        if (isset($this->transaction['to_amount'])) {
            $this->to_amount = $this->transaction['to_amount'];
        }
        if (isset($this->transaction['exchange_rate'])) {
            $this->exchange_rate = $this->transaction['exchange_rate'];
        }
        if (isset($this->transaction['exchange_type'])) {
            $this->exchange_type = $this->transaction['exchange_type'];
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
        $verification = $verification && (is_string($this->from_type));
        $verification = $verification && (is_string($this->from_currency_name));
        $verification = $verification && (is_numeric($this->from_amount));
        $verification = $verification && (is_string($this->to_type));
        $verification = $verification && (is_string($this->to_currency_name));
        $verification = $verification && (is_numeric($this->to_amount));
        $verification = $verification && (is_numeric($this->exchange_rate));
        $verification = $verification && (is_string($this->exchange_type));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'Exchange');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);
        $verification = $verification && (in_array($this->to_type, ['coin', 'token']));
        $verification = $verification && (in_array($this->from_type, ['coin', 'token']));
        $verification = $verification && (in_array($this->exchange_type, ['buy', 'sell']));
        $verification = $verification && ((float) $this->to_amount * (float) $this->exchange_rate == (float) $this->from_amount);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _LoadStatus()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            if ($this->from_type === 'coin') {
                Coin::LoadBalance($this->from);
            }

            if ($this->from_type === 'token') {
                Token::LoadToken($this->from, $this->from_currency_name);
            }
        } else {
            // sell

            if ($this->to_type === 'coin') {
                Coin::LoadBalance($this->from);
            }

            if ($this->to_type === 'token') {
                Token::LoadToken($this->from, $this->to_currency_name);
            }
        }

        if ($this->from_type === 'token') {
            TokenList::LoadTokenList($this->from_currency_name);
        }

        if ($this->to_type === 'token') {
            TokenList::LoadTokenList($this->to_currency_name);
        }

        \src\Status\Exchange::LoadOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name);
    }

    public function _GetStatus()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            if ($this->from_type === 'coin') {
                $this->balance = Coin::GetBalance($this->from);
            }

            if ($this->from_type === 'token') {
                $this->balance = Token::GetBalance($this->from, $this->from_currency_name);
            }
        } else {
            // sell

            if ($this->to_type === 'coin') {
                $this->balance = Coin::GetBalance($this->from);
            }

            if ($this->to_type === 'token') {
                $this->balance = Token::GetBalance($this->from, $this->to_currency_name);
            }
        }

        $this->valid_token = true;

        if ($this->from_type === 'token') {
            if (empty(TokenList::GetInfo($this->from_currency_name))) {
                $this->valid_token = false;
            }
        }

        if ($this->to_type === 'token') {
            if (empty(TokenList::GetInfo($this->to_currency_name))) {
                $this->valid_token = false;
            }
        }

        $this->related_orders = \src\Status\Exchange::GetOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name);
    }

    public function _MakeDecision()
    {
        if ($this->valid_token === false) {
            return 'denied';
        }

        if ($this->exchange_type === 'buy') {
            // buy

            if ((int) $this->from_amount > (int) $this->balance) {
                return 'denied';
            }
        } else {
            // sell

            if ((int) $this->to_amount > (int) $this->balance) {
                return 'denied';
            }
        }

        return 'accept';
    }

    public function _SetStatus()
    {
        $this->SetBalance();
        $orders_for_exchange = $this->GetOrdersForExchange();

        $remain_from_amount = $this->from_amount;
        $remain_to_amount = $this->to_amount;

        foreach ($orders_for_exchange as $k => $order) {
            if ((float) $order['to_amount'] > (float) $remain_to_amount) {
                $orders_for_exchange[$k]['to_amount'] = (float) $orders_for_exchange[$k]['to_amount'] - (float) $remain_to_amount;
                $orders_for_exchange[$k]['from_amount'] = (float) $orders_for_exchange[$k]['from_amount'] - (float) $remain_from_amount;
                \src\Status\Exchange::SetOrder($order['from_type'], $order['from_currency_name'], $order['to_type'], $order['to_currency_name'], $order['eid'], $orders_for_exchange[$k]);

                $result = [
                    'from' => $this->from,
                    'from_type' => $this->from_type,
                    'from_currency_name' => $this->from_currency_name,
                    'from_amount' => $remain_from_amount,
                    'to' => $order['from'],
                    'to_type' => $this->to_type,
                    'to_currency_name' => $this->to_currency_name,
                    'to_amount' => $remain_to_amount,
                    'exchange_rate' => $this->exchange_rate,
                    'exchange_type' => $this->exchange_type,
                    'timestamp' => $this->timestamp,
                ];

                $remain_to_amount = 0;
                $remain_from_amount = 0;

                \src\Status\Exchange::SetResult($result);
            } else {
                \src\Status\Exchange::SetDeleteOrder($order['eid']);

                $result = [
                    'from' => $this->from,
                    'from_type' => $this->from_type,
                    'from_currency_name' => $this->from_currency_name,
                    'from_amount' => $order['from_amount'],
                    'to' => $order['from'],
                    'to_type' => $this->to_type,
                    'to_currency_name' => $this->to_currency_name,
                    'to_amount' => $order['to_amount'],
                    'exchange_rate' => $order['exchange_rate'],
                    'exchange_type' => $this->exchange_type,
                    'timestamp' => $this->timestamp,
                ];

                $remain_to_amount = (float) $remain_to_amount - (float) $order['to_amount'];
                $remain_from_amount = (float) $remain_to_amount * (float) $this->exchange_rate;

                \src\Status\Exchange::SetResult($result);
            }

            if ((float) $remain_to_amount == 0) {
                break;
            }
        }

        if ((float) $remain_to_amount > 0) {
            $order = [
                'from' => $this->from,
                'from_type' => $this->from_type,
                'from_currency_name' => $this->from_currency_name,
                'from_amount' => $remain_from_amount,
                'to_type' => $this->to_type,
                'to_currency_name' => $this->to_currency_name,
                'to_amount' => $remain_to_amount,
                'exchange_rate' => $this->exchange_rate,
                'exchange_type' => $this->exchange_type,
                'timestamp' => $this->timestamp,
            ];

            $ehash = hash('sha256', json_encode($order));
            $eid = $ehash . $this->timestamp;

            $order['eid'] = $eid;

            \src\Status\Exchange::SetOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name, $eid, $order);
        }
    }

    private function SetBalance()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            $this->balance = (int) $this->balance - (int) $this->from_amount;

            if ($this->from_type === 'coin') {
                Coin::SetBalance($this->from, $this->balance);
            }

            if ($this->from_type === 'token') {
                Token::SetBalance($this->from, $this->from_currency_name, $this->balance);
            }
        } else {
            // sell

            $this->balance = (int) $this->balance - (int) $this->to_amount;

            if ($this->to_type === 'coin') {
                Coin::SetBalance($this->from, $this->balance);
            }

            if ($this->to_type === 'token') {
                Token::SetBalance($this->from, $this->to_currency_name, $this->balance);
            }
        }
    }

    private function GetOrdersForExchange()
    {
        $related_exchange_type = 'buy';

        if ($this->exchange_type === 'buy') {
            $related_exchange_type = 'sell';
        }

        $orders_for_exchange = [];
        $orders_exchange_rate = [];
        $orders_timestamp = [];

        foreach ($this->related_orders as $related_order) {
            if ($related_order['exchange_type'] !== $related_exchange_type) {
                continue;
            }

            if ($this->exchange_type === 'buy') {
                if ((float) $related_order['exchange_rate'] > (float) $this->exchange_rate) {
                    continue;
                }
            } else {
                if ((float) $related_order['exchange_rate'] < (float) $this->exchange_rate) {
                    continue;
                }
            }

            $orders_for_exchange[] = $related_order;
            $orders_exchange_rate[] = $related_order['exchange_rate'];
            $orders_timestamp[] = $related_order['timestamp'];
        }

        if ($this->exchange_type === 'buy') {
            array_multisort($orders_exchange_rate, $orders_timestamp, $orders_for_exchange);
        } else {
            array_multisort($orders_exchange_rate, SORT_DESC, $orders_timestamp, $orders_for_exchange);
        }

        return $orders_for_exchange;
    }
}
