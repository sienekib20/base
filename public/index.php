<?php


use App\Http\Middlewares\AuthMiddleware;
use Kib\Http\Request;

// Carregando o autoload de classes
require __DIR__ . '/../spr-4/autoload.php';


// Carregando o app
require __DIR__ . '/../bootstrap/app.php';

// Inicializando o app
$app = Application::configure(dirname(__DIR__))
    ->withRouting(
        __DIR__ . '/../routes/web.php',
        __DIR__ . '/../routes/console.php',
        ''
    )
    ->withMiddleware(new AuthMiddleware())
    ->withExceptions(function ($exception) {})
    ->create(Request::capture());

$app->start();

