<?php

namespace App\Controllers;

use Core\Controller;

class SobreController extends Controller
{
    public function index(): void
    {
        $data = [
            'pageTitle'  => 'Sobre Nós',
            'activeMenu' => 'sobre',
        ];

        $this->view('sobre/index', $data);
    }
}
