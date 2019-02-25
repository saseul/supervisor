<?php

namespace src\System;

use src\Util\ScriptFinder;

class RequestManager
{
    private $request_interfaces;
    private $request;

    public function __construct()
    {
        $this->request_interfaces = [];
        $this->request = new Request();

        $request_interfaces = ScriptFinder::GetRequestInterfaces();
        $vrequest_interfaces = ScriptFinder::GetVRequestInterfaces();

        foreach ($request_interfaces as $request_interface) {
            $class = 'src\\Request\\' . $request_interface;
            $this->request_interfaces[$request_interface] = new $class();
        }

        foreach ($vrequest_interfaces as $vrequest_interface) {
            $class = 'src\\VRequest\\' . $vrequest_interface;
            $this->request_interfaces[$vrequest_interface] = new $class();
        }
    }

    public function Initialize()
    {
        $this->request = null;
    }

    public function InitializeRequest($type, $request, $thash, $public_key, $signature)
    {
        if (isset($this->request_interfaces[$type])) {
            $this->request = $this->request_interfaces[$type];
        }

        $this->request->_Init($request, $thash, $public_key, $signature);
    }

    public function GetRequestValidity()
    {
        return $this->request->_GetValidity();
    }

    public function GetResponse()
    {
        return $this->request->_GetResponse();
    }
}
