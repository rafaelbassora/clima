<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    private array $config;

    public function __construct()
    {
        $this->config = $GLOBALS['config']['mail'] ?? [];
    }

    public function enviar(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = (bool) ($this->config['smtp_auth'] ?? true);
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) ($this->config['port'] ?? 587);
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($this->config['from_email'], $this->config['from_name'] ?? '');
            $mail->addAddress($paraEmail, $paraNome);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpoHtml;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('MailerService: falha ao enviar e-mail: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
