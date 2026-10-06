<?php

return [
    // Aplicação
    'app_name'  => 'Clima Pereira Barreto',
    'app_title' => 'Clima Pereira Barreto - SMCPB',
    'timezone'  => 'America/Sao_Paulo',
    'base_url'  => '/',

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
        'from_name' => 'Clima Pereira Barreto',
    ],

    // Ambiente
    'ambiente_testes' => 0, // 1 = exibe barra de desenvolvimento
];
