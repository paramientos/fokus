<?php

namespace App\Services\PasswordManager;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EncryptionService
{
    /**
     * Encrypt a password with an optional personal key.
     */
    public function encrypt(string $password, ?string $personalKey = null): string
    {
        if ($personalKey) {
            // Use personal key for additional encryption layer
            $password = $this->encryptWithPersonalKey($password, $personalKey);
        }

        return Crypt::encryptString($password);
    }

    /**
     * Decrypt a password with an optional personal key.
     */
    public function decrypt(string $encryptedPassword, ?string $personalKey = null): string
    {
        $password = Crypt::decryptString($encryptedPassword);

        if ($personalKey) {
            // Decrypt the additional layer with personal key
            $password = $this->decryptWithPersonalKey($password, $personalKey);
        }

        return $password;
    }

    /**
     * Generate a secure random password.
     */
    public function generatePassword(
        int $length = 16,
        bool $includeUppercase = true,
        bool $includeNumbers = true,
        bool $includeSymbols = true
    ): string {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        $characters = $lowercase;
        if ($includeUppercase) {
            $characters .= $uppercase;
        }
        if ($includeNumbers) {
            $characters .= $numbers;
        }
        if ($includeSymbols) {
            $characters .= $symbols;
        }

        $password = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        // Ensure password contains at least one character from each selected type
        $hasRequiredChars = true;

        if ($includeUppercase && !preg_match('/[A-Z]/', $password)) {
            $hasRequiredChars = false;
        }
        if ($includeNumbers && !preg_match('/[0-9]/', $password)) {
            $hasRequiredChars = false;
        }
        if ($includeSymbols && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $hasRequiredChars = false;
        }

        // If not, generate a new password
        if (!$hasRequiredChars) {
            return $this->generatePassword($length, $includeUppercase, $includeNumbers, $includeSymbols);
        }

        return $password;
    }

    /**
     * Calculate password strength on a scale of 0-5.
     */
    public function calculatePasswordStrength(string $password): int
    {
        $strength = 0;

        // Length check
        if (strlen($password) >= 8) {
            $strength++;
        }
        if (strlen($password) >= 12) {
            $strength++;
        }

        // Complexity checks
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength++;
        }

        return min(5, $strength);
    }

    /**
     * Additional encryption layer with personal key.
     */
    protected function encryptWithPersonalKey(string $data, string $personalKey): string
    {
        $key = hash('sha256', $personalKey);
        $iv = substr(hash('sha256', Str::random(16)), 0, 16);

        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            hex2bin($key),
            0,
            $iv
        );

        return base64_encode($encrypted.'::'.$iv);
    }

    /**
     * Decrypt data with personal key.
     */
    protected function decryptWithPersonalKey(string $data, string $personalKey): string
    {
        $key = hash('sha256', $personalKey);

        [$encrypted_data, $iv] = explode('::', base64_decode($data), 2);

        return openssl_decrypt(
            $encrypted_data,
            'AES-256-CBC',
            hex2bin($key),
            0,
            $iv
        );
    }
}
