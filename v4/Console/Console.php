<?php

namespace Kib\Console;

use Factory\seeders\DatabaseSeeder;

class Console
{
    public static function init($args)
    {
        if (php_sapi_name() != 'cli') {
            self::printError('Ambiente de Console Invalido');
            exit(1);
        }

        array_shift($args);

        if (empty($args)) {
            self::printError("Sem Argumentos válidos");
            self::printUsage();
            exit(1);
        }

        $call = array_shift($args);

        try {
            static::__callStatic($call, $args);
        } catch (\Exception $ex) {
            self::printError($ex->getMessage());
            exit(1);
        }
    }

    public static function db($arguments)
    {
        $set = array_shift($arguments);

        if ($set != "seed" && $set != "migrate") {
            self::printError("Argumentos inválidos");
            self::printDbUsage();
            exit(1);
        }

        if ($set == 'seed') {
            $models = glob(abs_path() . "model/*");
        }

        $next = array_shift($arguments);

        if ($next == '-a') {
            (new DatabaseSeeder())->run();
            self::printSuccess("Seeding completed successfully");
        } else if ($next == '-t' && empty($arguments)) {
            self::printError("Para a flag -t precisas passar o nome depois: -t nomeTabela");
            exit(1);
        }

        return;
    }

    public static function __callStatic($name, $arguments)
    {
        if (!method_exists(__CLASS__, $name)) {
            self::printError("Argumentos inválidos");
            self::printUsage();
            exit(1);
        }
        return self::$name($arguments);
    }

    private static function printUsage()
    {
        echo "Tente: " . PHP_EOL;
        echo "- db" . PHP_EOL;
        echo "- create" . PHP_EOL;
        echo "- migration" . PHP_EOL;
    }

    private static function printDbUsage()
    {
        echo "Tente: " . PHP_EOL;
        echo "- db seed -flag" . PHP_EOL;
        echo "- db migrate -flag" . PHP_EOL;
        echo "* As flags podem ser: -a (para tudo) ou -t (passar o nome específico da tabela)" . PHP_EOL;
    }

    private static function printError($message)
    {
        echo "\033[31m" . $message . "\033[0m" . PHP_EOL;
    }

    private static function printSuccess($message)
    {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
    }
}
