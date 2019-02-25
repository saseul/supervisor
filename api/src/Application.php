<?php

namespace src;

class Application
{
    private $handler = 'main';
    private $route = 200;

    public function __construct()
    {
        $this->_DefaultSetting();
        $this->_LoadClasses();
        $this->_Route();
    }

    public function _DefaultSetting()
    {
        date_default_timezone_set('Asia/Seoul');
        session_start();
        header('Access-Control-Allow-Origin: *');
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

    public function _Route()
    {
        if (isset($_REQUEST['handler'])) {
            $this->handler = $_REQUEST['handler'];
        }

        $dir = $this->FindDir("src/API/{$this->handler}.php");

        if ($this->route === 200 && $dir === false) {
            $this->route = 404;
        }

        $class = preg_replace('/\\/{1,}/', '\\', $dir);
        $class = preg_replace('/.php$/', '', $class);

        if ($this->route === 200) {
            $api = new $class();
        } else {
            $api = new API();
        }

        switch ($this->route) {
            case 403:
                $api->Error403();

                break;
            case 404:
                $api->Error404();

                break;
            case 200:
                $api->Call();

                break;
            default:
                $api->Error();

                break;
        }
    }

    public function FindDir($full_dir)
    {
        $full_dir = "./{$full_dir}";
        $dir = preg_replace('/\\/{2,}/', '/', $full_dir);
        $dir = explode('/', $dir);

        if (count($dir) > 1) {
            $parent = $dir[0];

            for ($i = 1; $i < count($dir); $i++) {
                $find = $dir[$i];

                if ($this->FindFile($parent, $find) === false) {
                    return false;
                }

                $parent = $this->FindFile($parent, $find);

                if (strtolower($parent) == strtolower($full_dir)) {
                    return substr($parent, 2);
                }
            }
        }

        return false;
    }

    public function FindFile($parent_dir, $search_str)
    {
        if (file_exists($parent_dir)) {
            $d = scandir($parent_dir);
            $lower_d = '';

            foreach ($d as $dir) {
                if (strtolower($search_str) === strtolower($dir)) {
                    $lower_d = $dir;

                    break;
                }
            }

            return "{$parent_dir}/{$lower_d}";
        }

        return false;
    }
}
