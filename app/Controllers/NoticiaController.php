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

        // link absoluto da notícia (compartilhar e prévia no WhatsApp/Facebook)
        $dominio = $this->config['app']['dominio'] ?? ($_SERVER['HTTP_HOST'] ?? '');
        $base = 'https://' . $dominio;
        $urlNoticia = $base . '/noticias/view?id=' . (int) $noticia['id'];

        $this->view('noticias/view', [
            'pageTitle' => $noticia['titulo'],
            'noticia'   => $noticia,
            'activeMenu' => 'noticias',
            'urlCompartilhar' => $urlNoticia,
            'ogTitulo'    => $noticia['titulo'],
            'ogDescricao' => $noticia['resumo'] ?? '',
            'ogImagem'    => !empty($noticia['imagem']) ? $base . '/' . ltrim($noticia['imagem'], '/') : null,
            'ogUrl'       => $urlNoticia,
            'ogTipo'      => 'article',
        ]);
    }
}
