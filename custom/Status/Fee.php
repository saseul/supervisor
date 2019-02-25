<?php

namespace src\Status;

use src\System\Database;
use src\System\Status;

class Fee extends Status
{
    protected static $namespace = 'saseul_committed.contract';

    protected static $validators = [];
    protected static $fee = 0;
    protected static $blockhash = '';
    protected static $s_timestamp = 0;

    public static function GetFee()
    {
        return self::$fee;
    }

    public static function SetFee(int $value)
    {
        self::$fee = $value;
    }

    public static function SetValidators(array $validators)
    {
        self::$validators = $validators;
    }

    public static function SetValidator(string $address)
    {
        self::$validators[] = $address;
    }

    public static function _Reset()
    {
        self::$validators = [];
        self::$fee = 0;
        self::$blockhash = '';
        self::$s_timestamp = 0;
    }

    public static function SetBlockhash(string $blockhash)
    {
        self::$blockhash = $blockhash;
    }

    public static function SetStandardTimestamp(int $s_timestamp)
    {
        self::$s_timestamp = $s_timestamp;
    }

    public static function _Preprocess()
    {
    }

    public static function _Postprocess()
    {
        if ((int) self::$fee === 0) {
            self::_Reset();

            return;
        }

        $validators = \src\Method\Attributes::GetValidator();
        $all = \src\Method\Coin::GetAll($validators);

        $sum = 0;
        $remain = self::$fee;
        $validators = [];
        $fees = [];
        $addresses = [];

        // Calculate sum
        foreach ($all as $item) {
            $sum = $sum + (int) $item['deposit'];
        }

        // Calculate remain
        foreach ($all as $address => $item) {
            $fee = (int) (self::$fee * ((int) $item['deposit'] / $sum));
            $fees[] = $fee;
            $addresses[] = $address;

            $validators[] = [
                'address' => $address,
                'balance' => $item['balance'] + $fee,
            ];

            $remain = $remain - $fee;
        }

        // Calculate remain fee
        array_multisort($fees, $addresses, $validators);

        foreach ($validators as $key => $_) {
            if ($remain <= 0) {
                break;
            }

            $validators[$key]['balance'] = $validators[$key]['balance'] + 1;
            $remain = $remain - 1;
        }

        // Set Balance;
        $db = Database::GetInstance();

        foreach ($validators as $validator) {
            $filter = ['address' => $validator['address']];
            $row = [
                '$set' => ['balance' => $validator['balance']],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite('saseul_committed.coin');
        }

        self::_Reset();
    }
}
