<?php

namespace Kib\Anotation;

use App\Http\Exceptions\ExceptionHandler;
use Kib\Http\Request;

class Route
{
    protected static $routes = [];
    protected static $names = [];
    protected static $middleware = null;
    protected static $prefix = null;
    protected static $prefixArr = [];

    // Handles static method calls for valid HTTP methods (GET, POST, PUT, DELETE, ANY)
    public static function __callStatic($name, $arguments)
    {
        $validMethods = ['get', 'post', 'put', 'delete', 'any'];
        $method = strtolower($name);

        if (in_array($method, $validMethods)) {
            $uri = $arguments[0];
            $action = $arguments[1];
            self::addRoute($method, $uri, $action);
        }
    }

    // Returns all registered routes
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    // Defines restricted routes with middleware
    public static function restrictedRoutes($middleware, $callback)
    {
        self::$middleware = $middleware;

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('O segundo parâmetro de restrictedRoutes deve ser um callable');
        }

        $callback();
        self::$middleware = null;
    }

    // Defines routes with a prefix
    public static function prefixRoutes($prefix, $callback)
    {
        self::$prefix = $prefix;

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('O segundo parâmetro de prefixRoutes deve ser um callable');
        }

        $callback();
        self::$prefix = null;
    }

    // Registers a route with a method, URI, action, middleware, and prefix
    public static function addRoute($method, $uri, $action)
    {
        $uri = '/' . ltrim($uri, '/');
        $prefix = self::$prefix ? '/' . ltrim(self::$prefix, '/') : '';

        self::$routes[] = (object) [
            'method' => strtoupper($method),
            'uri' => $prefix . $uri,
            'action' => $action,
            'middleware' => self::$middleware,
            'prefix' => self::$prefix,
        ];

        error_log("Rota registrada: " . $method . " " . $prefix . $uri);
    }


    // Resolves a route based on the request URI and method
    public static function resolve(Request $request, string $uri)
    {
        foreach (self::$routes as $route) {
            // Aplica o prefixo ao padrão da URI
            $pathPattern = preg_replace('/\/{(\w+)}/', '/(?<$1>[^/]+)', self::resolvePrefix(self::$routes, $route->uri));
            $pathPattern = $pathPattern == '/' ? '/' : rtrim($pathPattern, '/');

            $matchUri = $uri === '/' ? '/' : rtrim($uri, '/');

            if (
                preg_match('#^' . $pathPattern . '$#', $matchUri, $matches) &&
                strtoupper($request->method()) === $route->method
            ) {
                array_shift($matches);
                $request->bind($matches);

                return [
                    'action' => $route->action,
                    'middleware' => $route->middleware,
                ];
            }
        }
        return null; // Rota não encontrada
    }


    // Resolves the route prefix if defined
    private static function resolvePrefix($routes, $uri): string
    {
        if ($uri === '/') {
            return $uri;
        }

        foreach ($routes as $route) {
            if (!is_null($route->prefix) && $route->uri == $uri) {
                if ($uri === '/') {
                    return rtrim($uri, '/');
                }
            }
        }

        return $uri;
    }

    // Returns all registered routes
    public static function all()
    {
        return self::$routes;
    }

    // Assigns a name to a route
    public static function name($uri, $name)
    {
        self::$names[$name] = $uri;
    }

    // Retrieves a route name by its name
    public static function getName($name)
    {
        return self::$names[$name] ?? null;
    }

    // Dispatches the route action, applies middleware, and handles exceptions
    public static function dispatcher(
        array $action,
        array $middlewares = [],
        Request $request,
        ExceptionHandler $e
    ): void {
        if (!is_null($action['middleware'])) {
            $middlewareInstance = new $action['middleware']();
            $middlewareInstance->handle($request);
        }


        try {
            list($controller, $method) = $action['action'];

            if (!class_exists($controller)) {
                throw new \Exception('Classe não encontrada: ' . $controller);
            }

            $controllerInstance = new $controller();

            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception('Método não encontrado: ' . $method);
            }

            $controllerInstance->$method($request);
        } catch (\Exception $ex) {
            $e->handle($ex);
        }
    }
}
