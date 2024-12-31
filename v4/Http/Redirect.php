<?php

namespace Kib\Http;

class Redirect
{
    private static $instance = null; // Instância singleton
    private $urlRoute;
    private $redirectWith = false;

    public function __construct()
    {
        // Garantir que a sessão esteja ativa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Obtém a instância singleton
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Redireciona para a URL anterior
     */
    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/'; // Voltar para a raiz se não houver referer
        self::getInstance()->redirect($referer);
    }

    /**
     * Redireciona para uma rota específica
     */
    public static function route($url)
    {
        $instance = self::getInstance();
        $instance->urlRoute = $url;
        $instance->redirect($url);
    }

    /**
     * Adiciona dados à sessão antes do redirecionamento
     */
    public static function with($key, $message)
    {
        $instance = self::getInstance();
        $_SESSION[$key] = $message;
        $instance->redirectWith = true;
        return $instance;
    }

    /**
     * Aplica o redirecionamento
     */
    public function redirect($url)
    {
        if (!headers_sent()) { // Garante que os headers não tenham sido enviados

            header("Location: /" . url_path() . "/$url");
            exit();
        } else {
            // Em caso de headers já enviados, usar JavaScript como fallback
            echo "<script>window.location.href='$url';</script>";
            exit();
        }
    }
}
// Redirect::back();
// Redirect::route('/home');
// Redirect::with('success', 'Você foi redirecionado com sucesso!')->route('/dashboard');
