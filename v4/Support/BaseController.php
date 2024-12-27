<?php

namespace Kib\Support;

class BaseController
{

    private $layout = null;

    private $contentLayout = '';

    private $sectionTitle = '';

    public function uploadImg($ficheiro, $fich, $target)
    {
        $nomeFicheiro = pathinfo($ficheiro, PATHINFO_FILENAME);
        $ext = pathinfo($ficheiro, PATHINFO_EXTENSION);
        $tmp_name = $fich;

        $extencoes = array("jpg", "jpeg", "png");

        if (in_array($ext, $extencoes)) {
            $dataimg = getdate();

            $instance_now = $dataimg['year'] . '-' . $dataimg['mon'] . '-' . $dataimg['mday'] . ' ' . $dataimg['hours'] . '-' . $dataimg['minutes'] . '-' . $dataimg['seconds'];

            $numeros_rand = rand(1, 10000);

            // $file_name_toStore = md5($nomeFicheiro . '-' . $instance_now . '-' . $numeros_rand);
            $file_name_toStore = $nomeFicheiro . "_p_" . date('d') . $numeros_rand;
            $file_name_toStore .= '.' . $ext;
            $dirname = storage_path() . "$target/";
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777, true);
            }
            $storage = $dirname . $file_name_toStore;
            if (move_uploaded_file($tmp_name, $storage)) {
                return $file_name_toStore;
            } else {
                return 401;
            }
        } else {
            return 433;
        }
    }

    // Método para carregar um modelo
    public function model($model)
    {
        // Verifica se o arquivo do modelo existe
        if (file_exists('model/' . $model . '.php')) {
            // Requer o arquivo do modelo
            require_once 'model/' . $model . '.php';
            // Retorna uma nova instância do modelo
            return new $model();
        } else {
            die('O ' . $model . ' modelo não existe.');
        }
    }

    public function yield($section, $value)
    {
        $this->sectionTitle = $section == 'title' ? $value : '';
        return $this->sectionTitle;
    }

    public function extends($layout, $params = [])
    {
        $this->layout = $layout;
        $this->sectionTitle = $params['title'] ?? $this->sectionTitle;
        $this->yield('title', $this->sectionTitle);
    }

    public function load()
    {
        echo $this->contentLayout;
    }

    // Método para carregar uma visualização
    public function view($view, $data = [])
    {
        $path = view_path();
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        // Verifica se o arquivo de visualização exist

        if (file_exists($path . $view . '.php')) {
            // Requer o arquivo da visualização
            ob_start();
            extract($data);
            require_once $path . $view . '.php';
            $content = ob_get_contents();
            $this->contentLayout = $content;
            ob_end_clean();

            if ($this->layout != null) {
                if (!$this->layoutExists($this->layout)) {
                    return $this->loadErrorView('error.404', ['message' => 'O Layout indicado não existe!']);
                }
                ob_start();
                require_once $this->layoutFile($this->layout);
                $contentLayout = ob_get_contents();
                ob_end_clean();
                echo $contentLayout;
                $this->layout = null;
                return;
            }
            echo $content;
            return;
        } else {

            die('A view indicada não existe!');
        }
    }

    public function loadErrorView($view, $params = [])
    {
        $path = view_path();
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        if (file_exists($path . $view . '.php')) {
            // Requer o arquivo da visualização
            extract($params);
            require_once $path . $view . '.php';
        }
    }

    private function layoutExists($layout)
    {
        return file_exists($this->layoutFile($layout));
    }

    private function layoutFile($layout)
    {
        return view_path() . 'layouts/' . str_replace('.', DIRECTORY_SEPARATOR, $layout) . '.php';
    }

    public function view_component($view, $data = [])
    {
        $path = view_path();
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

        // Verifica se o arquivo de visualização existe
        if (file_exists($path . 'components/' . $view . '.vc.php')) {
            // Requer o arquivo da visualização
            extract($data);

            require_once $path . 'components/' . $view . '.vc.php';
        } else {

            die('A view indicada não existe!');
        }
    }
}
