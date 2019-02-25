<?php

namespace src\System;

use src\Util\Logger;
use src\Util\Merkle;

class Chunk
{
    public static function GetExpectStandardTimestamp($last_s_timestamp)
    {
        $expect_s_timestamp = 0;
        $max_s_timestamp = Logger::Microtime() - Config::$microinterval_chunk;

        $d = scandir(Config::$directory_apichunks);
        $file_timestamps = [];

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $dir) . '000000';

            if ((int) $file_timestamp > (int) $last_s_timestamp && (int) $file_timestamp < (int) $max_s_timestamp) {
                $file_timestamps[] = (int) $file_timestamp;
            }
        }

        sort($file_timestamps);

        for ($i = 0; $i < count($file_timestamps); $i++) {
            $expect_s_timestamp = $file_timestamps[$i];

            if ($i == 4) {
                break;
            }
        }

        return $expect_s_timestamp;
    }

    public static function RemoveAPIChunk($s_timestamp)
    {
        if (!is_numeric($s_timestamp)) {
            return;
        }

        $d = scandir(Config::$directory_apichunks);
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $dir) . '000000';

            if ((int) $file_timestamp <= (int) $s_timestamp) {
                $files[] = $dir;
            }
        }

        foreach ($files as $file) {
            $filename = Config::$directory_apichunks . '/' . $file;
            unlink($filename);
        }
    }

    public static function RemoveBroadcastChunk($s_timestamp)
    {
        if (!is_numeric($s_timestamp)) {
            return;
        }

        $d = scandir(Config::$directory_broadcastchunks);
        $match = [];
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match('/([0-9]+)\\.(json|key)/', $dir, $match)) {
                continue;
            }

            if (!isset($match[1])) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $match[1]) . '000000';

            if ((int) $file_timestamp <= (int) $s_timestamp) {
                $files[] = $dir;
            }
        }

        foreach ($files as $file) {
            $filename = Config::$directory_broadcastchunks . '/' . $file;
            unlink($filename);
        }
    }

    public static function GetChunkSignature($filename)
    {
        $contents = self::GetChunk($filename);
        $thash = Merkle::MakeMerkleHash($contents);

        return Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);
    }

    public static function GetContentsSignature($contents)
    {
        $thash = Merkle::MakeMerkleHash($contents);

        return Key::MakeSignature($thash, Config::$node_private_key, Config::$node_public_key);
    }

    public static function ValidateContentsSignature($contents, $public_key, $signature)
    {
        $thash = Merkle::MakeMerkleHash($contents);

        return Key::ValidSignature($thash, $public_key, $signature);
    }

    public static function GetChunkMicrotime($chunkname)
    {
        $tid = [];
        preg_match('/_([0-9]+)\.json$/', $chunkname, $tid);

        if (!isset($tid[1])) {
            return null;
        }

        return (int) ($tid[1] . '000000');
    }

    public static function GetChunk($filename)
    {
        $file = fopen($filename, 'r');
        $contents = fread($file, filesize($filename));
        fclose($file);
        $contents = '[' . preg_replace('/\,*?$/', '', $contents) . ']';

        return json_decode($contents, true);
    }

    public static function GetKey($filename)
    {
        $file = fopen($filename, 'r');
        $contents = fread($file, filesize($filename));
        fclose($file);

        $keys = preg_split('/[^0-9a-zA-Z]/', trim($contents));

        if (count($keys) < 2) {
            return null;
        }

        return [
            'public_key' => $keys[0],
            'signature' => $keys[1],
        ];
    }

    public static function GetTransactionDirectory($block_number)
    {
        $hex = str_pad(dechex($block_number), 12, '0', STR_PAD_LEFT);
        $dir = [
            mb_substr($hex, 0, 2),
            mb_substr($hex, 2, 2),
            mb_substr($hex, 4, 2),
            mb_substr($hex, 6, 2),
            mb_substr($hex, 8, 2),
        ];

        return implode('/', $dir);
    }

    public static function MakeTransactionDirectory($full_dir)
    {
        $subdir = Config::$directory_transactions;
        $dir = explode('/', $full_dir);

        foreach ($dir as $item) {
            $subdir = $subdir . '/' . $item;

            if (!file_exists($subdir)) {
                shell_exec("mkdir {$subdir}");
            }
        }
    }

    public static function SaveBroadcastChunk($chunk)
    {
        $name = $chunk['name'];
        $rows = $chunk['rows'];
        $count = $chunk['count'];
        $signature = $chunk['signature'];
        $public_key = $chunk['public_key'];

        if (!preg_match('/[0-9]+\\.json$/', $name)) {
            return;
        }

        $filename = Config::$directory_broadcastchunks . '/' . $name;
        $keyname = preg_replace('/\.json$/', '.key', $filename);

        if (file_exists($filename) || file_exists($keyname)) {
            if ($count !== count(file($filename))) {
                unlink($filename);
                unlink($keyname);
            }

            return;
        }

        $file = fopen($filename, 'a');
        foreach ($rows as $row) {
            fwrite($file, json_encode($row) . ",\n");
        }

        fclose($file);

        chmod($filename, 0775);

        $key = fopen($keyname, 'a');
        fwrite($key, $public_key . "\n");
        fwrite($key, $signature . "\n");
        fclose($key);

        chmod($keyname, 0775);
    }

    public static function SaveAPIChunk($contents, $timestamp)
    {
        $filename = Config::$directory_apichunks . '/' . Config::$prefix_chunks . self::GetID($timestamp) . '.json';

        $sign = false;

        if (!file_exists($filename)) {
            $sign = true;
        }

        $file = fopen($filename, 'a');
        fwrite($file, json_encode($contents) . ",\n");
        fclose($file);

        if ($sign) {
            chmod($filename, 0775);
        }
    }

    public static function GetID($timestamp)
    {
        $tid = $timestamp - ($timestamp % Config::$microinterval_chunk) + Config::$microinterval_chunk;

        return preg_replace('/0{6}$/', '', $tid);
    }
}
