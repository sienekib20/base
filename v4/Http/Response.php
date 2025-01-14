<?php

namespace Kib\Http;

class Response implements \Serializable
{
    protected $status;
    protected $headers = [];
    protected $content;
    protected $cookies = [];

    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    // Define o conteúdo da resposta
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    // Define o status da resposta
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    // Define o cabeçalho para JSON e retorna a resposta como JSON
    public function json($data, $status = 200)
    {
        $data = !is_array($data) ? [$data] : $data;
        try {
            $this->setStatus($status);
            $this->addHeader('Content-Type', 'application/json');

            $this->addHeader("Access-Control-Allow-Origin", "*"); // Ou substitua * pela origem desejada
            $this->addHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
            $this->addHeader("Access-Control-Allow-Headers", "Content-Type");
        } catch (\Exception) {
            $this->setStatus(400);
        }
        $this->setContent(json_encode($data));
        $this->send();
        return $this;
    }

    public function sendFromApi($data, $status = 200)
    {
        $data = !is_array($data) ? [$data] : $data;
        $data = (object) $data;
        $this->setStatus($status);
        $this->addHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        $this->send();
        return $this;
    }

    // Adiciona um cabeçalho à resposta
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    // Define múltiplos cabeçalhos
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
        return $this;
    }

    // Define um cookie na resposta
    public function setCookie($name, $value, $expire = 0, $path = "/", $domain = "", $secure = false, $httpOnly = true)
    {
        $this->cookies[] = compact('name', 'value', 'expire', 'path', 'domain', 'secure', 'httpOnly');
        return $this;
    }

    // Envia todos os cabeçalhos
    protected function sendHeaders()
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }
    }

    // Envia a resposta final
    public function send()
    {
        $this->sendHeaders();
        echo $this->content;
    }

    // Implementação da interface Serializable
    public function serialize()
    {
        return serialize([
            'status' => $this->status,
            'headers' => $this->headers,
            'content' => $this->content,
            'cookies' => $this->cookies,
        ]);
    }

    public function unserialize($data)
    {
        $unserialized = unserialize($data);
        $this->status = $unserialized['status'];
        $this->headers = $unserialized['headers'];
        $this->content = $unserialized['content'];
        $this->cookies = $unserialized['cookies'];
    }
}
