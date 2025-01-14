<?php

namespace Kib\Http\Navigation;

use Kib\Http\Request;

class Pathway
{
    private $uri;

    public function __construct()
    {
        $request = new Request();
        $this->uri = $request->uri();
    }

    public function createPathway()
    {
        if (substr_count($this->uri, '?') > 0) {
            $explodeIgual = explode('=', $this->uri);
            return "<span>{$explodeIgual}</span>";
        }

        if ($this->uri == '/') {
            return  "<span> <i class='bx bxs-home'></i> </span>";
        }

        echo "<a href='/'> <i class='bx bxs-home'></i> </a>";
        foreach ($this->uri as $partUri) {
            echo "<span> {$partUri} </span>";
        }
    }
}
