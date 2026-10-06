<?php

namespace App\Controllers;

use Core\Controller;

class TermosController extends Controller
{
    public function index(): void
    {
        $data = [
            'pageTitle'  => 'Termos de Uso',
            'activeMenu' => 'termos',
        ];

        $this->view('termos/index', $data);
    }
}
