<?php

namespace src\Request;

use src\Method\Attributes;
use src\System\Key;
use src\System\Request;

class GetRole extends Request
{
    protected $request;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $transactional_data;
    private $timestamp;

    public function _Init($request, $thash, $public_key, $signature)
    {
        $this->request = $request;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        if (isset($this->request['type'])) {
            $this->type = $this->request['type'];
        }
        if (isset($this->request['version'])) {
            $this->version = $this->request['version'];
        }
        if (isset($this->request['from'])) {
            $this->from = $this->request['from'];
        }
        if (isset($this->request['transactional_data'])) {
            $this->transactional_data = $this->request['transactional_data'];
        }
        if (isset($this->request['timestamp'])) {
            $this->timestamp = $this->request['timestamp'];
        }
    }

    public function _GetValidity()
    {
        $verification = true;
        $verification = $verification && (is_string($this->type));
        $verification = $verification && (is_string($this->version));
        $verification = $verification && (is_string($this->from));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'GetRole');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _GetResponse()
    {
        $from = $this->request['from'];

        return Attributes::GetRole($from);
    }
}
