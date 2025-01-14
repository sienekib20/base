<?php

namespace Kib\Support;

class Hash
{
    public static function make($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verify($password, $hashed)
    {
        return password_verify($password, $hashed);
    }

    public static function create($content)
    {
        return sha1($content);
    }
}
