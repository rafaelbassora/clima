<?php

namespace App\Services;

/**
 * TOTP (RFC 6238) compatível com Google Authenticator / Authy / etc, sem dependências externas.
 */
class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return $this->base32Encode($bytes);
    }

    /**
     * Monta a URI otpauth:// usada para gerar o QR Code (lido pelo app autenticador).
     */
    public function getOtpAuthUri(string $secret, string $label, string $issuer = 'Clima'): string
    {
        $label = rawurlencode($issuer . ':' . $label);
        $issuerEnc = rawurlencode($issuer);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEnc}&period=" . self::PERIOD . "&digits=" . self::DIGITS;
    }

    /**
     * Verifica um código de 6 dígitos, tolerando 1 período (30s) de diferença pra frente/trás.
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateCode($secret, $currentTimeSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private function generateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $part1 = ord($hash[$offset]) & 0x7F;
        $part2 = ord($hash[$offset + 1]) & 0xFF;
        $part3 = ord($hash[$offset + 2]) & 0xFF;
        $part4 = ord($hash[$offset + 3]) & 0xFF;

        $value = ($part1 << 24) | ($part2 << 16) | ($part3 << 8) | $part4;
        $modulo = 10 ** self::DIGITS;

        return str_pad((string) ($value % $modulo), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    private function base32Decode(string $data): string
    {
        $data = strtoupper(rtrim($data, '='));
        $binaryString = '';

        foreach (str_split($data) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
