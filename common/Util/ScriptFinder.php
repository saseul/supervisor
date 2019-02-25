<?php

namespace src\Util;

/**
 * Class ScriptFinder provides functions related to finding files.
 */
class ScriptFinder
{
    /**
     * Returns the PHP file names that implement the Status interface.
     *
     * @param bool $opt If the value is true, delete the word ".php".
     *
     * @return array The PHP file names that implement the Status interface.
     */
    public static function GetStatusInterfaces($opt = true)
    {
        return self::GetInterfaces(ROOT_DIR . '/src/Status', $opt);
    }

    /**
     * Returns the PHP file names that implement the Transaction interface.
     *
     * @param bool $opt If the value is true, delete the word ".php".
     *
     * @return array The PHP file names that implement the Transaction interface.
     */
    public static function GetTransactionInterfaces($opt = true)
    {
        return self::GetInterfaces(ROOT_DIR . '/src/Transaction', $opt);
    }

    /**
     * Returns the PHP file names that implement the Request interface.
     *
     * @param bool $opt If the value is true, delete the word ".php".
     *
     * @return array The PHP file names that implement the Request interface.
     */
    public static function GetRequestInterfaces($opt = true)
    {
        return self::GetInterfaces(ROOT_DIR . '/src/Request', $opt);
    }

    /**
     * Returns the PHP file names that implement the VRequest interface.
     *
     * @param bool $opt If the value is true, delete the word ".php".
     *
     * @return array The PHP file names that implement the VRequest interface.
     */
    public static function GetVRequestInterfaces($opt = true)
    {
        return self::GetInterfaces(ROOT_DIR . '/src/VRequest', $opt);
    }

    /**
     * Returns the PHP file names that implements all the interfaces under the directory path.
     *
     * @param string $dir The directory path you want to look up.
     * @param bool   $opt If the value is true, delete the word ".php".
     *
     * @return array All the PHP file names that implement the interface under directory path.
     */
    public static function GetInterfaces($dir, $opt = true)
    {
        $scripts = self::GetFiles($dir, $dir);

        if ($opt === true) {
            $scripts = preg_replace('/\.php$/', '', $scripts);
        }

        return $scripts;
    }

    /**
     * Returns the all file names under the directory path.
     *
     * @param string $full_dir The directory path you want to look up.
     * @param string $del_dir  The directory path to escape for file path.
     *
     * @return array The all file names under directory path.
     */
    public static function GetFiles($full_dir, $del_dir = '')
    {
        $d = scandir($full_dir);
        $files = [];

        foreach ($d as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            if (is_dir($full_dir . '/' . $dir)) {
                $c_scripts = self::GetFiles($full_dir . '/' . $dir, $del_dir);
                $files = array_merge($files, $c_scripts);
            } else {
                $escaped_del_dir = preg_replace('/\//', '\\\/', $del_dir);
                $files[] = preg_replace('/^' . $escaped_del_dir . '\//', '', $full_dir . '/' . $dir);
            }
        }

        return $files;
    }
}
