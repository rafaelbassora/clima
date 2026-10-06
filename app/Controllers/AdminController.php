<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\ChuvaManualModel;
use App\Models\AlertaModel;
use App\Models\NoticiaModel;
use App\Models\LeituraModel;
use App\Models\UserModel;
use App\Models\ChuvaCorrecaoModel;
use App\Models\CidadaoModel;
use App\Models\ChuvaCidadaModel;
use App\Services\TotpService;
use App\Services\MailerService;

class AdminController extends Controller
{
    private ChuvaManualModel $model;
    private AlertaModel $alertaModel;
    private NoticiaModel $noticiaModel;
    private LeituraModel $leituraModel;
    private UserModel $userModel;
    private ChuvaCorrecaoModel $chuvaCorrecaoModel;
    private CidadaoModel $cidadaoModel;
    private ChuvaCidadaModel $chuvaCidadaModel;
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
        $this->userModel = new UserModel();
        $this->chuvaCorrecaoModel = new ChuvaCorrecaoModel();
        $this->cidadaoModel = new CidadaoModel();
        $this->chuvaCidadaModel = new ChuvaCidadaModel();
        $this->db = $GLOBALS['db'];
    }

    public function dashboard(): void
    {
        $nowcastStats = $this->leituraModel->getNowcastingStats();
        $geral = $nowcastStats['geral'] ?? [];
        $totalPrevisoes = (int) ($geral['total'] ?? 0);
        $acertos = (int) ($geral['acertos'] ?? 0);
        $acuracia = $totalPrevisoes > 0 ? round(($acertos / $totalPrevisoes) * 100, 1) : null;

        $pendentes = $this->cidadaoModel->getPendentes();

        $this->view('admin/dashboard', [
            'title' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'totalUsuarios' => count($this->userModel->getAll()),
            'totalCidadaosPendentes' => count($pendentes),
            'totalEstacoesAprovadas' => count($this->cidadaoModel->getAprovados()),
            'totalChuvasManuais' => $this->model->countAll(),
            'totalAlertasAtivos' => count($this->alertaModel->getAtivos()),
            'totalNoticias' => count($this->noticiaModel->getAll(false)),
            'acuraciaNowcasting' => $acuracia,
            'cidadaosPendentesRecentes' => array_slice($pendentes, 0, 5),
            'correcoesRecentes' => array_slice($this->chuvaCorrecaoModel->getAll(), 0, 5),
        ], 'admin');
    }

    public function index(): void
    {
        $page = (int)$this->getParam('page', 1);
        $perPage = 500;
        
        $dados = $this->model->getPaginated($page, $perPage);
        $total = $this->model->countAll();
        $pages = ceil($total / $perPage);

        $this->view('admin/index', [
            'activeMenu' => 'chuvas',
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
        $this->view('admin/form', ['title' => 'Nova Chuva Manual', 'activeMenu' => 'chuvas'], 'admin');
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
            'activeMenu' => 'alertas',
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
        $this->view('admin/alertas/form', ['title' => 'Novo Alerta', 'activeMenu' => 'alertas'], 'admin');
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
            'activeMenu' => 'noticias',
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
        $this->view('admin/noticias/form', ['title' => 'Nova Notícia', 'activeMenu' => 'noticias'], 'admin');
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
            'activeMenu' => 'nowcasting',
            'title' => 'Estatísticas de Nowcasting',
            'stats' => $stats,
            'historico' => $historico,
            'nowcastConfig' => (new \App\Models\NowcastConfigModel())->getConfig(),
        ], 'admin');
    }

    public function nowcastingRecalibrar(): void
    {
        $resultado = (new \App\Services\NowcastCalibrationService())->recalibrar(
            $this->leituraModel,
            new \App\Models\NowcastConfigModel(),
            true
        );
        $_SESSION['nowcast_recalibrado'] = $resultado;
        header('Location: /admin/nowcasting');
    }

    // === USUÁRIOS ===

    public function usuarios(): void
    {
        $dados = $this->userModel->getAll();
        $this->view('admin/usuarios/index', [
            'activeMenu' => 'usuarios',
            'title' => 'Gerenciar Usuários',
            'dados' => $dados
        ], 'admin');
    }

    public function usuarioAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validarUsuarioForm();
            if ($erro) {
                $this->view('admin/usuarios/form', ['title' => 'Novo Usuário', 'erro' => $erro], 'admin');
                return;
            }

            $this->userModel->create([
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'password' => $_POST['password'],
                'is_admin' => isset($_POST['is_admin']),
            ]);
            header('Location: /admin/usuarios');
            return;
        }
        $this->view('admin/usuarios/form', ['title' => 'Novo Usuário', 'activeMenu' => 'usuarios'], 'admin');
    }

    public function usuarioEdit(): void
    {
        $id = (int) $this->getParam('id');
        $item = $this->userModel->getById($id);

        if (!$item) {
            header('Location: /admin/usuarios');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validarUsuarioForm($id);
            if ($erro) {
                $this->view('admin/usuarios/form', ['title' => 'Editar Usuário', 'item' => $item, 'erro' => $erro], 'admin');
                return;
            }

            $this->userModel->update($id, [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'password' => $_POST['password'] ?? '',
                'is_admin' => isset($_POST['is_admin']),
            ]);
            header('Location: /admin/usuarios');
            return;
        }

        $this->view('admin/usuarios/form', [
            'title' => 'Editar Usuário',
            'item' => $item
        ], 'admin');
    }

    public function usuarioDelete(): void
    {
        $id = (int) $this->getParam('id');
        if ($id !== ($_SESSION['usuario_id'] ?? null)) {
            $this->userModel->delete($id);
        }
        header('Location: /admin/usuarios');
    }

    private function validarUsuarioForm(?int $editId = null): ?string
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            return 'Nome e e-mail são obrigatórios.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'E-mail inválido.';
        }
        if ($editId === null && $password === '') {
            return 'Senha é obrigatória para novo usuário.';
        }
        if ($password !== '' && strlen($password) < 8) {
            return 'Senha deve ter pelo menos 8 caracteres.';
        }
        if ($this->userModel->emailExists($email, $editId)) {
            return 'Já existe um usuário com este e-mail.';
        }

        return null;
    }

    // === CORREÇÃO DE CHUVA AUTOMÁTICA ===

    public function chuvaAutomatica(): void
    {
        $dados = $this->chuvaCorrecaoModel->getAll();
        $this->view('admin/chuva-automatica/index', [
            'activeMenu' => 'chuva-automatica',
            'title' => 'Corrigir Chuva Automática',
            'dados' => $dados
        ], 'admin');
    }

    public function chuvaAutomaticaSalvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $local = (string) ($_POST['local'] ?? '1');
            $data = $_POST['data'] ?? date('Y-m-d');
            $hora = trim($_POST['hora'] ?? '') ?: null;
            $choveu = isset($_POST['choveu']);
            $valor = (float) str_replace(',', '.', $_POST['valor_mm'] ?? '0');
            $obs = trim($_POST['obs'] ?? '') ?: null;

            $this->chuvaCorrecaoModel->salvar($local, $data, $valor, $obs, $hora, $choveu);
            $this->cache->clear();
            header('Location: /admin/chuva-automatica');
            return;
        }
        header('Location: /admin/chuva-automatica');
    }

    public function chuvaAutomaticaDelete(): void
    {
        $id = (int) $this->getParam('id');
        $this->chuvaCorrecaoModel->delete($id);
        $this->cache->clear();
        header('Location: /admin/chuva-automatica');
    }

    // === 2FA (autosserviço, cada usuário configura a própria conta) ===

    public function twofa(): void
    {
        $user = $this->userModel->getById((int) $_SESSION['usuario_id']);

        if (!empty($user['totp_enabled'])) {
            $this->view('admin/2fa', ['title' => 'Autenticação em Duas Etapas', 'ativado' => true, 'activeMenu' => '2fa'], 'admin');
            return;
        }

        // Gera (ou reaproveita) um segredo pendente de confirmação
        if (empty($_SESSION['2fa_setup_secret'])) {
            $_SESSION['2fa_setup_secret'] = (new TotpService())->generateSecret();
        }
        $secret = $_SESSION['2fa_setup_secret'];
        $otpUri = (new TotpService())->getOtpAuthUri($secret, $user['email']);

        $this->view('admin/2fa', [
            'title' => 'Autenticação em Duas Etapas',
            'ativado' => false,
            'secret' => $secret,
            'otpUri' => $otpUri,
        ], 'admin');
    }

    public function twofaEnable(): void
    {
        $userId = (int) $_SESSION['usuario_id'];
        $secret = $_SESSION['2fa_setup_secret'] ?? '';
        $codigo = $_POST['codigo'] ?? '';

        if ($secret && (new TotpService())->verifyCode($secret, $codigo)) {
            $this->userModel->setTotpSecret($userId, $secret);
            $this->userModel->enableTotp($userId);
            unset($_SESSION['2fa_setup_secret']);
            header('Location: /admin/2fa');
            return;
        }

        $user = $this->userModel->getById($userId);
        $otpUri = (new TotpService())->getOtpAuthUri($secret, $user['email']);
        $this->view('admin/2fa', [
            'title' => 'Autenticação em Duas Etapas',
            'ativado' => false,
            'secret' => $secret,
            'otpUri' => $otpUri,
            'erro' => 'Código inválido. Tente novamente.',
        ], 'admin');
    }

    public function twofaDisable(): void
    {
        $this->userModel->disableTotp((int) $_SESSION['usuario_id']);
        unset($_SESSION['2fa_setup_secret']);
        header('Location: /admin/2fa');
    }

    // === PLUVIÔMETRO CIDADÃO (moderação) ===

    public function cidadaos(): void
    {
        $this->view('admin/cidadaos/index', [
            'activeMenu' => 'cidadaos',
            'title' => 'Pluviômetro Cidadão - Moderação',
            'pendentes' => $this->cidadaoModel->getPendentes(),
            'aprovados' => $this->cidadaoModel->getAprovados(),
            'lancamentosComObs' => $this->chuvaCidadaModel->getUltimosParaAdmin(50),
        ], 'admin');
    }

    public function cidadaoAprovar(): void
    {
        $id = (int) $this->getParam('id');
        $cidadao = $this->cidadaoModel->getById($id);
        $code = $this->cidadaoModel->aprovar($id);

        if ($cidadao && $code) {
            $corpo = "<p>Olá, {$this->escapeHtml($cidadao['nome'])}!</p>"
                . "<p>Seu cadastro no <strong>Pluviômetro Cidadão</strong> foi aprovado.</p>"
                . "<p>Sua estação recebeu o código: <strong>{$code}</strong></p>"
                . "<p>Já pode acessar seu painel e começar a lançar os dados de chuva:</p>"
                . "<p><a href=\"https://clima.pereirabarreto.sp.gov.br/pluviometro-cidadao/login\">Acessar meu painel</a></p>";
            (new MailerService())->enviar($cidadao['email'], $cidadao['nome'], 'Cadastro Aprovado - Pluviômetro Cidadão', $corpo);
        }

        header('Location: /admin/cidadaos');
    }

    public function cidadaoRejeitar(): void
    {
        $id = (int) $this->getParam('id');
        $this->cidadaoModel->rejeitar($id);
        header('Location: /admin/cidadaos');
    }

    private function escapeHtml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}
