<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\ChuvaManualModel;
use App\Models\AlertaModel;
use App\Models\NoticiaModel;
use App\Models\LeituraModel;

class AdminController extends Controller
{
    private ChuvaManualModel $model;
    private AlertaModel $alertaModel;
    private NoticiaModel $noticiaModel;
    private LeituraModel $leituraModel;
    private \MysqliDb $db;

    public function __construct()
    {
        parent::__construct();
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit;
        }
        $this->model = new ChuvaManualModel();
        $this->alertaModel = new AlertaModel();
        $this->noticiaModel = new NoticiaModel();
        $this->leituraModel = new LeituraModel();
        $this->db = $GLOBALS['db'];
    }

    public function index(): void
    {
        $page = (int)$this->getParam('page', 1);
        $perPage = 500;
        
        $dados = $this->model->getPaginated($page, $perPage);
        $total = $this->model->countAll();
        $pages = ceil($total / $perPage);

        $this->view('admin/index', [
            'title'   => 'Admin - Chuvas Manuais',
            'dados'   => $dados,
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total
        ], 'admin');
    }

    public function add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'data'   => $_POST['data'] . ' ' . date('H:i:s'),
                'local'  => (int)$_POST['local'],
                'choveu' => isset($_POST['choveu']) ? 1 : 0,
                'medida' => (float)str_replace(',', '.', $_POST['medida']),
                'ph'     => (float)str_replace(',', '.', $_POST['ph']),
                'obs'    => $_POST['obs']
            ];
            $this->model->create($data);
            $this->cache->clear();
            header('Location: /admin');
            return;
        }
        $this->view('admin/form', ['title' => 'Nova Chuva Manual'], 'admin');
    }

    public function edit(): void
    {
        $id = (int)$this->getParam('id');
        $item = $this->model->getById($id);

        if (!$item) {
            header('Location: /admin');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'data'   => $_POST['data'],
                'local'  => (int)$_POST['local'],
                'choveu' => isset($_POST['choveu']) ? 1 : 0,
                'medida' => (float)str_replace(',', '.', $_POST['medida']),
                'ph'     => (float)str_replace(',', '.', $_POST['ph']),
                'obs'    => $_POST['obs']
            ];
            $this->model->update($id, $data);
            $this->cache->clear();
            header('Location: /admin');
            return;
        }

        $this->view('admin/form', [
            'title' => 'Editar Chuva Manual',
            'item'  => $item
        ], 'admin');
    }

    public function delete(): void
    {
        $id = (int)$this->getParam('id');
        $this->model->delete($id);
        $this->cache->clear();
        header('Location: /admin');
    }

    // === ALERTAS ===

    public function alertas(): void
    {
        $dados = $this->alertaModel->getAll();
        $this->view('admin/alertas/index', [
            'title' => 'Gerenciar Alertas',
            'dados' => $dados
        ], 'admin');
    }

    public function alertaAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tipo'        => $_POST['tipo'],
                'mensagem'    => $_POST['mensagem'],
                'data_inicio' => $_POST['data_inicio'],
                'data_fim'    => $_POST['data_fim'],
                'ativo'       => isset($_POST['ativo']) ? 1 : 0
            ];
            $this->alertaModel->create($data);
            $this->cache->clear();
            header('Location: /admin/alertas');
            return;
        }
        $this->view('admin/alertas/form', ['title' => 'Novo Alerta'], 'admin');
    }

    public function alertaEdit(): void
    {
        $id = (int)$this->getParam('id');
        $item = $this->alertaModel->getById($id);

        if (!$item) {
            header('Location: /admin/alertas');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tipo'        => $_POST['tipo'],
                'mensagem'    => $_POST['mensagem'],
                'data_inicio' => $_POST['data_inicio'],
                'data_fim'    => $_POST['data_fim'],
                'ativo'       => isset($_POST['ativo']) ? 1 : 0
            ];
            $this->alertaModel->update($id, $data);
            $this->cache->clear();
            header('Location: /admin/alertas');
            return;
        }

        $this->view('admin/alertas/form', [
            'title' => 'Editar Alerta',
            'item'  => $item
        ], 'admin');
    }

    public function alertaDelete(): void
    {
        $id = (int)$this->getParam('id');
        $this->alertaModel->delete($id);
        $this->cache->clear();
        header('Location: /admin/alertas');
    }

    // === NOTÍCIAS ===

    public function noticias(): void
    {
        $dados = $this->noticiaModel->getAll(false); // Pega inclusive inativas
        $this->view('admin/noticias/index', [
            'title' => 'Gerenciar Notícias',
            'dados' => $dados
        ], 'admin');
    }

    public function noticiaAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagem = $this->uploadImagem('imagem');

            $data = [
                'titulo'           => $_POST['titulo'],
                'resumo'           => $_POST['resumo'],
                'conteudo'         => $_POST['conteudo'],
                'imagem'           => $imagem,
                'ativo'            => isset($_POST['ativo']) ? 1 : 0,
                'data_noticia'     => $_POST['data_noticia'] ?: date('Y-m-d H:i:s'),
                'data_agendamento' => $_POST['data_agendamento'] ?: date('Y-m-d H:i:s')
            ];
            $this->noticiaModel->create($data);
            $this->cache->clear();
            header('Location: /admin/noticias');
            return;
        }
        $this->view('admin/noticias/form', ['title' => 'Nova Notícia'], 'admin');
    }

    public function noticiaEdit(): void
    {
        $id = (int)$this->getParam('id');
        $item = $this->noticiaModel->getById($id);

        if (!$item) {
            header('Location: /admin/noticias');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'titulo'           => $_POST['titulo'],
                'resumo'           => $_POST['resumo'],
                'conteudo'         => $_POST['conteudo'],
                'ativo'            => isset($_POST['ativo']) ? 1 : 0,
                'data_noticia'     => $_POST['data_noticia'] ?: date('Y-m-d H:i:s'),
                'data_agendamento' => $_POST['data_agendamento'] ?: date('Y-m-d H:i:s')
            ];

            $novaImagem = $this->uploadImagem('imagem');
            if ($novaImagem) {
                // Remove a antiga se existir
                if ($item['imagem']) {
                    @unlink(__DIR__ . '/../../public/' . $item['imagem']);
                }
                $data['imagem'] = $novaImagem;
            }

            $this->noticiaModel->update($id, $data);
            $this->cache->clear();
            header('Location: /admin/noticias');
            return;
        }

        $this->view('admin/noticias/form', [
            'title' => 'Editar Notícia',
            'item'  => $item
        ], 'admin');
    }

    public function noticiaDelete(): void
    {
        $id = (int)$this->getParam('id');
        $item = $this->noticiaModel->getById($id);
        if ($item) {
            if ($item['imagem']) {
                @unlink(__DIR__ . '/../../public/' . $item['imagem']);
            }
            $this->noticiaModel->delete($id);
            $this->cache->clear();
        }
        header('Location: /admin/noticias');
    }

    private function uploadImagem(string $field): ?string
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$field];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowed)) {
            return null;
        }

        // Verifica MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!str_starts_with($mime, 'image/')) {
            return null;
        }

        $newName = uniqid('news_', true) . '.' . $ext;
        $destPath = __DIR__ . '/../../public/uploads/noticias/' . $newName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return 'uploads/noticias/' . $newName;
        }

        return null;
    }
    public function nowcasting(): void
    {
        $stats = $this->leituraModel->getNowcastingStats();
        
        $historico = $this->db->rawQuery(
            "SELECT * FROM nowcasting ORDER BY created_at DESC LIMIT 50"
        );

        $this->view('admin/nowcasting', [
            'title' => 'Estatísticas de Nowcasting',
            'stats' => $stats,
            'historico' => $historico
        ], 'admin');
    }
}
