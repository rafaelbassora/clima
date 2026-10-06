<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\UserModel;
use App\Services\TotpService;

class LoginController extends Controller
{
    public function index(): void
    {
        if (isset($_SESSION['usuario'])) {
            header('Location: /admin');
            return;
        }
        $this->view('login/index', ['title' => 'Login - Admin'], 'none');
    }

    public function auth(): void
    {
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';

        // 1. Verificar Turnstile
        $secret = $this->config['admin']['turnstile_secret'] ?? '';
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        
        if (!function_exists('curl_init')) {
            $this->view('login/index', [
                'title' => 'Login - Admin',
                'error' => 'Erro interno: Extensão CURL do PHP não está ativa.'
            ], 'none');
            return;
        }

        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita falha por falta de CA Certs no Windows
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'secret'   => $secret,
            'response' => $turnstileResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $responseJson = curl_exec($ch);
        $curlError    = curl_error($ch);
        curl_close($ch);
        
        $result = json_decode($responseJson, true);

        if (!$result || !$result['success']) {
            $errorMsg = 'Por favor, confirme que você não é um robô (Turnstile).';
            if ($curlError) {
                $errorMsg = 'Erro na conexão com Cloudflare: ' . $curlError;
            }
            
            $this->view('login/index', [
                'title' => 'Login - Admin',
                'error' => $errorMsg
            ], 'none');
            return;
        }

        // 2. Autenticação contra a tabela usuarios
        $user = (new UserModel())->findByEmail($usuario);
        if (!$user || !password_verify($senha, $user['password'])) {
            $this->view('login/index', [
                'title' => 'Login - Admin',
                'error' => 'Usuário ou senha inválidos.'
            ], 'none');
            return;
        }

        // 3. Se o usuário tiver 2FA ativado, exige o código antes de abrir a sessão
        if (!empty($user['totp_enabled']) && !empty($user['totp_secret'])) {
            $_SESSION['2fa_pending_id'] = $user['id'];
            header('Location: /login/2fa');
            return;
        }

        $this->iniciarSessao($user);
    }

    public function twofa(): void
    {
        if (!isset($_SESSION['2fa_pending_id'])) {
            header('Location: /login');
            return;
        }
        $this->view('login/2fa', ['title' => 'Verificação em Duas Etapas'], 'none');
    }

    public function twofaVerify(): void
    {
        if (!isset($_SESSION['2fa_pending_id'])) {
            header('Location: /login');
            return;
        }

        $userId = (int) $_SESSION['2fa_pending_id'];
        $user = (new UserModel())->getById($userId);
        $codigo = $_POST['codigo'] ?? '';

        if ($user && !empty($user['totp_secret']) && (new TotpService())->verifyCode($user['totp_secret'], $codigo)) {
            unset($_SESSION['2fa_pending_id']);
            $this->iniciarSessao($user);
            return;
        }

        $this->view('login/2fa', [
            'title' => 'Verificação em Duas Etapas',
            'error' => 'Código inválido ou expirado.'
        ], 'none');
    }

    private function iniciarSessao(array $user): void
    {
        $_SESSION['usuario'] = $user['name'];
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        header('Location: /admin');
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
    }
}
