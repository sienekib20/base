<?php

namespace Kib\Anotation;

use App\Http\Exceptions\ExceptionHandler;
use Kib\Http\Request;

class Route
{
    protected static $routes = [];
    protected static $names = [];
    protected static $middleware = null;

    public static function __callStatic($name, $arguments)
    {
        // Verifica se o método é válido (get, post, put, delete, any)
        $validMethods = ['get', 'post', 'put', 'delete', 'any'];

        if (in_array(strtolower($name), $validMethods)) {
            // O primeiro argumento é o URI e o segundo é a ação
            $method = strtolower($name);
            $uri = $arguments[0];
            $action = $arguments[1];

            // Adiciona a rota
            self::add($method, $uri, $action);
        }
    }

    public static function restrictedRoutes($middle, $callback)
    {
        self::$middleware = $middle;
        if (!is_callable($callback)) {
            die('O Segundo parâmetro de restrictedRoutes deve ser um callable');
        }
        $callback();
        self::$middleware = null;
    }

    // Registra uma rota
    public static function add($method, $uri, $action)
    {
        self::$routes[] = (object) [
            'method' => strtoupper($method),
            'uri' => $uri,
            'action' => $action,
            'middleware' => self::$middleware,
        ];
    }

    // Resolve uma rota com base na requisição
    public static function resolve(Request $request, $uri)
    {
        foreach (self::$routes as $route) {
            if (strpos($request->method(), $route->method) === 0) {

                $pathPattern = preg_replace('/\/{(\w+)}/', '/(?<$1>.*?)', $route->uri);
                //echo '<pre>'; print_r($pathPattern); exit;
                if (preg_match('#^' . $pathPattern . '$#', $uri, $matches)) {
                    array_shift($matches);
                    $request->bind($matches);

                    return [
                        'action' => $route->action,
                        'middleware' => $route->middleware
                    ];
                }
            }
        }
    }

    // Retorna todas as rotas registradas
    public static function all()
    {
        return self::$routes;
    }

    public static function name($uri, $name)
    {
        self::$names[$name] = $uri;
    }

    // Obtém o nome da rota
    public static function getName($name)
    {
        return isset(self::$names[$name]) ? self::$names[$name] : null;
    }

    public static function dispatcher(
        array $action,
        array $middlewares = [],
        Request $request,
        ExceptionHandler $e
    ): void {
        if (!is_null($action['middleware'])) {
            $middleware = $action['middleware'];
            // foreach ($middlewares as $middleware) {
            // }

            $middlewareInstance = new $middleware();
            $middlewareInstance->handle($request);
        }
        try {
            list($controller, $method) = $action['action'];
            if (!class_exists($controller)) {
                throw new \Exception('Class not found: ' . $controller);
            }
            $controllerInstance = new $controller();
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception('Method not found: ' . $method);
            }

            $controllerInstance->$method($request);

            return;
        } catch (\Exception $ex) {
            $e->handle($ex);
        }
    }
}
