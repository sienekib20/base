<?php

use App\Http\Middlewares\AuthMiddleware;
use App\Http\Exceptions\ExceptionHandler;
use Kib\Anotation\Route;
use Kib\Http\Request;
use Kib\Support\Env;

class Application
{
    private $basePath;
    private $routes = [];
    private $middleware = [];
    private $exceptions = [];
    protected $exceptionHandler;
    private $request;


    public static function configure($basePath)
    {
        $instance = new self();
        $instance->basePath = $basePath;
        $instance->exceptionHandler = new ExceptionHandler();
        $instance->registerBasePathFunction();
        return $instance;
    }

    /**
     * Registra a função global base_path para acessar o caminho base da aplicação.
     */
    private function registerBasePathFunction()
    {
        if (!function_exists('base_path')) {
            function base_path($path = '')
            {
                return dirname(__DIR__, 1) . ($path ? DIRECTORY_SEPARATOR . $path : '');
            }
        }
    }

    public function withRouting($web, $commands, $health)
    {
        $this->routes = [
            'web' => $web,
            'commands' => $commands,
            'health' => $health
        ];
        require $this->routes['web'];
        require base_path() . "/v4/Support/helpers.php";

        $env = Env::createImmutable(base_path() . '/');
        $env->load();

        session()->start();

        return $this;
    }

    public function withMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function withExceptions($exceptions)
    {
        $this->exceptions[] = $exceptions;
        return $this;
    }

    public function getBasePath()
    {
        $paths = explode(DIRECTORY_SEPARATOR, $this->basePath);

        return ltrim(end($paths), '/');
    }



    public function handleRequest(Request $request)
    {
        try {
            // Obtém o REQUEST_URI e remove o basePath da URL
            $uri = parse_url($request->serverParams()['REQUEST_URI'], PHP_URL_PATH);

            // Remover a basePath da URL
            $relativeUri = str_replace($this->getBasePath(), '', ltrim($uri, '/'));


            // Ajusta o nome da pasta ou resolve qualquer diferença
            $relativeUri = ltrim($relativeUri, '/');  // Remove a barra inicial se houver
            $relativeUri = '/' . $relativeUri;  // Garante que a URI comece com uma barra



            // Resolve a rota com a URI corrigida
            $route = Route::resolve($request, $relativeUri);

            if ($route) {
                // Chama a função de controlador associada à rota
                Route::dispatcher($route, $this->middleware, $request, $this->exceptionHandler);
            } else {
                throw new \Exception("Rota não encontrada.");
            }
        } catch (\Exception $e) {
            // Tratar exceções usando o handler
            $this->exceptionHandler->handle($e);
        }
    }



    public function handleException($exception)
    {
        foreach ($this->exceptions as $handler) {
            $handler($exception);
        }
    }

    public function create(Request $request)
    {
        $this->request = $request;
        //echo "Aplicação configurada com sucesso!\n";
        //echo "Base Path: " . $this->getBasePath() . "\n";
        return $this;
    }

    // Inicia a aplicação
    public function start()
    {
        $this->handleRequest($this->request);
    }
}
