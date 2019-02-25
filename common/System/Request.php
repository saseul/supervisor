<?php

namespace src\System;

class Request
{
    public function _Init($request, $thash, $public_key, $signature)
    {
    }

    public function _GetValidity()
    {
        return false;
    }

    public function _GetResponse()
    {
        return [];
    }
}
