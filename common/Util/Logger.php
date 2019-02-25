<?php

namespace src\Util;

/**
 * Logger provides functions for logging.
 */
class Logger
{
    /**
     * Prints information of an object and calling location.
     *
     * Optionally exit the program completely.
     *
     * @param mixed $obj    Object that need to print for inspect its value.
     * @param bool  $option When true, exit the program.
     */
    public static function Log($obj, $option = false)
    {
        print_r('[Log]' . xdebug_call_file() . ':' . xdebug_call_line() . PHP_EOL);
        print_r($obj);
        print_r(PHP_EOL);
        if ($option) {
            exit();
        }
    }

    /**
     * Prints information of an object.
     *
     * @param mixed $obj Object that need to print for inspect its value.
     */
    public static function EchoLog($obj)
    {
        print_r($obj);
        print_r(PHP_EOL);
    }

    /**
     * Prints an error message with an object and calling location.
     * And Exit the program completely.
     *
     * @param array $obj Object that need to print for inspect its value.
     */
    public static function Error($obj = null)
    {
        print_r('[Error]' . xdebug_call_file() . ':' . xdebug_call_line() . PHP_EOL);

        if ($obj !== null) {
            print_r($obj);
            print_r(PHP_EOL);
        }

        exit();
    }

    /**
     * This returns formatted date string based on system clock.
     *
     * example: On January 28, 2019 at 15:37:21, will return 20190128153721.
     *
     * @return string current datetime
     */
    public static function Date()
    {
        return date('YmdHis');
    }

    /**
     * This returns the current Unix timestamp with microseconds.
     *
     * @return int indicates the current time
     */
    public static function Microtime()
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000000);
    }

    /**
     * This returns comma formatted current Unix timestamp with microseconds.
     *
     * example: When the microseconds is 1548727355484738,
     * this returns 1548727355.4848 (double)
     *
     * @return float indicates the current time
     */
    public static function MicrotimeWithComma()
    {
        // FIXME: Where is comma insertion?
        return array_sum(explode(' ', microtime()));
    }

    /**
     * This returns the current Unix timestamp with milliseconds.
     *
     * @return int indicates the current time
     */
    public static function Millitime()
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000);
    }
}
