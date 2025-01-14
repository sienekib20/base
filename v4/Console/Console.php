<?php

namespace Kib\Console;

use Factory\seeders\DatabaseSeeder;
use Kib\Orm\DB;

class Console
{
    private static $basePath = null;

    public static function init($path, $args)
    {
        self::$basePath = $path;

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
            $models = glob(self::$basePath . "model/*");
            $next = array_shift($arguments);

            if ($next == '-a') {
                (new DatabaseSeeder())->run();
                self::printSuccess("Seeding completed successfully");
            } else if ($next == '-t' && empty($arguments)) {
                self::printError("Para a flag -t precisas passar o nome depois: -t nomeTabela");
                exit(1);
            }
        } else if ($set == 'migrate') {
            session_start();
            unset($_SESSION);
            session_destroy();
            $migrations = scandir(self::$basePath . "database/migrations");
            $next = array_shift($arguments);
            $arrayInstance = [];
            $arrayNames = [];

            if ($next == '-a') {

                foreach ($migrations as $migration) {
                    if ($migration == '.' || $migration == '..') {
                        continue;
                    }
                    require_once self::$basePath . 'database/migrations/' . $migration;
                    $arrayNames[] = $migration;
                    $explodes = explode('m_', $migration)[1];
                    $nameMigration = str_replace('_table.php', '', $explodes);
                    $nameMigration = "Create" . ucfirst($nameMigration) . "Table";
                    if (!class_exists($nameMigration)) {
                        die('A class da migration n encontrada: ' . $nameMigration);
                    }
                    $migrationInstance = new $nameMigration();
                    $arrayInstance[] = $migrationInstance;
                    DB::checkConnection();
                    DB::disableForeignKeyChecks();
                    $migrationInstance->down();
                }
                self::printError("Tabelas removidas com sucesso!");
                sleep(1.5);
                $count = 0;
                foreach ($arrayInstance as  $instance) {
                    $instance->up();
                    sleep(1.5);
                    self::printSuccess("Tabela " . $arrayNames[$count++] . " criada com sucesso.");
                }
            } else if ($next == '-t' && empty($arguments)) {
                self::printError("Para a flag -t precisas passar o nome depois: -t nomeTabela");
                exit(1);
            } else {
                self::printError("Argumentos inválidos");
                self::printError("Coloque uma flag antes de continuar -a ou -t");
                exit(1);
            }
        }


        return;
    }

    public static function create($arguments)
    {
        $set = array_shift($arguments);

        if ($set != "migration" && $set != "controller") {
            self::printError("Argumentos inválidos");
            self::printCreateUsage();
            exit(1);
        }

        if (strpos($set, 'migration') === 0) {
            $next = array_shift($arguments);
            if (empty($next) || is_null($next) || strpos($next, '-name') !== 0) {
                self::printError("Argumentos inválidos");
                self::printCreateUsage();
                exit(1);
            }
            $next = array_shift($arguments);
            if (empty($next) || is_null($next)) {
                self::printError("Argumentos inválidos");
                self::printCreateUsage();
                exit(1);
            }
            //self::$basePath
            $file = __DIR__ . '/squeleton/migration';
            $content = file_get_contents($file);
            $content = str_replace('\\TableClass\\', ucfirst($next), $content);

            // $tableName = str_ends_with($next, 's') ? $next : $next . 's';
            $tableName = $next;
            $content = str_replace('\\TableName\\', $tableName, $content);

            $tableId = str_ends_with($next, 's')
                ? substr($next, 0, strlen($next) - 1)
                : $next;
            $content = str_replace('\\TableId\\', $tableId, $content);

            $path = self::$basePath . 'database/migrations/' . date('Y_m_d_is') . '_m_' . $tableId . '_table.php';

            if (file_put_contents($path, $content)) {
                self::printSuccess("Migration $tableId criado com sucesso!");
            } else {
                self::printError("Algo deu errado ao tentar criar a migration $tableId");
            }
        }
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

    private static function printCreateUsage()
    {
        echo "Tente: " . PHP_EOL;
        echo "- create migration -name validMigrationName" . PHP_EOL;
        echo "- create controller -name validControllerName" . PHP_EOL;
        echo "- create model -name validModelName" . PHP_EOL;
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
