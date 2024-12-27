<?php

namespace App\Http\Middlewares;

abstract class Middleware
{
    abstract public function handle();
}
