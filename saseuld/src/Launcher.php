<?php

namespace src;

class Launcher
{
    private $argv;
    private $name;
    private $script_directory = 'Scripts';

    private $m_test_scripts;

    public function __construct($argv, $name)
    {
        $this->argv = $argv;
        $this->name = $name;
        $this->_DefaultSetting();
        $this->_LoadClasses();
        $this->Main();
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

    public function Main()
    {
        $this->GetTestScripts();

        if (!is_array($this->m_test_scripts) || $this->m_test_scripts === []) {
            $this->ShowAllScripts();

            return;
        }

        if (is_array($this->m_test_scripts) && $this->m_test_scripts !== []) {
            $this->ExecScript($this->m_test_scripts);

            return;
        }
    }

    public function GetTestScripts()
    {
        $script_dir = $this->script_directory;

        if (count($this->argv) === 1) {
            return;
        }

        $script_file_pattern1 = ROOT_DIR . '/src/' . $script_dir . '/' . $this->argv[1];
        $script_file_pattern2 = ROOT_DIR . '/src/' . $script_dir . '/' . $this->argv[1] . '.php';

        if (preg_match('/\/\*?$/', $script_file_pattern1)) {
            return;
        }

        if (file_exists($script_file_pattern1) || file_exists($script_file_pattern2)) {
            $this->m_test_scripts[] = $this->argv[1];
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
        $scripts = $this->find_scripts(ROOT_DIR . '/src/' . $script_dir, ROOT_DIR . '/src/' . $script_dir);
        $scripts = preg_replace('/\.php$/', '', $scripts);

        foreach ($scripts as $script) {
            echo ' - ' . $script . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function ExecScript($scripts)
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

    public function find_scripts($full_dir, $del_dir = '')
    {
        $d = scandir($full_dir);
        $scripts = [];

        foreach ($d as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            if (is_dir($full_dir . '/' . $dir)) {
                $c_scripts = $this->find_scripts($full_dir . '/' . $dir, $del_dir);
                $scripts = array_merge($scripts, $c_scripts);
            } else {
                $escaped_del_dir = preg_replace('/\//', '\\\/', $del_dir);
                $scripts[] = preg_replace('/^' . $escaped_del_dir . '\//', '', $full_dir . '/' . $dir);
            }
        }

        return $scripts;
    }
}
