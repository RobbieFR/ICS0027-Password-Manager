<?php

class EncryptionService {
    
    public static function deriveKey(string $masterPassword, string $hexSalt): string {
        $salt = hex2bin($hexSalt);
        return hash_pbkdf2('sha256', $masterPassword, $salt, 100000, 32, true);
    }

    public static function encryptEntry(string $url, string $username, string $password, string $key): array {
        $iv = random_bytes(12);
        
        $tagUrl = '';
        $encUrl = openssl_encrypt($url, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tagUrl);
        
        $tagUsername = '';
        $encUsername = openssl_encrypt($username, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tagUsername);
        
        $tagPassword = '';
        $encPassword = openssl_encrypt($password, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tagPassword);

        return [
            'encrypted_url' => base64_encode($encUrl . $tagUrl),
            'encrypted_username' => base64_encode($encUsername . $tagUsername),
            'encrypted_password' => base64_encode($encPassword . $tagPassword),
            'iv' => base64_encode($iv)
        ];
    }

    public static function decryptEntry(array $row, string $key): array {
        $iv = base64_decode($row['iv']);
        
        return [
            'entry_id' => $row['entry_id'],
            'url' => self::decryptField($row['encrypted_url'], $key, $iv),
            'username' => self::decryptField($row['encrypted_username'], $key, $iv),
            'password' => self::decryptField($row['encrypted_password'], $key, $iv)
        ];
    }

    private static function decryptField(string $encodedData, string $key, string $iv): string|false {
        $decoded = base64_decode($encodedData);
        $tag = substr($decoded, -16);
        $ciphertext = substr($decoded, 0, -16);
        
        return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}