<?php

namespace src\Util;

/**
 * Merkle provides functions related to the hashing of the merkle tree and block.
 */
class Merkle
{
    /**
     * Generates the Merkle root.
     *
     * @param array $array Data to be hashed(e.g. Transactions)
     *
     * @return string a hash value
     */
    public static function MakeMerkleHash($array)
    {
        if (count($array) === 0) {
            return hash('sha256', json_encode($array));
        }

        $hash_array = [];

        foreach ($array as $item) {
            $hash_array[] = self::Hash($item);
        }

        while (count($hash_array) > 1) {
            $tmp_array = $hash_array;
            $hash_array = [];

            for ($i = 0; $i < count($tmp_array); $i = $i + 2) {
                if ($i === count($tmp_array) - 1) {
                    $hash_array[] = $tmp_array[$i];
                } else {
                    $hash_array[] = self::Hash($tmp_array[$i] . $tmp_array[$i + 1]);
                }
            }
        }

        return $hash_array[0];
    }

    /**
     * Generates a SHA-256 hash value of input object.
     *
     * @param mixed $obj
     *
     * @return string a hash value
     */
    public static function Hash($obj)
    {
        if (in_array(gettype($obj), ['array', 'object', 'resource'])) {
            return hash('sha256', json_encode($obj));
        }

        return hash('sha256', $obj);
    }

    /**
     * Generates the hash value of the current block.
     *
     * The generated hash value is made of 'the hash value of the previous block'
     * and 'the Merkle root of the transactions to be included in the current block'.
     *
     * @param string $last_blockhash   the hash value of the last block
     * @param string $transaction_hash the hash value of the transactions
     *
     * @return string a hash value
     */
    public static function MakeBlockHash($last_blockhash, $transaction_hash)
    {
        return hash('sha256', $last_blockhash . $transaction_hash);
    }
}
