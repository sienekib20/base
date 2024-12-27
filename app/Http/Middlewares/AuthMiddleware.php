<?php

namespace App\Http\Middlewares;

class AuthMiddleware extends Middleware
{
    public function handle()
    {
        // Simulando a verificação de autenticação
        // echo "Verificando autenticação...\n";
        $isAuthenticated = true; // Aqui você verificaria a sessão ou o token, por exemplo.

        if (!$isAuthenticated) {
            throw new \App\Http\Exceptions\ExceptionHandler("Usuário não autenticado!");
        }

        // echo "Usuário autenticado. Acesso permitido!\n";
    }
}
