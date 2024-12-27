<?php

namespace Kib\Sessions;

class SessionManager
{
    private static $instance = null;
    private $timeout = 42000; //3000000; // Tempo limite de inatividade em segundos (ex: 300 segundos = 5 minutos)

    private function __construct() {}

    public function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkSessionTimeout();
        $this->handleFlashMessages();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new SessionManager();
        }
        return self::$instance;
    }

    // Verifica se a sessão expirou devido à inatividade
    private function checkSessionTimeout()
    {
        if (isset($_SESSION['last_time_activity'])) {
            $inactive = time() - $_SESSION['last_time_activity'];
            if ($inactive > $this->timeout) {

                $this->destroySession();
                // Opcional: Redirecionar ou notificar o usuário sobre a expiração da sessão
                header("Location: login.php?message=Session expired");
                exit();
            } else {
                $_SESSION['last_time_activity'] = time();
            }
        } else {
            $_SESSION['last_time_activity'] = time();
        }
    }

    // Destroi a sessão atual
    public function destroySession()
    {
        // Limpa todas as variáveis de sessão
        $_SESSION = [];

        // Se desejar, limpa os cookies de sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroi a sessão
        session_unset();
        session_destroy();
    }

    // Define um valor na sessão
    public function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // Obtém um valor da sessão
    public function getSession($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // Define uma mensagem flash
    public function setFlash($key, $message)
    {
        $_SESSION['flash_messages'][$key] = $message;
    }

    // Obtém e remove uma mensagem flash
    public function getFlash($key)
    {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        return null;
    }

    // Manipula as mensagens flash para garantir que sejam exibidas apenas uma vez
    private function handleFlashMessages()
    {
        if (isset($_SESSION['flash_messages'])) {
            // Você pode adicionar lógica adicional aqui se necessário
            // Por exemplo, limpar mensagens expiradas ou reter mensagens
        }
    }

    // Método utilitário para redirecionar o usuário
    public function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public function isLoggedIn()
    {
        return $this->getSession('loggedId') && $this->getSession('auth');
    }

    public function logout()
    {
        $this->destroySession();

        return $this;
    }
}
