<?php

namespace Kib\Orm;

class Seeder
{
    public static function applySeeders($seeders)
    {
        foreach ($seeders as $seeder) {
            try {
                (new $seeder())->seed();
            } catch (\Exception $e) {
                echo "Erro ao aplicar seeder " . get_class($seeder) . ": " . $e->getMessage() . "\n";
            }
        }
    }
}
