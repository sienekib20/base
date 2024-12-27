<?php

namespace Kib\Support;


class Env
{

    private static $instance = null;

    /**
     * Caminho para o arquivo .env
     *
     * @var string
     */
    protected $path;

    /**
     * Array associativo para armazenar as variáveis de ambiente
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Construtor da classe Env
     *
     * @param string $path Caminho para o arquivo .env
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function createImmutable($path)
    {
        if (static::$instance == null) {
            static::$instance = new Env($path . "/.env");
        }

        return static::$instance;
    }

    /**
     * Carrega e parseia o arquivo .env
     *
     * @return void
     */
    public function load()
    {
        if (!file_exists($this->path)) {
            throw new \Exception("Arquivo .env não encontrado no caminho: {$this->path}");
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Dividir chave e valor
            $parts = explode('=', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Remover aspas se existirem
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
            ) {
                $value = substr($value, 1, -1);
            }

            // Substituir variáveis de ambiente dentro do valor (por exemplo, ${APP_ENV})
            $value = $this->parseValue($value);

            $this->variables[$key] = $value;

            // Definir as variáveis de ambiente
            $this->setEnvironmentVariable($key, $value);
        }
    }

    /**
     * Substitui referências a outras variáveis de ambiente no valor
     *
     * @param string $value
     * @return string
     */
    protected function parseValue($value)
    {
        return preg_replace_callback('/\${(\w+)}/', function ($matches) {
            $key = $matches[1];
            return isset($this->variables[$key]) ? $this->variables[$key] : '';
        }, $value);
    }

    /**
     * Define a variável de ambiente no PHP
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setEnvironmentVariable($key, $value)
    {
        // Definir no ambiente do PHP
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Recupera o valor de uma variável de ambiente
     *
     * @param string $key
     * @param mixed $default Valor padrão caso a variável não exista
     * @return string|mixed
     */
    public function get($key, $default = null)
    {
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        }

        // Tenta recuperar de getenv, $_ENV e $_SERVER
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
        }

        return $value;
    }

    /**
     * Verifica se uma variável de ambiente está definida
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->variables[$key]) || getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key]);
    }

    /**
     * Define uma variável de ambiente manualmente
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->variables[$key] = $value;
        $this->setEnvironmentVariable($key, $value);
    }
}
