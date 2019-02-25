<?php

namespace src\API\VRequest;

use src\API;
use src\System\Config;
use src\System\Key;

class GetSign extends API
{
    private $string;

    public function _init()
    {
        if (!isset($_REQUEST['string'])) {
            $this->Error('There is no parameter : string ');
        }
        if (!is_string($_REQUEST['string'])) {
            $this->Error('Wrong parameter : string ');
        }
        if (mb_strlen($_REQUEST['string']) != 32) {
            $this->Error('Length of string must be 32. ');
        }

        $this->string = (string) $_REQUEST['string'];
    }

    public function _process()
    {
        $private_key = Config::$node_private_key;
        $public_key = Config::$node_public_key;
        $address = Config::$node_address;
        $signature = Key::MakeSignature($this->string, $private_key, $public_key);

        $this->data = [
            'string' => $this->string,
            'public_key' => $public_key,
            'address' => $address,
            'signature' => $signature,
        ];
    }
}
