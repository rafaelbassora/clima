<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\NoticiaModel;

class NoticiaController extends Controller
{
    private NoticiaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new NoticiaModel();
    }

    public function index(): void
    {
        $page = (int)$this->getParam('page', 1);
        if ($page < 1) $page = 1;
        
        $perPage = 6;
        $dados = $this->model->getPaginated($page, $perPage);
        $total = $this->model->countAll();
        $pages = ceil($total / $perPage);

        $this->view('noticias/index', [
            'pageTitle' => 'Notícias',
            'noticias'  => $dados,
            'page'      => $page,
            'pages'     => $pages,
            'activeMenu' => 'noticias'
        ]);
    }

    public function show(): void
    {
        $id = (int)$this->getParam('id');
        $noticia = $this->model->getById($id);

        if (!$noticia || !$noticia['ativo']) {
            header('Location: /noticias');
            exit;
        }

        $this->view('noticias/view', [
            'pageTitle' => $noticia['titulo'],
            'noticia'   => $noticia,
            'activeMenu' => 'noticias'
        ]);
    }
}
