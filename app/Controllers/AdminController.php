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
use App\Models\EstacaoConfigModel;
use App\Services\TotpService;
use App\Services\MailerService;
use App\Services\WundergroundService;
use App\Services\ExportacaoService;
use App\Services\NoticiaAutomaticaService;

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

    // ------------------------------------------------------------------
    // Geração automática de notícias (rascunho a partir dos dados)
    // ------------------------------------------------------------------

    /**
     * Lê o pedido (GET na prévia, POST ao salvar) e devolve [tipo, parametros, noticia|null, erro|null].
     */
    private function gerarNoticiaDoPedido(array $in, NoticiaAutomaticaService $service): array
    {
        $tipo = in_array($in['tipo'] ?? '', ['mes', 'ano', 'evento'], true) ? $in['tipo'] : '';
        $anoAtual = (int) date('Y');
        $p = [
            'ano'   => min($anoAtual, max(2018, (int) ($in['ano'] ?? $anoAtual))),
            'mes'   => min(12, max(1, (int) ($in['mes'] ?? (int) date('n', strtotime('first day of last month'))))),
            'chave' => preg_match('/^(chuva|calor|frio|umidade|estiagem):[0-9:\-]{10,21}$/', (string) ($in['chave'] ?? '')) ? $in['chave'] : '',
        ];
        if ($tipo === 'mes' && sprintf('%04d-%02d', $p['ano'], $p['mes']) > date('Y-m')) {
            return [$tipo, $p, null, 'Esse mês ainda não começou.'];
        }
        if ($tipo === '' || ($tipo === 'evento' && $p['chave'] === '')) {
            return [$tipo, $p, null, null];
        }
        try {
            $noticia = match ($tipo) {
                'mes'    => $service->gerarMes($p['ano'], $p['mes']),
                'ano'    => $service->gerarAno($p['ano']),
                'evento' => $service->gerarEvento($p['chave']),
            };
            return [$tipo, $p, $noticia, null];
        } catch (\Throwable $e) {
            return [$tipo, $p, null, $e->getMessage()];
        }
    }

    public function noticiaGerar(): void
    {
        if (empty($_SESSION['csrf_noticia_auto'])) {
            $_SESSION['csrf_noticia_auto'] = bin2hex(random_bytes(32));
        }
        $service = new NoticiaAutomaticaService($this->leituraModel, $this->config);
        [$tipo, $p, $noticia, $erro] = $this->gerarNoticiaDoPedido($_GET, $service);

        // período para procurar eventos extremos (padrão: últimos 90 dias)
        $valida = fn ($d) => is_string($d) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
        $de = $valida($_GET['de'] ?? null) ? $_GET['de'] : date('Y-m-d', strtotime('-90 days'));
        $ate = $valida($_GET['ate'] ?? null) ? $_GET['ate'] : date('Y-m-d');
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        $this->view('admin/noticias/gerar', [
            'title' => 'Gerar Notícia Automaticamente',
            'activeMenu' => 'noticias',
            'tipo' => $tipo,
            'p' => $p,
            'noticia' => $noticia,
            'erro' => $erro,
            'eventos' => $service->detectarEventos($de, $ate),
            'de' => $de,
            'ate' => $ate,
            'token' => $_SESSION['csrf_noticia_auto'],
        ], 'admin');
    }

    public function noticiaGerarSalvar(): void
    {
        if (empty($_POST['token']) || empty($_SESSION['csrf_noticia_auto']) || !hash_equals($_SESSION['csrf_noticia_auto'], (string) $_POST['token'])) {
            header('Location: /admin/noticias/gerar');
            return;
        }
        // gera de novo no servidor a partir dos parâmetros (os números vêm sempre do banco)
        $service = new NoticiaAutomaticaService($this->leituraModel, $this->config);
        [, , $noticia, $erro] = $this->gerarNoticiaDoPedido($_POST, $service);
        if (!$noticia) {
            header('Location: /admin/noticias/gerar?erro=' . urlencode($erro ?: 'Não foi possível gerar a notícia.'));
            return;
        }
        $agora = date('Y-m-d H:i:s');
        $id = $this->noticiaModel->create([
            'titulo'           => $noticia['titulo'],
            'resumo'           => $noticia['resumo'],
            'conteudo'         => $noticia['conteudo'],
            'imagem'           => null,
            'ativo'            => 0, // rascunho: só aparece no site depois de revisada e ativada
            'data_noticia'     => $agora,
            'data_agendamento' => $agora,
        ]);
        $_SESSION['noticia_gerada'] = 'Rascunho gerado automaticamente a partir dos dados. Revise o texto, adicione uma imagem se quiser e marque "Ativo" para publicar.';
        header('Location: /admin/noticia/edit?id=' . (int) $id);
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

        // ---- filtros do histórico (todos validados antes de entrar na consulta) ----
        $data = function (string $k): string {
            $v = (string) ($_GET[$k] ?? '');
            return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $v : '';
        };
        $f = [
            'inicio'  => $data('inicio'),
            'fim'     => $data('fim'),
            'tipo'    => in_array($_GET['tipo'] ?? '', ['firme', 'chuva', 'melhoria'], true) ? $_GET['tipo'] : '',
            'status'  => in_array($_GET['status'] ?? '', ['acertou', 'errou', 'pendente'], true) ? $_GET['status'] : '',
            'busca'   => mb_substr(trim((string) ($_GET['busca'] ?? '')), 0, 100),
            'chuva'   => !empty($_GET['chuva']) ? '1' : '',
            'ordem'   => ($_GET['ordem'] ?? '') === 'asc' ? 'asc' : 'desc',
            'por'     => in_array($porPagina = (int) ($_GET['por'] ?? 50), [25, 50, 100], true) ? $porPagina : 50,
        ];
        if ($f['inicio'] !== '' && $f['fim'] !== '' && $f['inicio'] > $f['fim']) {
            [$f['inicio'], $f['fim']] = [$f['fim'], $f['inicio']];
        }

        $cond = [];
        $params = [];
        if ($f['inicio'] !== '') { $cond[] = 'created_at >= ?'; $params[] = $f['inicio'] . ' 00:00:00'; }
        if ($f['fim'] !== '')    { $cond[] = 'created_at <= ?'; $params[] = $f['fim'] . ' 23:59:59'; }
        if ($f['tipo'] !== '')   { $cond[] = 'tipo_previsao = ?'; $params[] = $f['tipo']; }
        if ($f['status'] === 'acertou')  { $cond[] = 'acertou = 1'; }
        if ($f['status'] === 'errou')    { $cond[] = 'acertou = 0'; }
        if ($f['status'] === 'pendente') { $cond[] = 'acertou IS NULL'; }
        if ($f['chuva'] !== '')  { $cond[] = 'chuva_registrada > 0'; }
        if ($f['busca'] !== '') {
            $cond[] = 'mensagem LIKE ?';
            $params[] = '%' . addcslashes($f['busca'], '%_\\') . '%';
        }
        $where = $cond ? ' WHERE ' . implode(' AND ', $cond) : '';
        $bind = $params ?: null; // a biblioteca não aceita lista vazia de parâmetros

        $resumo = $this->db->rawQueryOne(
            "SELECT COUNT(*) AS total, SUM(acertou = 1) AS acertos, SUM(acertou = 0) AS erros, SUM(acertou IS NULL) AS pendentes
             FROM nowcasting{$where}",
            $bind
        ) ?: ['total' => 0, 'acertos' => 0, 'erros' => 0, 'pendentes' => 0];

        $total = (int) $resumo['total'];
        $paginas = max(1, (int) ceil($total / $f['por']));
        $pagina = min($paginas, max(1, (int) ($_GET['pagina'] ?? 1)));
        $offset = ($pagina - 1) * $f['por'];
        $ordem = $f['ordem'] === 'asc' ? 'ASC' : 'DESC';

        $historico = $this->db->rawQuery(
            "SELECT * FROM nowcasting{$where} ORDER BY created_at {$ordem}, id {$ordem} LIMIT {$f['por']} OFFSET {$offset}",
            $bind
        );

        $this->view('admin/nowcasting', [
            'activeMenu' => 'nowcasting',
            'title' => 'Estatísticas de Nowcasting',
            'stats' => $stats,
            'historico' => $historico,
            'filtros' => $f,
            'resumoFiltro' => $resumo,
            'filtrado' => (bool) $cond,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'totalFiltro' => $total,
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
        $otpUri = (new TotpService())->getOtpAuthUri($secret, $user['email'], $this->config['app']['nome_sistema'] ?? 'Clima');

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
        $otpUri = (new TotpService())->getOtpAuthUri($secret, $user['email'], $this->config['app']['nome_sistema'] ?? 'Clima');
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
                . "<p><a href=\"https://{$this->config['app']['dominio']}/pluviometro-cidadao/login\">Acessar meu painel</a></p>";
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

    // === FONTE DE DADOS DA ESTAÇÃO (spoofing vs Wunderground API) ===

    public function estacaoConfig(): void
    {
        $configModel = new EstacaoConfigModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fonte = ($_POST['fonte_dados'] ?? '') === 'wunderground' ? 'wunderground' : 'spoofing';
            $stationId = trim($_POST['wunderground_station_id'] ?? '');
            $frequencia = max(1, (int) ($_POST['frequencia_minutos'] ?? 5));

            // A chave nunca volta pro navegador: o campo vem vazio. Vazio = mantém a chave salva;
            // só troca se digitarem uma nova, e só apaga marcando "remover chave salva".
            $chaveAtual = (string) ($configModel->getConfig()['wunderground_api_key'] ?? '');
            $chaveDigitada = trim($_POST['wunderground_api_key'] ?? '');
            if (!empty($_POST['remover_api_key'])) {
                $apiKey = '';
            } elseif ($chaveDigitada !== '') {
                $apiKey = $chaveDigitada;
            } else {
                $apiKey = $chaveAtual;
            }

            $erro = null;
            if ($fonte === 'wunderground' && ($apiKey === '' || $stationId === '')) {
                $erro = 'Pra usar a fonte Wunderground, informe a API Key e o Station ID.';
            }

            if (!$erro) {
                $configModel->atualizar([
                    'fonte_dados' => $fonte,
                    'wunderground_api_key' => $apiKey,
                    'wunderground_station_id' => $stationId,
                    'frequencia_minutos' => $frequencia,
                ]);
                $_SESSION['estacao_config_sucesso'] = 'Configuração salva com sucesso.';
                header('Location: /admin/estacao');
                return;
            }

            $this->view('admin/estacao', [
                'title' => 'Fonte de Dados da Estação',
                'activeMenu' => 'estacao',
                'estacaoConfig' => $configModel->getConfig(),
                'erro' => $erro,
            ], 'admin');
            return;
        }

        $this->view('admin/estacao', [
            'title' => 'Fonte de Dados da Estação',
            'activeMenu' => 'estacao',
            'estacaoConfig' => $configModel->getConfig(),
        ], 'admin');
    }

    public function estacaoTestar(): void
    {
        $configModel = new EstacaoConfigModel();
        $config = $configModel->getConfig();

        if (empty($config['wunderground_api_key']) || empty($config['wunderground_station_id'])) {
            $_SESSION['estacao_teste'] = ['sucesso' => false, 'erro' => 'Preencha e salve a API Key e o Station ID primeiro.'];
            header('Location: /admin/estacao');
            return;
        }

        try {
            $dados = (new WundergroundService())->buscarCondicaoAtual(
                $config['wunderground_api_key'],
                $config['wunderground_station_id']
            );
            $_SESSION['estacao_teste'] = ['sucesso' => true, 'dados' => $dados];
        } catch (\Throwable $e) {
            $_SESSION['estacao_teste'] = ['sucesso' => false, 'erro' => $e->getMessage()];
        }

        header('Location: /admin/estacao');
    }

    // ------------------------------------------------------------------
    // Exportação de dados (CSV / SQL) — só administradores
    // ------------------------------------------------------------------

    private function ehAdmin(): bool
    {
        return !empty($_SESSION['is_admin']);
    }

    public function exportar(): void
    {
        if (!$this->ehAdmin()) {
            $this->view('admin/exportar/index', [
                'activeMenu' => 'exportar',
                'title' => 'Exportar Dados',
                'semPermissao' => true,
            ], 'admin');
            return;
        }

        if (empty($_SESSION['csrf_exportar'])) {
            $_SESSION['csrf_exportar'] = bin2hex(random_bytes(32));
        }
        $service = new ExportacaoService($this->db);
        $periodos = $GLOBALS['cache']->remember('exportar_periodos_v1', 3600, fn () => $service->periodos());

        $this->view('admin/exportar/index', [
            'activeMenu' => 'exportar',
            'title' => 'Exportar Dados',
            'semPermissao' => false,
            'tabelas' => ExportacaoService::TABELAS,
            'periodos' => $periodos,
            'token' => $_SESSION['csrf_exportar'],
            'erro' => isset($_GET['erro']) ? (string) $_GET['erro'] : null,
        ], 'admin');
    }

    public function exportarBaixar(): void
    {
        if (!$this->ehAdmin()) {
            http_response_code(403);
            echo 'Acesso restrito a administradores.';
            return;
        }
        if (empty($_POST['token']) || empty($_SESSION['csrf_exportar']) || !hash_equals($_SESSION['csrf_exportar'], (string) $_POST['token'])) {
            header('Location: /admin/exportar?erro=' . urlencode('Sessão expirada. Recarregue a página e tente de novo.'));
            return;
        }
        [$erro, $p] = ExportacaoService::validar($_POST);
        if ($erro) {
            header('Location: /admin/exportar?erro=' . urlencode($erro));
            return;
        }

        // libera a sessão (senão outras abas do admin travam durante um download longo)
        session_write_close();
        @set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $service = new ExportacaoService($this->db);
        $nome = $service->nomeArquivo($p);
        $tipo = $p['gzip'] ? 'application/gzip' : ($p['formato'] === 'csv' ? 'text/csv; charset=utf-8' : 'application/sql; charset=utf-8');
        header('Content-Type: ' . $tipo);
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'wb');
        if ($p['gzip']) {
            // window 31 = formato gzip (abre no 7-Zip, WinRAR, gunzip)
            stream_filter_append($out, 'zlib.deflate', STREAM_FILTER_WRITE, ['window' => 31, 'level' => 6]);
        }
        try {
            $service->exportar($p, $out);
        } catch (\Throwable $e) {
            fwrite($out, "\n-- ERRO NA EXPORTAÇÃO: " . $e->getMessage() . "\n");
        }
        fclose($out);
        exit;
    }

    public function exportarContar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->ehAdmin()) {
            http_response_code(403);
            echo json_encode(['erro' => 'Acesso restrito a administradores.']);
            return;
        }
        [$erro, $p] = ExportacaoService::validar($_GET);
        if ($erro) {
            echo json_encode(['erro' => $erro]);
            return;
        }
        @set_time_limit(120);
        $service = new ExportacaoService($this->db);
        echo json_encode([
            'linhas' => $service->contar($p),
            'filtros' => $service->descreverFiltros($p),
            'arquivo' => $service->nomeArquivo($p),
        ], JSON_UNESCAPED_UNICODE);
    }
}
