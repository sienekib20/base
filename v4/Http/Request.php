<?php

namespace Kib\Http;

use Kib\Anotation\Route;

class Request
{
    // Armazena todos os dados da requisição (GET, POST, etc.)
    protected $queryParams;
    protected $postParams;
    protected $serverParams;
    protected $cookies;
    protected $files;

    protected $requestData;
    protected $post;

    public function __construct($queryParams = [], $postParams = [], $serverParams = [], $cookies = [], $files = [])
    {
        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->serverParams = $serverParams;
        $this->cookies = $cookies;
        $this->post = $_POST ?? [];
        $this->files = $files;

        $this->setDataFromJson();
        $this->setRequestData();
    }

    public function setDataFromAjax()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->bind($_REQUEST);
        }
    }

    public function setDataFromJson()
    {
        if ($this->isJson()) {
            $input = file_get_contents('php://input');
            $methods = $this->serverParams()['REQUEST_METHOD'];
            if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
                $_REQUEST = json_decode($input, true) ?? [];
                $this->post = json_decode($input, true) ?? [];
                $this->postParams = json_decode($input, true) ?? [];
            } else {
                $this->queryParams = json_decode($input, true) ?? [];
            }
            $this->setRequestData();
        }
    }

    public function setRequestData()
    {
        // Combinar $_REQUEST e dados JSON, se houver
        $this->requestData = (object) array_merge(
            $_REQUEST,
            $this->postParams ?? $this->queryParams
        );
    }

    public function fromApi($env_api_url, $url, $params = [], $method = 'GET', $headers = [])
    {
        $ch = curl_init();

        if ($method == 'GET') {
            $queryString = http_build_query($params);
            $url = $queryString ? $url . '?' . $queryString : $url;
        } else if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $url = env($env_api_url) . $url;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    public function bind(array $bind)
    {
        if ($this->isGet()) {
            foreach ($bind as $key => $value) {
                if (is_string($key)) {
                    $_REQUEST[$key] = $value;
                }
            }
        }

        if ($this->isPost()) {
            foreach ($bind as $key => $value) {
                if (is_string($key)) {
                    $this->post[$key] = $value; // Ajuste para armazenar no array post
                    $_REQUEST[$key] = $value;  // Adicionar também ao $_REQUEST
                }
            }
        }
        $this->setRequestData();
    }

    /**
     * Captura a requisição atual e retorna uma instância da classe Request.
     */
    public static function capture()
    {
        return new static($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    /**
     * Retorna todos os parâmetros da requisição (GET, POST, etc.).
     */
    public function all()
    {
        return array_merge($this->queryParams, $this->postParams);
    }

    /**
     * Obtém um parâmetro específico da requisição.
     */
    public function input($key, $default = null)
    {
        return $this->queryParams[$key] ?? $this->postParams[$key] ?? $default;
    }

    /**
     * Verifica se um parâmetro existe na requisição.
     */
    public function has($key)
    {
        return isset($this->queryParams[$key]) || isset($this->postParams[$key]);
    }

    /**
     * Obtém o método HTTP da requisição (GET, POST, etc.).
     */
    public function method()
    {
        return $this->serverParams['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Retorna o IP do cliente.
     */
    public function ip()
    {
        return $this->serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Verifica se o método HTTP é POST.
     */
    public function isMethod($method)
    {
        return strtoupper($this->method()) === strtoupper($method);
    }

    /**
     * Retorna o cabeçalho de uma requisição.
     */
    public function header($key)
    {
        return $this->serverParams['HTTP_' . strtoupper(str_replace('-', '_', $key))] ?? null;
    }

    /**
     * Retorna todos os parâmetros de consulta (query) da requisição.
     */
    public function query()
    {
        return $this->queryParams;
    }

    /**
     * Retorna todos os parâmetros POST da requisição.
     */
    public function post($key)
    {
        return $this->postParams[$key] ?? null;
    }

    /**
     * Retorna os cookies da requisição.
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Retorna os arquivos enviados na requisição.
     */
    public function files()
    {
        return $this->files;
    }

    // Retorna os dados do servidor (como a URL completa)
    public function serverParams()
    {
        return $this->serverParams;
    }

    // Retorna o URI da requisição
    // public function uri()
    // {
    //     return $this->serverParams['REQUEST_URI'];
    // }

    public function uri()
    {
        $uri = $this->serverParams['REQUEST_URI'] ?? '/';

        // Remove tudo após o '#'
        if (strpos($uri, '#') !== false) {
            $uri = explode('#', $uri)[0];
        }

        // Remove os parâmetros de consulta, se necessário
        else if (strpos($uri, '?') !== false) {
            $uri = explode('?', $uri)[0];
        }



        return $uri;
    }

    public function path()
    {
        $routes = Route::all();

        $paths = explode("/", trim(str_replace(url_path(), '', $_SERVER['REQUEST_URI']), '/'));
        $array = [];
        foreach ($paths as $p) {
            //if (is_numeric($p) || empty($this->prefix_replace($routes, $p))) {
            if (is_numeric($p)) {
                continue;
            }
            $array[] = $p;
        }
        return $array;
    }

    public function hasPrefix()
    {
        $requestUri = trim(str_replace(url_path(), '', $_SERVER['REQUEST_URI']), '/');
        $path = explode("/", trim(str_replace(url_path(), '', $_SERVER['REQUEST_URI']), '/'));
        $savedPath = $path;

        foreach (Route::all() as $route) {
            if ($route->uri == "/$requestUri") {
                return !is_null($route->prefix) ? $route->prefix : '/';
            }
        }
        return '/';
    }

    public function prefix_replace($routes, $item)
    {
        foreach ($routes as $route) {
            if (strpos(ltrim($route->prefix, '/'), $item) === 0) {
                return '';
            }
        }
        return $item;
    }

    public function isGet()
    {
        return $this->serverParams['REQUEST_METHOD']  == 'GET';
    }

    public function isPost()
    {
        return $this->serverParams['REQUEST_METHOD'] == 'POST';
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function __get($name)
    {
        return $this->requestData->$name ?? null;
    }

    public function isSecure()
    {
        // Verifica se a requisição é segura
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    public function isHttps()
    {
        return $this->isSecure();
    }

    // Método adicional para verificar se a requisição é JSON
    public function isJson()
    {
        return isset($this->server['CONTENT_TYPE']) && strpos($this->server['CONTENT_TYPE'], 'application/json') !== false;
    }

    // Implementar jsonSerialize para customizar a serialização para JSON
    public function jsonSerialize(): mixed
    {
        return [
            'params' => $this->params,
            'files' => $this->files,
            'server' => $this->server,
            'post' => $this->post,
            'cookies' => $this->cookies,
            'env' => $this->env,
            'session' => $this->session,
            'requestData' => $this->requestData,
        ];
    }

    // Método sanitize para limpar o campo solicitado
    public function sanitize($key, $default = null)
    {
        $value = $this->post[$key] ?? $this->params[$key] ?? $default;

        if ($value) {
            // Remover tags HTML e caracteres especiais
            return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $default;
    }
}
