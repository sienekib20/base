<?php

use App\Http\Controllers\Controller;
use Kib\Anotation\Route;
use Kib\Http\Request;
use Kib\Http\Response;
use Kib\Http\Redirect;
use Kib\Sessions\SessionManager;

if (!function_exists('config_path')) {
    function config_path()
    {
        return base_path() . "/config";
    }
}

if (!function_exists('config')) {

    function config($key, $default = null)
    {
        $parts = explode('.', $key);
        $file = $parts[0];
        $configKey = $parts[1] ?? null;

        $path = config_path() . "/$file.php";

        if (!file_exists($path)) {
            return $default;
        }

        $config = include $path;

        if (!$configKey) {
            return $config;
        }

        return $config[$configKey] ?? $default;
    }
}

if (!function_exists("request")) {
    function request()
    {
        return (new Request());
    }
}

if (!function_exists("response")) {
    function response()
    {
        return (new Response());
    }
}

if (!function_exists("redirect")) {
    function redirect()
    {
        return (new Redirect());
    }
}


if (!function_exists('session')) {
    function session()
    {
        return SessionManager::getInstance();
    }
}


if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('view_path')) {
    function view_path()
    {
        return base_path() . "/views/";
    }
}


if (!function_exists('storage_path')) {
    function storage_path()
    {
        return base_path() . '/storage/';
    }
}

if (!function_exists('url_path')) {
    function url_path()
    {
        $parts = explode(DIRECTORY_SEPARATOR, base_path());
        return end($parts);
    }
}

if (!function_exists('asset_path')) {
    function asset_path()
    {
        return  "/" . url_path() . '/assets';
    }
}



if (!function_exists('storage')) {
    function storage($arquivo)
    {
        $hasAssetDir = explode("/", $arquivo)[0];
        $assetRoot = "/";
        $assetRoot .=
            $hasAssetDir == "storage"
            ? "/$arquivo"
            : asset_path() . "/storage/$arquivo";

        return $assetRoot;
    }
}

if (!function_exists('controllerInstance')) {
    function controllerInstance()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new Controller();
        }

        return $instance;
    }
}

if (!function_exists('view_component')) {
    function view_component($view, $data = [])
    {
        return controllerInstance()->view_component($view, $data);
    }
}

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        return controllerInstance()->view($view, $data);
    }
}


if (!function_exists("asset")) {
    function asset($arquivo)
    {
        $hasAssetDir = explode("/", $arquivo)[0];
        $assetRoot =
            $hasAssetDir == "assets"
            ?  url_path() . "/$arquivo"
            : asset_path() . "/$arquivo";
        $assetRoot .= "?v=" . time();
        return $assetRoot;
    }
}


if (!function_exists("url")) {
    function url($route, $params = null)
    {

        /*if (!($namedRoute = Route::getRouteByName($route)) == null) {

            $endpoint = $namedRoute;
            $params = is_array($params) ? $params : [$params];
            $countParams = 0;
            foreach ($params as $key => $value) {
                $endpoint .= $value . $countParams == count($params) - 1 ? "" : "/";
                $countParams += 1;
            }
            return $endpoint;
        }*/

        if (empty($route)) {
            throw new \Exception("Url can't be empty. Provide a valid Route!");
        }

        if ($route == "/") {
            return base_path();
        }
        $route = str_replace(".", "/", $route);

        if (!is_null($params)) {
            $params = is_array($params) ? $params : [$params];
            $countParams = 0;
            foreach ($params as $key => $value) {
                $route .= $value . $countParams == count($params) - 1 ? "" : "/";
                $countParams += 1;
            }
        }

        return base_path() . "/$route";
    }
}
