<?php

namespace Factory\seeders;

use Kib\Orm\DB;

class estado
{
    public function seed()
    {
        DB::table('estado')->insertMultiple([
            [
                'estado' => 'Activo'
            ],
            [
                'estado' => 'Suspenso'
            ],
        ]);
    }
}
