<?php

namespace src\System;

use src\Util\ScriptFinder;

class TransactionManager
{
    private $transaction_interfaces;
    private $transaction;

    public function __construct()
    {
        $this->transaction_interfaces = [];
        $this->transaction = new Transaction();

        $transaction_interfaces = ScriptFinder::GetTransactionInterfaces();

        foreach ($transaction_interfaces as $transaction_interface) {
            $class = 'src\\Transaction\\' . $transaction_interface;
            $this->transaction_interfaces[$transaction_interface] = new $class();
        }
    }

    public function InitializeTransaction($type, $transaction, $thash, $public_key, $signature)
    {
        if (isset($this->transaction_interfaces[$type])) {
            $this->transaction = $this->transaction_interfaces[$type];
        }

        $this->transaction->_Init($transaction, $thash, $public_key, $signature);
    }

    public function GetTransactionValidity()
    {
        return $this->transaction->_GetValidity();
    }

    public function LoadStatus()
    {
        $this->transaction->_LoadStatus();
    }

    public function GetStatus()
    {
        $this->transaction->_GetStatus();
    }

    public function MakeDecision()
    {
        return $this->transaction->_MakeDecision();
    }

    public function SetStatus()
    {
        $this->transaction->_SetStatus();
    }
}
