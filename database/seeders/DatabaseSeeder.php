<?php


namespace Factory\seeders;

use Kib\Orm\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Seeder::applySeeders([
            \Factory\seeders\estado::class,
        ]);
    }
}
