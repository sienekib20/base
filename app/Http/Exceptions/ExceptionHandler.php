<?php

namespace App\Http\Exceptions;

class ExceptionHandler extends \Exception
{
    public function __construct(
        $message = "Erro inesperado",
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    // Método para reportar a exceção (registrar em log, por exemplo)
    public function report()
    {
        // Aqui você pode adicionar lógica para registrar a exceção em um arquivo de log ou banco de dados
        echo '<pre>';
        echo "Exceção registrada: " . $this->getMessage() . "\n";
    }

    // Método para renderizar uma mensagem amigável para o usuário
    public function render()
    {
        // Aqui você pode adicionar lógica para exibir uma mensagem amigável para o usuário
        echo "Erro: " . $this->getMessage() . "\n";
    }

    // Novo método handle para processar ou "tratar" a exceção
    public function handle(\Exception $e)
    {
        // Lógica para tratar a exceção (pode ser uma ação como redirecionamento, notificação, etc.)
        echo '<pre>';
        echo $this->renderErrorPage("Tratando a exceção: " . $e->getMessage() . "\n");
        //echo "Tratando a exceção: " . $e->getMessage() . "\n";
        // Você pode incluir lógica adicional aqui, como enviar e-mails ou redirecionar o usuário.
    }

    // Método para gerar uma página de erro personalizada
    private function renderErrorPage($msg)
    {
        // A seguir, o HTML e CSS da página de erro
        return '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro - Sistema</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f3f4f6;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    color: #333;
                }
                .error-container {
                    text-align: center;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    padding: 30px;
                    max-width: 400px;
                    width: 100%;
                }
                .error-container h1 {
                    font-size: 4em;
                    margin: 0;
                    color: #e74c3c;
                }
                .error-container p {
                    font-size: 1.2em;
                    margin: 20px 0;
                }
                .btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #3498db;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 20px;
                    font-size: 1em;
                }
                .btn:hover {
                    background-color: #2980b9;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>404</h1>
                <p>' . $msg . '</p>
                <p>Algo deu errado. Tente novamente mais tarde.</p>
                <a href="/" class="btn">Voltar à página inicial</a>
            </div>
        </body>
        </html>';
    }
}
