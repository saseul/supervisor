<?php

namespace src;

use src\Util\ScriptFinder;

class Application
{
    private $argv;
    private $name;
    private $script_directory = 'Script';

    private $scripts;

    public function __construct($argv, $name)
    {
        $this->argv = $argv;
        $this->name = $name;
        $this->_DefaultSetting();
        $this->_LoadClasses();
        $this->_Route();
    }

    public function _DefaultSetting()
    {
        date_default_timezone_set('Asia/Seoul');
    }

    public function _LoadClasses()
    {
        spl_autoload_register(function ($class_name) {
            if (!class_exists($class_name)) {
                $class = str_replace('\\', '/', $class_name);
                require_once ROOT_DIR . '/' . $class . '.php';
            }
        });
    }

    public function _Route()
    {
        $this->GetScripts();

        if (!is_array($this->scripts) || $this->scripts === []) {
            $this->ShowAllScripts();

            return;
        }

        if (is_array($this->scripts) && $this->scripts !== []) {
            $this->ExecScripts($this->scripts);

            return;
        }
    }

    public function GetScripts()
    {
        $script_dir = $this->script_directory;

        if (count($this->argv) === 1) {
            return;
        }

        $script_file = ROOT_DIR . '/src/' . $script_dir . '/' . $this->argv[1];

        if (!preg_match('/\.php$/', $script_file)) {
            $script_file = $script_file . '.php';
        }

        if (preg_match('/\/\*?$/', $script_file)) {
            return;
        }

        if (file_exists($script_file)) {
            $this->scripts[] = $this->argv[1];
        }
    }

    public function ShowAllScripts()
    {
        echo PHP_EOL;
        echo 'You can run like this ' . PHP_EOL;
        echo ' $ ./' . $this->name . ' * ' . PHP_EOL;
        echo ' $ ./' . $this->name . ' ExampleTest';
        echo PHP_EOL;
        echo PHP_EOL;
        echo 'This is script lists. ' . PHP_EOL;

        $script_dir = $this->script_directory;
        $scripts = ScriptFinder::GetFiles(ROOT_DIR . '/src/' . $script_dir, ROOT_DIR . '/src/' . $script_dir);
        $scripts = preg_replace('/\.php$/', '', $scripts);

        foreach ($scripts as $script) {
            echo ' - ' . $script . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function ExecScripts($scripts)
    {
        foreach ($scripts as $script) {
            $script = $this->script_directory . '/' . $script;
            $script = preg_replace('/\.php$/', '', $script);
            $script = preg_replace('/\//', '\\', $script);
            $script = __NAMESPACE__ . '\\' . $script;
            $test = new $script();
            $test->Call();
        }
    }
}
