<?php

namespace src\API;

use src\API;
use src\System\RequestManager;

class Request extends API
{
    protected $request_manager;

    protected $request;
    protected $public_key;
    protected $signature;

    public function __construct()
    {
        $this->request_manager = new RequestManager();
    }

    public function _init()
    {
        if (!isset($_REQUEST['request'])) {
            $_REQUEST['request'] = '{}';
        }
        if (!isset($_REQUEST['public_key'])) {
            $_REQUEST['public_key'] = '';
        }
        if (!isset($_REQUEST['signature'])) {
            $_REQUEST['signature'] = '';
        }

        $this->request = json_decode($_REQUEST['request'], true);
        $this->public_key = $_REQUEST['public_key'];
        $this->signature = $_REQUEST['signature'];
    }

    public function _process()
    {
        if (!isset($this->request['type'])) {
            $this->Error('There is no type');
        }

        $type = $this->request['type'];

        $request = $this->request;
        $thash = hash('sha256', json_encode($request));
        $public_key = $this->public_key;
        $signature = $this->signature;

        $this->request_manager->InitializeRequest($type, $request, $thash, $public_key, $signature);
        $validity = $this->request_manager->GetRequestValidity();

        if ($validity == false) {
            $this->Error('Invalid request');
        }
    }

    public function _end()
    {
        $this->data = $this->request_manager->GetResponse();
    }
}
