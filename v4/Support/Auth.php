<?php

namespace Kib\Support;

class Auth
{
    protected $userModel;
    protected $sessionKey;
    protected $tokenKey;
    protected $tokenExpirationTime; // tempo de expiração do token em segundos

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
        $this->sessionKey = config('auth.sessionKey', 'userId');
        $this->tokenKey = 'auth_token'; // chave para armazenar o token na sessão
        $this->tokenExpirationTime = 3600; // 1 hora, você pode ajustar conforme necessário

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function attempt($email, $password)
    {
        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $token = $this->generateToken();
            $this->login($user, $token);
            return $token;
        }

        return false;
    }

    public function generateToken()
    {
        // Gerando um token aleatório para a sessão
        return bin2hex(random_bytes(32)); // Um token de 64 caracteres (32 bytes)
    }

    public function login(array $user, $token)
    {
        $_SESSION[$this->sessionKey] = $user;
        $_SESSION[$this->tokenKey] = [
            'token' => $token,
            'expires_at' => time() + $this->tokenExpirationTime // Definindo o tempo de expiração
        ];
    }

    public function user()
    {
        if ($this->check()) {
            return $_SESSION[$this->sessionKey];
        }

        return null;
    }

    public function check()
    {
        if (isset($_SESSION[$this->sessionKey]) && isset($_SESSION[$this->tokenKey])) {
            $tokenData = $_SESSION[$this->tokenKey];

            // Verificar se o token expirou
            if (time() > $tokenData['expires_at']) {
                $this->logout();
                return false; // Token expirado
            }

            return true; // Token válido
        }

        return false;
    }

    public function logout()
    {
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION[$this->tokenKey]);
    }

    public function id()
    {
        $user = $this->user();
        return $user['id'] ?? null;
    }

    public function validateToken($token)
    {
        // Validar o token comparando com o token armazenado na sessão
        return isset($_SESSION[$this->tokenKey]) && $_SESSION[$this->tokenKey]['token'] === $token;
    }
}
