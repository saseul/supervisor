<?php

namespace src;

class API
{
    public $data = [];
    protected $display_params = true;
    protected $result = [];

    public function Call()
    {
        $this->Exec();
        $this->Success();
    }

    public function Exec()
    {
        $this->_init();
        $this->_process();
        $this->_end();
    }

    public function _init()
    {
    }

    public function _process()
    {
    }

    public function _end()
    {
    }

    public function Error403($msg = 'Forbidden')
    {
        $this->Fail(403, $msg);
    }

    public function Error404($msg = 'API Not Found')
    {
        $this->Fail(404, $msg);
    }

    public function Error($msg = 'Error', $code = 999)
    {
        $this->Fail($code, $msg);
    }

    protected function Success()
    {
        $this->result['status'] = 'success';
        $this->result['data'] = $this->data;

        if ($this->display_params === true) {
            $this->result['params'] = $_REQUEST;
        }

        $this->View();
    }

    protected function Fail($code, $msg = '')
    {
        $this->result['status'] = 'fail';
        $this->result['code'] = $code;
        $this->result['msg'] = $msg;

        $this->View();
    }

    protected function View()
    {
        try {
            header('Content-Type: application/json; charset=utf-8;');
        } catch (\Exception $e) {
            echo $e . PHP_EOL . PHP_EOL;
        }
        echo json_encode($this->result);
        exit();
    }
}
