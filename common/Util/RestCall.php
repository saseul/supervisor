<?php

namespace src\Util;

/**
 * RestCall provides functions for HTTP request and etc.
 */
class RestCall
{
    protected static $instance = null;

    protected $rest;
    protected $timeout;
    protected $info;

    public function __construct($timeout = 15)
    {
        $this->timeout = $timeout;
        $this->info = null;
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     *  Requests an http response using the GET method with the given URL.
     *
     * @param string $url    The URL address to send the request to.
     * @param bool   $ssl    If true, verifying the peer's certificate.
     * @param array  $header The keys and values to include in the http header.
     *
     * @return bool|string true on success or false on failure.
     *                     However, if the CURLOPT_RETURNTRANSFER option is set,
     *                     it will return the result on success, false on failure.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.1
     * @see https://php.net/manual/en/function.curl-exec.php
     */
    public function GET($url, $ssl = false, $header = [])
    {
        $this->rest = curl_init();

        curl_setopt($this->rest, CURLOPT_URL, $url);
        curl_setopt($this->rest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->rest, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($this->rest, CURLOPT_TIMEOUT, $this->timeout);

        if (count($header) > 0) {
            curl_setopt($this->rest, CURLOPT_HTTPHEADER, $header);
        }

        $returnVal = curl_exec($this->rest);
        $this->info = curl_getinfo($this->rest);
        curl_close($this->rest);

        return $returnVal;
    }

    /**
     *  Requests an HTTP response using the POST method with the given URL
     *  and data.
     *
     * @param string $url    The url address to send the request to.
     * @param array  $data   The data to attach to the request.
     * @param bool   $ssl    If true, verifying the peer's certificate.
     * @param array  $header The keys and values to include in the http header.
     *
     * @return bool|string true on success or false on failure.
     *                     However, if the CURLOPT_RETURNTRANSFER option is set,
     *                     it will return the result on success, false on failure.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.3
     * @see https://php.net/manual/en/function.curl-exec.php
     */
    public function POST($url, $data = [], $ssl = false, $header = [])
    {
        $this->rest = curl_init();

        curl_setopt($this->rest, CURLOPT_URL, $url);
        curl_setopt($this->rest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->rest, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($this->rest, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->rest, CURLOPT_POST, true);
        curl_setopt($this->rest, CURLOPT_POSTFIELDS, $data);

        if (is_array($data)) {
            curl_setopt($this->rest, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($this->rest, CURLOPT_POSTFIELDS, $data);
        }

        if (count($header) > 0) {
            curl_setopt($this->rest, CURLOPT_HTTPHEADER, $header);
        }

        $returnVal = curl_exec($this->rest);
        $this->info = curl_getinfo($this->rest);
        curl_close($this->rest);

        return $returnVal;
    }

    /**
     * Execute command via shell and return the complete output as a string.
     *
     * @param string $curl_string A command to execute.
     *
     * @return null|string The output from the executed command
     *                     or NULL if an error occurred or the command produces no output.
     *
     * @see https://php.net/manual/en/function.shell-exec.php
     */
    public function WITHCURL($curl_string)
    {
        return shell_exec($curl_string);
    }

    /**
     * Returns information regarding a last HTTP transfer.
     *
     * @return mixed information of HTTP transfer.
     *
     * @see https://php.net/manual/en/function.curl-getinfo.php
     */
    public function INFO()
    {
        return $this->info;
    }

    /**
     * Stringify given data.
     *
     * @param mixed $datas Data to convert to human readable.
     */
    public function DataToString($datas)
    {
        $returnStr = '';

        if (gettype($datas) == 'array' && count($datas) > 0) {
            $conStr = '';

            foreach ($datas as $key => $value) {
                $returnStr .= $conStr . $key . '=' . $value;
                $conStr = '&';
            }
        }

        return $returnStr;
    }
}
