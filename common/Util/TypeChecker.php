<?php

namespace src\Util;

/**
 * Class TypeChecker provides a function to check the data type.
 */
class TypeChecker
{
    /**
     * Check which data structure is correct.
     *
     * @param array $tpl   The data to be used as a schema.
     * @param array $value The data to be checked.
     *
     * @return bool True if the $value is correct.
     */
    public static function StructureCheck($tpl, $value)
    {
        foreach ($tpl as $k => $v) {
            if (!isset($value[$k])) {
                return false;
            }
            if (gettype($v) !== gettype($value[$k])) {
                return false;
            }
            if (is_array($v) && count($v) > 0 && self::StructureCheck($v, $value[$k]) === false) {
                return false;
            }
        }

        return true;
    }
}
