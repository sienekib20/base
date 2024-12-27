<?php

use Kib\Anotation\Route;

Route::get('/', [\App\Http\Controllers\AppController::class, 'index']);
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'index']);

Route::restrictedRoutes(\App\Http\Middlewares\AuthMiddleware::class, function () {

    Route::get('/user/{id}', [\App\Http\Controllers\AppController::class, 'teste']);
});
