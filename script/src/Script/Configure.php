<?php

namespace src\Script;

use src\Script;
use src\System\Key;
use src\Util\Logger;

class Configure extends Script
{
    public function _process()
    {
        Logger::EchoLog('Enter IP Address ');
        $stdin = trim(fgets(STDIN));

        if (!preg_match('/^[0-9a-z]{1,}\\.[0-9a-z]{1,}\\.[0-9a-z]{1,}(\\.[0-9a-z]{1,})?$/', $stdin)) {
            Logger::EchoLog('Invalid IP');

            return;
        }

        $this->SetConfig($stdin);
        Logger::EchoLog('End');
    }

    public function SetConfig($ip)
    {
        $api_config = '/var/saseul-origin/common/System/Config.php';

        $private_key = Key::MakePrivateKey();
        $public_key = Key::MakePublicKey($private_key);
        $address = Key::MakeAddress($public_key);
        $ip = preg_replace('/\\./', '\\.', $ip);

        $this->SubConfig($api_config, $private_key, $public_key, $address, $ip);
    }

    public function SubConfig($file, $private_key, $public_key, $address, $ip)
    {
        shell_exec("sed -e \"s/.*node_private_key.*/    public static \\\$node_private_key = '{$private_key}';/g\" {$file} > {$file}.tmp");
        shell_exec("mv -f {$file}.tmp {$file}");
        usleep(100000);

        shell_exec("sed -e \"s/.*node_public_key.*/    public static \\\$node_public_key = '{$public_key}';/g\" {$file} > {$file}.tmp");
        shell_exec("mv -f {$file}.tmp {$file}");
        usleep(100000);

        shell_exec("sed -e \"s/.*node_address.*/    public static \\\$node_address = '{$address}';/g\" {$file} > {$file}.tmp");
        shell_exec("mv -f {$file}.tmp {$file}");
        usleep(100000);

        shell_exec("sed -e \"s/.*node_host.*/    public static \\\$node_host = '{$ip}';/g\" {$file} > {$file}.tmp");
        shell_exec("mv -f {$file}.tmp {$file}");
        usleep(100000);
    }
}
