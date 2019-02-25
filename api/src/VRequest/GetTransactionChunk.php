<?php

namespace src\VRequest;

use src\System\Chunk;
use src\System\Config;
use src\System\Key;
use src\System\Request;
use src\System\Tracker;

class GetTransactionChunk extends Request
{
    protected $request;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $value;
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
        if (isset($this->request['value'])) {
            $this->value = $this->request['value'];
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
        $verification = $verification && (is_string($this->value));
        $verification = $verification && (is_numeric($this->timestamp));

        if ($verification === false) {
            return false;
        }

        $verification = $verification && ($this->type === 'GetTransactionChunk');
        $verification = $verification && (mb_strlen($this->version) < 64);
        $verification = $verification && (mb_strlen($this->from) === 48);

        $verification = $verification && (Key::MakeAddress($this->public_key) === $this->from);
        $verification = $verification && (Key::ValidSignature($this->thash, $this->public_key, $this->signature));
        $verification = $verification && (Tracker::IsAdmittedFullNode($this->from));

        if ($verification === false) {
            return false;
        }

        return true;
    }

    public function _GetResponse()
    {
        $filename = Config::$directory_transactions . '/' . $this->value;

        if (!file_exists($filename)) {
            return ['chunk' => []];
        }

        $chunk_data = Chunk::GetChunk($filename);

        return ['chunk' => $chunk_data];
    }
}
