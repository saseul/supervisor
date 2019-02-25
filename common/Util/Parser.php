<?php

namespace src\Util;

class Parser
{
    public static function obj2array($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }

    public static function array2obj($d)
    {
        if (is_array($d)) {
            return (object) array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }
}
