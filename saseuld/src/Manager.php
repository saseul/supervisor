<?php

namespace src;

class Manager
{
    private $application;

    public function __construct()
    {
        $this->_DefaultSetting();
        $this->_LoadClasses();

        $this->application = new Application();
    }

    public function _DefaultSetting()
    {
        date_default_timezone_set('Asia/Seoul');
        ini_set('memory_limit', '4G');
    }

    public function _LoadClasses()
    {
        spl_autoload_register(function ($class_name) {
            if (!class_exists($class_name) && preg_match('/^(src)/', $class_name)) {
                $class = str_replace('\\', '/', $class_name);
                require_once ROOT_DIR . '/' . $class . '.php';
            }
        });
    }

    public function Main()
    {
        $this->application->Main();
    }
}
