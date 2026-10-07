<?php

return [
    // Aplicação (retrocompatibilidade — preferir 'app' abaixo)
    'app_name'  => 'CHANGE_ME',
    'app_title' => 'CHANGE_ME',
    'timezone'  => 'America/Sao_Paulo',
    'base_url'  => '/',

    // Identidade da cidade — isso é o que muda pra cada prefeitura.
    // O 'ibge' é o código de 7 dígitos do IBGE (necessário pra previsão via INMET).
    // Consulte em: https://www.ibge.gov.br/explica/codigos-dos-municipios.php
    'cidade' => [
        'nome' => 'CHANGE_ME', // Ex: "Pereira Barreto"
        'uf'   => 'CHANGE_ME', // Ex: "SP"
        'ibge' => 'CHANGE_ME', // Ex: "3537404"
    ],

    // Identidade do sistema/app
    'app' => [
        'nome_sistema' => 'CHANGE_ME', // Nome completo, ex: "Clima Pereira Barreto"
        'nome_curto'   => 'CHANGE_ME', // Nome curto pro menu/header, ex: "Clima PBTO"
        'sigla'        => 'CHANGE_ME', // Ex: "SMCPB"
        'titulo'       => 'CHANGE_ME', // Título das páginas (<title>)
        'dominio'      => 'CHANGE_ME', // Ex: "clima.suacidade.sp.gov.br" (sem https://)
    ],

    // Marca/visual — logos, cores e créditos institucionais
    'branding' => [
        'favicon'         => '/img/logo.png', // Ícone usado no favicon e no preview de compartilhamento (og:image)
        'logo_prefeitura' => '/img/logo.png', // Logo da prefeitura exibido no rodapé/parceiros
        'cor_primaria'    => '#0d6efd',
        'cor_destaque'    => '#ff6b35',
        'footer_creditos' => 'CHANGE_ME', // Texto livre de créditos (HTML permitido)
        'footer_aviso'    => 'CHANGE_ME', // Aviso legal/institucional no rodapé
    ],

    // Parceiros institucionais exibidos no rodapé (logo relativo a /public).
    // Pode deixar como array vazio [] se não houver parceiros.
    'parceiros' => [
        // ['nome' => 'Nome do Parceiro', 'logo' => '/img/logo-parceiro.png'],
    ],

    // SEO
    'seo' => [
        'meta_description' => 'CHANGE_ME',
    ],

    // Banco de dados
    'db' => [
        'host'     => 'localhost',
        'username' => 'CHANGE_ME',
        'password' => 'CHANGE_ME',
        'database' => 'dados',
        'charset'  => 'utf8mb4',
    ],

    // Cache
    'cache' => [
        'enabled'    => true,
        'path'       => __DIR__ . '/../cache/',
        'ttl_rapido' => 15,   // Real-time (15 seg)
        'ttl_hist'   => 3600, // Histórico (1 hora)
    ],

    // AJAX refresh (em milissegundos)
    'ajax_refresh_ms' => 30000,

    // Google Analytics
    'ga_id' => 'CHANGE_ME',

    // Segurança Cron
    'cron_token' => 'CHANGE_ME',

    // Chave de autenticação — Umidade do Solo
    'CHAVE_UMIDADE' => 'CHANGE_ME',

    // Login admin
    'admin' => [
        'turnstile_secret' => 'CHANGE_ME',
    ],

    // Servidor de E-mail (SMTP) — usado para recuperação de senha
    'mail' => [
        'host' => 'CHANGE_ME',
        'username' => 'CHANGE_ME',
        'password' => 'CHANGE_ME',
        'smtp_auth' => true,
        'smtp_secure' => 'tls',
        'port' => 587,
        'from_email' => 'CHANGE_ME',
        'from_name' => 'CHANGE_ME',
    ],

    // Ambiente
    'ambiente_testes' => 0, // 1 = exibe barra de desenvolvimento
];
