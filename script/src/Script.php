<?php

namespace src;

use src\Util\Logger;

class Script
{
    protected $data = [];

    public function Call()
    {
        $this->_awake();
        $this->_process();
        $this->_end();
        $this->_result();
    }

    public function Exec()
    {
        $this->_awake();
        $this->_process();
        $this->_end();
    }

    public function _awake()
    {
    }

    public function _process()
    {
    }

    public function _end()
    {
    }

    public function Error($msg = 'Error')
    {
        Logger::Log('Error : ');
        Logger::Log($msg, true);
    }

    protected function _result()
    {
        if ($this->data !== []) {
            Logger::Log($this->data, true);
        }
    }
}
