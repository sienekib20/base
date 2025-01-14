<?php

namespace Kib\Orm;

use Kib\Orm\DB;

class Schema
{
    public static function create($sql)
    {
        DB::execCommand($sql);
    }

    public static function dropIfExists($tableName)
    {
        DB::execCommand("DROP TABLE IF EXISTS $tableName");
    }
}
