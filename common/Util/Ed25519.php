<?php

namespace src\Util;

class Ed25519
{
    public static function MakePrivateKey()
    {
        return bin2hex(random_bytes(24)) . str_pad(dechex(Logger::Microtime()), 16, 0, 0);
    }

    public static function MakePublicKey($private_key)
    {
        return bin2hex(ed25519_publickey(hex2bin($private_key)));
    }

    public static function MakeAddress($public_key, $prefix_0, $prefix_1)
    {
        $p0 = $prefix_0;
        $p1 = $prefix_1;
        $s1 = $p1 . hash('ripemd160', hash('sha256', $p0 . $public_key));

        return $s1 . substr(hash('sha256', hash('sha256', $s1)), 0, 4);
    }

    public static function MakeSignature($str, $private_key, $public_key)
    {
        return bin2hex(ed25519_sign($str, hex2bin($private_key), hex2bin($public_key)));
    }

    public static function ValidSignature($str, $public_key, $signature)
    {
        return ed25519_sign_open($str, hex2bin($public_key), hex2bin($signature));
    }
}
