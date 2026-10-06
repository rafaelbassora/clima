<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\CidadaoModel;
use App\Models\ChuvaCidadaModel;
use App\Services\CepService;
use App\Services\MailerService;

class CidadaoController extends Controller
{
    private CidadaoModel $model;
    private ChuvaCidadaModel $chuvaModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new CidadaoModel();
        $this->chuvaModel = new ChuvaCidadaModel();
    }

    public function index(): void
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        $resumoMensal = $this->chuvaModel->getResumoMensalPublico($year, $month);
        $mediaCidade = count($resumoMensal) > 0
            ? array_sum(array_column($resumoMensal, 'total_mm')) / count($resumoMensal)
            : 0;

        $this->view('cidadao/index', [
            'pageTitle' => 'Pluviômetro Cidadão',
            'mediaCidade' => $mediaCidade,
            'totalEstacoes' => count($resumoMensal),
            'amostra' => $this->chuvaModel->getAmostraStatusHoje(10),
            'mesAno' => $month . '/' . $year,
        ], 'main');
    }

    public function estacao(): void
    {
        $codigo = $this->getParam('codigo', '');
        $estacao = $codigo ? $this->model->findEstacaoAprovadaPorCodigo($codigo) : null;

        if (!$estacao) {
            header('Location: /pluviometro-cidadao');
            return;
        }

        $this->view('cidadao/estacao', [
            'pageTitle' => 'Estação ' . $estacao['station_code'],
            'estacao' => $estacao,
            'historico' => $this->chuvaModel->getHistoricoPorEstacao($codigo),
        ], 'main');
    }

    public function cadastro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarCadastro();
            return;
        }
        $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro'], 'main');
    }

    /**
     * Verifica o desafio Cloudflare Turnstile enviado no POST.
     */
    private function verificarTurnstile(): bool
    {
        $secret = $this->config['admin']['turnstile_secret'] ?? '';
        $resposta = $_POST['cf-turnstile-response'] ?? '';

        if (!$secret || !$resposta || !function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'secret' => $secret,
            'response' => $resposta,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $json = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($json, true);
        return !empty($result['success']);
    }

    private function processarCadastro(): void
    {
        if (!$this->verificarTurnstile()) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'Por favor, confirme que você não é um robô.'], 'main');
            return;
        }

        $tipo = $_POST['tipo'] ?? 'pf';
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $cep = $_POST['cep'] ?? '';

        $cepInfo = (new CepService())->consultar($cep);

        if (!$cepInfo['valido']) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => $cepInfo['erro']], 'main');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'E-mail inválido.'], 'main');
            return;
        }

        if (strlen($senha) < 8) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'A senha deve ter pelo menos 8 caracteres.'], 'main');
            return;
        }

        if ($this->model->emailExists($email)) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'Já existe um cadastro com este e-mail.'], 'main');
            return;
        }

        $celular = preg_replace('/\D/', '', $_POST['celular'] ?? '');
        if (strlen($celular) < 10) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'Informe um celular válido com DDD.'], 'main');
            return;
        }

        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        if ($tipo !== 'pj' && strlen($cpf) !== 11) {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'Informe um CPF válido (11 dígitos).'], 'main');
            return;
        }

        $data = [
            'tipo' => $tipo === 'pj' ? 'pj' : 'pf',
            'nome' => trim($_POST['nome'] ?? ''),
            'usa_nome_social' => isset($_POST['usa_nome_social']),
            'nome_social' => trim($_POST['nome_social'] ?? ''),
            'sexo' => $_POST['sexo'] ?? '',
            'data_nascimento' => $_POST['data_nascimento'] ?? '',
            'cnpj' => $tipo === 'pj' ? preg_replace('/\D/', '', $_POST['cnpj'] ?? '') : '',
            'cpf' => $tipo !== 'pj' ? $cpf : '',
            'celular' => $celular,
            'email' => $email,
            'senha' => $senha,
            'cep' => $cep,
            'bairro' => $cepInfo['bairro'],
            'cidade' => $cepInfo['cidade'],
            'uf' => $cepInfo['uf'],
        ];

        if ($data['nome'] === '') {
            $this->view('cidadao/cadastro', ['pageTitle' => 'Cadastrar meu Pluviômetro', 'erro' => 'Nome é obrigatório.'], 'main');
            return;
        }

        $this->model->criar($data);
        $this->view('cidadao/cadastro-sucesso', ['pageTitle' => 'Cadastro Recebido'], 'main');
    }

    public function cepLookup(): void
    {
        $cep = $this->getParam('cep', '');
        $resultado = (new CepService())->consultar($cep);
        $this->json($resultado);
    }

    public function login(): void
    {
        if (isset($_SESSION['cidadao_id'])) {
            header('Location: /pluviometro-cidadao/painel');
            return;
        }
        $this->view('cidadao/login', ['pageTitle' => 'Entrar - Pluviômetro Cidadão'], 'main');
    }

    public function loginAuth(): void
    {
        if (!$this->verificarTurnstile()) {
            $this->view('cidadao/login', ['pageTitle' => 'Entrar - Pluviômetro Cidadão', 'erro' => 'Por favor, confirme que você não é um robô.'], 'main');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $cidadao = $this->model->findByEmail($email);

        if (!$cidadao || !password_verify($senha, $cidadao['senha'])) {
            $this->view('cidadao/login', ['pageTitle' => 'Entrar - Pluviômetro Cidadão', 'erro' => 'E-mail ou senha inválidos.'], 'main');
            return;
        }

        if ($cidadao['status'] === 'pendente') {
            $this->view('cidadao/login', ['pageTitle' => 'Entrar - Pluviômetro Cidadão', 'erro' => 'Seu cadastro ainda está em análise. Avisaremos por e-mail quando for aprovado.'], 'main');
            return;
        }

        if ($cidadao['status'] === 'rejeitado') {
            $this->view('cidadao/login', ['pageTitle' => 'Entrar - Pluviômetro Cidadão', 'erro' => 'Seu cadastro não foi aprovado. Entre em contato com a prefeitura para mais informações.'], 'main');
            return;
        }

        $_SESSION['cidadao_id'] = $cidadao['id'];
        $_SESSION['cidadao_nome'] = (!empty($cidadao['usa_nome_social']) && !empty($cidadao['nome_social']))
            ? $cidadao['nome_social']
            : $cidadao['nome'];
        $_SESSION['cidadao_station'] = $cidadao['station_code'];
        header('Location: /pluviometro-cidadao/painel');
    }

    public function logout(): void
    {
        unset($_SESSION['cidadao_id'], $_SESSION['cidadao_nome'], $_SESSION['cidadao_station']);
        header('Location: /pluviometro-cidadao/login');
    }

    public function painel(): void
    {
        if (!$this->exigirLogin()) {
            return;
        }

        $historico = $this->chuvaModel->getHistorico((int) $_SESSION['cidadao_id']);

        $this->view('cidadao/painel', [
            'pageTitle' => 'Meu Painel - Pluviômetro Cidadão',
            'historico' => $historico,
        ], 'main');
    }

    public function painelLancar(): void
    {
        if (!$this->exigirLogin()) {
            return;
        }

        $data = $_POST['data'] ?? date('Y-m-d');
        $medida = (float) str_replace(',', '.', $_POST['medida_mm'] ?? '0');
        $granizo = isset($_POST['granizo']);
        $chuvaMansa = isset($_POST['chuva_mansa']);
        $chuvaForte = isset($_POST['chuva_forte']);
        $observacao = trim($_POST['observacao'] ?? '');

        if ($medida < 0 || $medida > 500 || $data > date('Y-m-d')) {
            header('Location: /pluviometro-cidadao/painel');
            return;
        }

        $this->chuvaModel->lancar((int) $_SESSION['cidadao_id'], $data, $medida, $granizo, $chuvaMansa, $chuvaForte, $observacao);
        header('Location: /pluviometro-cidadao/painel');
    }

    public function painelEditar(): void
    {
        if (!$this->exigirLogin()) {
            return;
        }

        $id = (int) $this->getParam('id');
        $cidadaoId = (int) $_SESSION['cidadao_id'];
        $item = $this->chuvaModel->getByIdDoCidadao($id, $cidadaoId);

        if (!$item) {
            header('Location: /pluviometro-cidadao/painel');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST['data'] ?? $item['data'];
            $medida = (float) str_replace(',', '.', $_POST['medida_mm'] ?? '0');
            $granizo = isset($_POST['granizo']);
            $chuvaMansa = isset($_POST['chuva_mansa']);
            $chuvaForte = isset($_POST['chuva_forte']);
            $observacao = trim($_POST['observacao'] ?? '');

            if ($medida < 0 || $medida > 500 || $data > date('Y-m-d')) {
                header('Location: /pluviometro-cidadao/painel');
                return;
            }

            $this->chuvaModel->atualizar($id, $cidadaoId, $data, $medida, $granizo, $chuvaMansa, $chuvaForte, $observacao);
            header('Location: /pluviometro-cidadao/painel');
            return;
        }

        $this->view('cidadao/editar', [
            'pageTitle' => 'Editar Lançamento',
            'item' => $item,
        ], 'main');
    }

    public function painelPerfil(): void
    {
        if (!$this->exigirLogin()) {
            return;
        }

        $cidadaoId = (int) $_SESSION['cidadao_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $celular = preg_replace('/\D/', '', $_POST['celular'] ?? '');
            $nome = trim($_POST['nome'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->view('cidadao/perfil', ['pageTitle' => 'Meus Dados', 'erro' => 'E-mail inválido.', 'item' => $this->model->getById($cidadaoId)], 'main');
                return;
            }
            if (strlen($celular) < 10) {
                $this->view('cidadao/perfil', ['pageTitle' => 'Meus Dados', 'erro' => 'Informe um celular válido com DDD.', 'item' => $this->model->getById($cidadaoId)], 'main');
                return;
            }
            if ($nome === '') {
                $this->view('cidadao/perfil', ['pageTitle' => 'Meus Dados', 'erro' => 'Nome é obrigatório.', 'item' => $this->model->getById($cidadaoId)], 'main');
                return;
            }
            if ($this->model->emailExists($email, $cidadaoId)) {
                $this->view('cidadao/perfil', ['pageTitle' => 'Meus Dados', 'erro' => 'Já existe outro cadastro com este e-mail.', 'item' => $this->model->getById($cidadaoId)], 'main');
                return;
            }

            $cidadaoAtual = $this->model->getById($cidadaoId);
            $trocandoEmail = $email !== $cidadaoAtual['email'];

            $this->model->atualizarPerfil($cidadaoId, [
                'nome' => $nome,
                'usa_nome_social' => isset($_POST['usa_nome_social']),
                'nome_social' => trim($_POST['nome_social'] ?? ''),
                'sexo' => $_POST['sexo'] ?? '',
                'data_nascimento' => $_POST['data_nascimento'] ?? '',
                'celular' => $celular,
            ]);

            $_SESSION['cidadao_nome'] = (isset($_POST['usa_nome_social']) && trim($_POST['nome_social'] ?? '') !== '')
                ? trim($_POST['nome_social'])
                : $nome;

            $mensagem = 'Dados atualizados com sucesso.';

            if ($trocandoEmail) {
                $token = $this->model->solicitarTrocaEmail($cidadaoId, $email);
                $base = $this->urlBase();
                $link = "{$base}/pluviometro-cidadao/confirmar-email?token={$token}";
                $corpo = "<p>Você solicitou a troca do e-mail de acesso no Pluviômetro Cidadão.</p>"
                    . "<p><a href=\"{$link}\">Clique aqui para confirmar o novo e-mail</a></p>"
                    . "<p>Este link expira em 1 hora. Enquanto não for confirmado, o e-mail antigo continua valendo pro login.</p>";
                (new MailerService())->enviar($email, $nome, 'Confirme seu novo e-mail - Pluviômetro Cidadão', $corpo);
                $mensagem .= ' Enviamos um link de confirmação para o novo e-mail — ele só passa a valer depois de confirmado.';
            }

            $this->view('cidadao/perfil', ['pageTitle' => 'Meus Dados', 'sucesso' => $mensagem, 'item' => $this->model->getById($cidadaoId)], 'main');
            return;
        }

        $this->view('cidadao/perfil', [
            'pageTitle' => 'Meus Dados',
            'item' => $this->model->getById($cidadaoId),
        ], 'main');
    }

    public function confirmarEmail(): void
    {
        $token = $this->getParam('token', '');
        $cidadao = $token ? $this->model->confirmarTrocaEmail($token) : null;

        $this->view('cidadao/confirmar-email', [
            'pageTitle' => 'Confirmação de E-mail',
            'sucesso' => (bool) $cidadao,
        ], 'main');
    }

    public function painelExcluir(): void
    {
        if (!$this->exigirLogin()) {
            return;
        }

        $id = (int) $this->getParam('id');
        $this->chuvaModel->excluir($id, (int) $_SESSION['cidadao_id']);
        header('Location: /pluviometro-cidadao/painel');
    }

    private function exigirLogin(): bool
    {
        if (!isset($_SESSION['cidadao_id'])) {
            header('Location: /pluviometro-cidadao/login');
            return false;
        }
        return true;
    }

    public function esqueciSenha(): void
    {
        $this->view('cidadao/esqueci-senha', ['pageTitle' => 'Recuperar Senha'], 'main');
    }

    public function esqueciSenhaEnviar(): void
    {
        $email = trim($_POST['email'] ?? '');
        $documento = preg_replace('/\D/', '', $_POST['documento'] ?? '');

        if ($email !== '' && $documento !== '') {
            $token = $this->model->gerarTokenReset($email, $documento);
            if ($token) {
                $base = $this->urlBase();
                $link = "{$base}/pluviometro-cidadao/redefinir-senha?token={$token}";
                $corpo = "<p>Você solicitou a redefinição de senha do Pluviômetro Cidadão.</p>"
                    . "<p><a href=\"{$link}\">Clique aqui para criar uma nova senha</a></p>"
                    . "<p>Este link expira em 1 hora.</p>";
                (new MailerService())->enviar($email, '', 'Redefinição de Senha - Pluviômetro Cidadão', $corpo);
            }
        }

        $this->view('cidadao/esqueci-senha', [
            'pageTitle' => 'Recuperar Senha',
            'sucesso' => 'Se o e-mail e o CPF/CNPJ informados baterem com um cadastro, você receberá um link de redefinição em instantes.',
        ], 'main');
    }

    private function urlBase(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public function redefinirSenha(): void
    {
        $token = $this->getParam('token', '');
        $cidadao = $token ? $this->model->findByResetToken($token) : null;

        $this->view('cidadao/redefinir-senha', [
            'pageTitle' => 'Redefinir Senha',
            'tokenInvalido' => !$cidadao,
            'token' => $token,
        ], 'main');
    }

    public function redefinirSenhaSalvar(): void
    {
        $token = $_POST['token'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmacao = $_POST['senha_confirmacao'] ?? '';

        $cidadao = $token ? $this->model->findByResetToken($token) : null;

        if (!$cidadao) {
            $this->view('cidadao/redefinir-senha', ['pageTitle' => 'Redefinir Senha', 'tokenInvalido' => true], 'main');
            return;
        }

        if (strlen($senha) < 8 || $senha !== $confirmacao) {
            $this->view('cidadao/redefinir-senha', [
                'pageTitle' => 'Redefinir Senha',
                'token' => $token,
                'erro' => 'As senhas devem ser iguais e ter pelo menos 8 caracteres.',
            ], 'main');
            return;
        }

        $this->model->redefinirSenha($cidadao['id'], $senha);
        header('Location: /pluviometro-cidadao/login?redefinida=1');
    }
}
