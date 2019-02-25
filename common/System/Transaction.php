<?php

namespace src\System;

class Transaction
{
    public function _Init($transaction, $thash, $public_key, $signature)
    {
    }

    public function _GetValidity()
    {
        return false;
    }

    public function _LoadStatus()
    {
    }

    public function _GetStatus()
    {
    }

    public function _MakeDecision()
    {
        return 'denided';
    }

    public function _SetStatus()
    {
    }
}
