<?php

namespace App\Services;

class CepService
{
    private string $cidadePermitida;
    private string $cidadeNomeExibicao;
    private string $ufPermitida;

    public function __construct()
    {
        $config = $GLOBALS['config']['cidade'] ?? [];
        $this->cidadeNomeExibicao = $config['nome'] ?? 'Pereira Barreto';
        $this->cidadePermitida = $this->normalizar($this->cidadeNomeExibicao);
        $this->ufPermitida = strtoupper($config['uf'] ?? 'SP');
    }

    /**
     * Consulta o CEP na API pública ViaCEP e retorna os dados,
     * validando se pertence à cidade configurada (ver config['cidade']).
     */
    public function consultar(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return ['valido' => false, 'erro' => 'CEP inválido.'];
        }

        $ch = curl_init("https://viacep.com.br/ws/{$cep}/json/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $response = curl_exec($ch);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($erroCurl || !$response) {
            return ['valido' => false, 'erro' => 'Não foi possível consultar o CEP agora. Tente novamente.'];
        }

        $data = json_decode($response, true);

        if (!$data || !empty($data['erro'])) {
            return ['valido' => false, 'erro' => 'CEP não encontrado.'];
        }

        $cidade = $this->normalizar($data['localidade'] ?? '');
        $uf = strtoupper($data['uf'] ?? '');

        if ($cidade !== $this->cidadePermitida || $uf !== $this->ufPermitida) {
            return ['valido' => false, 'erro' => "Este programa é exclusivo para moradores de {$this->cidadeNomeExibicao} - {$this->ufPermitida}."];
        }

        return [
            'valido' => true,
            'bairro' => $data['bairro'] ?: 'Centro',
            'cidade' => $data['localidade'],
            'uf' => $uf,
        ];
    }

    private function normalizar(string $texto): string
    {
        $texto = strtoupper($texto);
        $map = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'É' => 'E', 'Ê' => 'E',
            'Í' => 'I',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U',
            'Ç' => 'C',
        ];
        return strtr($texto, $map);
    }
}
