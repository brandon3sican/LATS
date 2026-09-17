<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class Google2faService
{
    private const SECRET_LENGTH = 32;
    private const CODE_LENGTH = 6; // Standard Google Authenticator uses 6 digits
    private const WINDOW = 1; // Allow 1 step before/after for time drift

    /**
     * Generate a random secret key for Google Authenticator
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(self::SECRET_LENGTH));
    }

    /**
     * Generate a TOTP code for a given secret
     */
    public function generateCode(string $secret): string
    {
        $time = floor(time() / 30);
        return $this->generateCodeAtTime($secret, $time);
    }

    /**
     * Generate a TOTP code for a specific time counter
     */
    private function generateCodeAtTime(string $secret, int $time): string
    {
        $secret = $this->base32Decode($secret);
        $timeBytes = pack('N', $time);
        $timeBytes = str_pad($timeBytes, 8, "\0", STR_PAD_LEFT);

        $hash = hash_hmac('sha1', $timeBytes, $secret, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;

        $binary = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % pow(10, self::CODE_LENGTH);

        return str_pad((string)$otp, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a TOTP code against a secret
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $time = floor(time() / 30);

        // Check current time and surrounding windows for time drift
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $generatedCode = $this->generateCodeAtTime($secret, $time + $i);
            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate QR code URL for Google Authenticator setup
     */
    public function getQrCodeUrl(string $secret, string $email, string $appName = 'LATS'): string
    {
        $encodedSecret = rawurlencode($secret);
        $encodedEmail = rawurlencode($email);
        $encodedAppName = rawurlencode($appName);

        return "otpauth://totp/{$encodedAppName}:{$encodedEmail}?secret={$encodedSecret}&issuer={$encodedAppName}";
    }

    /**
     * Encrypt secret for storage
     */
    public function encryptSecret(string $secret): string
    {
        return Crypt::encrypt($secret);
    }

    /**
     * Decrypt secret from storage
     */
    public function decryptSecret(string $encryptedSecret): string
    {
        return Crypt::decrypt($encryptedSecret);
    }

    /**
     * Base32 encode
     */
    private function base32Encode(string $data): string
    {
        $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $v <<= 8;
            $v += ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $base32[$v >> $vbits];
                $v &= ((1 << $vbits) - 1);
            }
        }

        if ($vbits > 0) {
            $v <<= (5 - $vbits);
            $output .= $base32[$v];
        }

        return $output;
    }

    /**
     * Base32 decode
     */
    private function base32Decode(string $data): string
    {
        $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $pos = strpos($base32, $char);

            if ($pos === false) {
                continue; // Skip invalid characters
            }

            $v <<= 5;
            $v += $pos;
            $vbits += 5;

            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 0xFF);
                $v &= ((1 << $vbits) - 1);
            }
        }

        return $output;
    }
}