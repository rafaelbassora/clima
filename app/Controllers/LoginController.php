<?php

namespace App\Controllers;

use Core\Controller;

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

        // 2. Autenticação
        $adminUsuario = $this->config['admin']['usuario'] ?? null;
        $adminSenha = $this->config['admin']['senha'] ?? null;
        if ($adminUsuario && $usuario === $adminUsuario && $senha === $adminSenha) {
            $_SESSION['usuario'] = $adminUsuario;
            header('Location: /admin');
        } else {
            $this->view('login/index', [
                'title' => 'Login - Admin',
                'error' => 'Usuário ou senha inválidos.'
            ], 'none');
        }
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
    }
}
