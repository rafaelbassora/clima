<?php

namespace App\Controllers;

use Core\Controller;

class PrivacidadeController extends Controller
{
    public function index(): void
    {
        $this->view('privacidade/index', [
            'pageTitle'  => 'Política de Privacidade',
            'activeMenu' => 'privacidade',
        ]);
    }
}
