<?php

namespace Kib\Orm;

class DB
{
    private static $conn = null;
    private static $table = null;
    private static $select = "*";
    private static $where_field = null;
    private static $where_value = null;

    // Método para conectar ao banco de dados
    public function connect()
    {
        if (self::$conn == null) {
            try {
                self::$conn = new \PDO(
                    'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
                    env('DB_USERNAME'),
                    env('DB_PASSWORD')
                );
                self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                die('Erro de Conexão: ' . $e->getMessage());
            }
        }
        return self::$conn;
    }

    public static function checkConnection()
    {
        if (self::$conn == null) {
            (new static())->connect();
        }
    }

    // Método para definir a tabela a ser utilizada
    public static function table($table)
    {
        self::checkConnection();
        self::$table = $table;
        return new self();
    }

    // Método para inserir dados na tabela
    public static function insert($bind)
    {
        self::checkConnection();
        try {
            if (!is_array($bind)) {
                die('O parâmetro do Insert deve ser um Array');
            }

            $columns = implode(', ', array_keys($bind));
            $placeholders = rtrim(str_repeat('?, ', count($bind)), ', ');

            $sql = "INSERT INTO " . self::$table . " ($columns) VALUES ($placeholders)";

            $stmt = self::$conn->prepare($sql);
            $stmt->execute(array_values($bind));

            return self::lastInsertedId();
        } catch (\PDOException $e) {
            die('Erro ao inserir: ' . $e->getMessage());
        }
    }

    public static function insertMultiple($bind)
    {
        self::checkConnection();

        try {
            // Desativar verificação de chaves estrangeiras
            self::disableForeignKeyChecks();

            // Limpa a tabela
            $truncateSql = "TRUNCATE TABLE " . self::$table;
            self::$conn->exec($truncateSql);
            // Verifica se todos os itens são arrays
            foreach ($bind as $params) {
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('Cada item deve ser um array.');
                }
            }

            // Prepara inserções
            $columns = implode(', ', array_keys($bind[0]));
            $placeholders = rtrim(str_repeat('?, ', count($bind[0])), ', ');
            $sql = "INSERT INTO " . self::$table . " ($columns) VALUES ($placeholders)";
            $stmt = self::$conn->prepare($sql);

            // Executa inserções
            foreach ($bind as $params) {
                $stmt->execute(array_values($params));
            }
            // Reativar verificação de chaves estrangeiras
            self::enableForeignKeyChecks();

            return self::$conn->lastInsertId();
        } catch (\PDOException $e) {
            die('Erro de inserção: ' . $e->getMessage());
        } catch (\InvalidArgumentException $ex) {
            die('Erro de argumento: ' . $ex->getMessage());
        } finally {
            // Certifique-se de reativar a verificação de chaves estrangeiras em caso de erro
            self::enableForeignKeyChecks();
        }
    }

    private static function disableForeignKeyChecks()
    {
        self::$conn->exec('SET foreign_key_checks = 0');
    }

    private static function enableForeignKeyChecks()
    {
        self::$conn->exec('SET foreign_key_checks = 1');
    }


    // Método para buscar o último ID inserido com base no AUTO_INCREMENT
    public static function lastInsertedId()
    {
        try {
            $sql = "SELECT `AUTO_INCREMENT`
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE table_name = '" . static::$table . "'
                 AND table_schema = '" . env('DB_DATABASE') . "'";

            $stmt = self::$conn->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && isset($result['AUTO_INCREMENT'])) {
                return intval($result['AUTO_INCREMENT']) - 1; // Retorna o último valor inserido
            } else {
                throw new \Exception("Não foi possível recuperar o ID do AUTO_INCREMENT.");
            }
        } catch (\PDOException $e) {
            die('Erro ao buscar AUTO_INCREMENT: ' . $e->getMessage());
        }
    }

    public static function select($fields)
    {
        static::$select = $fields;
        return new static();
    }

    public static function where($field, $value)
    {
        static::$where_field = $field;
        static::$where_value = $value;
        return new static();
    }

    // Método para buscar todos os registros
    public static function get()
    {
        try {
            $sql = "SELECT " . static::$select . " FROM " . self::$table;
            if (self::$where_field != null && self::$where_value != null) {
                $sql .= " WHERE " . self::$where_field . " = ?";
                $stmt = self::$conn->prepare($sql);
                $stmt->execute([self::$where_value]);
                return $stmt->fetchAll(\PDO::FETCH_OBJ);
            } else {
                $stmt = self::$conn->query($sql);
                return $stmt->fetchAll(\PDO::FETCH_OBJ);
            }
        } catch (\PDOException $e) {
            die('Erro ao buscar registros: ' . $e->getMessage());
        }
    }

    // Método para buscar um registro específico com base no ID
    public static function find($id)
    {
        try {
            $sql = "SELECT * FROM " . self::$table . " WHERE id = ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            die('Erro ao buscar o registro: ' . $e->getMessage());
        }
    }

    // Método para atualizar um registro com base no ID
    public static function update($where, $value, $bind)
    {
        self::checkConnection();
        try {
            if (!is_array($bind)) {
                die('O parâmetro do Update deve ser um Array');
            }

            $fields = '';
            foreach ($bind as $key => $val) {
                $fields .= "$key = ?, ";
            }
            $fields = rtrim($fields, ', ');

            $sql = "UPDATE " . self::$table . " SET $fields WHERE $where = ?";

            $stmt = self::$conn->prepare($sql);
            $values = array_values($bind);
            $values[] = $value;

            return $stmt->execute($values);
        } catch (\PDOException $e) {
            die('Erro ao atualizar: ' . $e->getMessage());
        }
    }

    // Método para deletar um registro com base no ID
    public static function delete($columnReference, $val)
    {
        try {
            $sql = "DELETE FROM " . self::$table . " WHERE $columnReference = ?";
            $stmt = self::$conn->prepare($sql);
            return $stmt->execute([$val]);
        } catch (\PDOException $e) {
            die('Erro ao deletar o registro: ' . $e->getMessage());
        }
    }

    // Método para realizar consultas personalizadas
    public static function query($sql, $params = [])
    {
        self::checkConnection();
        try {
            $stmt = self::$conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            die('Erro na consulta: ' . $e->getMessage());
        }
    }
}
