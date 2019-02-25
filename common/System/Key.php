<?php

namespace src\System;

use src\Util\Ed25519;

class Key
{
    public static function MakePrivateKey()
    {
        return Ed25519::MakePrivateKey();
    }

    public static function MakePublicKey($private_key)
    {
        return Ed25519::MakePublicKey($private_key);
    }

    public static function MakeAddress($public_key)
    {
        return Ed25519::MakeAddress($public_key, Config::$address_prefix_0, Config::$address_prefix_1);
    }

    public static function MakeSignature($str, $private_key, $public_key)
    {
        return Ed25519::MakeSignature($str, $private_key, $public_key);
    }

    public static function ValidSignature($str, $public_key, $signature)
    {
        return Ed25519::ValidSignature($str, $public_key, $signature);
    }
}
