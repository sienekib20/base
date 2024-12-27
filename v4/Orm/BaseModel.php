<?php

namespace Kib\Orm;

use Kib\Orm\DB;

class BaseModel
{
    protected static $db;

    protected static $classAssociada;

    protected static $table;

    protected static $fillable = [];

    protected static $instance = null;

    public function __construct()
    {
        self::$db = (new DB())->connect();
        self::$classAssociada = get_called_class();

        self::inicializarModelo();
    }

    private static function inicializarModelo()
    {
        $vars = get_class_vars(self::$classAssociada);
        foreach ($vars as $key => $value) {
            if ($key == 'table' || $key == 'tabela') {
                if (empty($value)) {
                    die('Nome da tabela obrigatorio no Model: ' . self::$classAssociada);
                }
                self::$table = $value;
            }

            if ($key == 'fillable') {
                self::$fillable = $value ?? [];
            }
        }
    }

    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function all()
    {
        self::getInstance();
        try {
            return DB::table(self::$table)->get();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public static function query($sql, $params = [])
    {
        self::getInstance();
        try {

            return DB::query($sql, $params);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public  static function find($id)
    {
        self::getInstance();
        try {

            return DB::table(self::$table)->find($id);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public static function update($where, $field, $binding)
    {
        self::getInstance();

        if (!is_array($binding)) {
            die("Parametro do Create deve ser um Array");
        }

        return DB::table(self::$table)->update($where, $field, $binding);
    }

    public static function create($fields)
    {
        self::getInstance();

        if (!is_array($fields)) {
            die("Parametro do Create deve ser um Array");
        }

        return DB::table(self::$table)->insert($fields);
    }

    public static function delete($where, $val)
    {
        self::getInstance();
        try {

            return DB::table(self::$table)->delete($where, $val);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function lastInserted()
    {
        self::getInstance();
    }

    public static function attempt()
    {
        self::getInstance();
    }
}
