<?php

class ValidationService {
    public static function validateRegistration(array $data): array {
        $errors = [];

        if (empty($data['email']) || empty($data['password'])) {
            $errors[] = 'Please fill in all fields.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address format.';
        }

        if (strlen($data['password']) < 12) {
            $errors[] = 'Password must be at least 12 characters long.';
        }

        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }

public static function validateVaultEntry(array $data, bool $isEdit = false): array {
        $errors = [];

        $url = trim($data['url'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($url === '' || $username === '' || (!$isEdit && $password === '')) {
            $errors[] = 'Please fill in all required fields.';
            return $errors;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
            $errors[] = 'Invalid URL format (must start with http:// or https://).';
        }

        if (strlen($url) > 2048) {
            $errors[] = 'URL cannot exceed 2048 characters.';
        }

        if (strlen($username) > 255) {
            $errors[] = 'Username cannot exceed 255 characters.';
        }

        if (strlen($password) > 1024) {
            $errors[] = 'Password cannot exceed 1024 characters.';
        }

        return $errors;
    }

    public static function validatePasswordChange(array $data): array {
        $errors = [];

        if (empty($data['current_password']) || empty($data['new_password']) || empty($data['new_password_confirm'])) {
            $errors[] = 'Please fill in all password fields.';
            return $errors;
        }

        if (strlen($data['new_password']) < 12) {
            $errors[] = 'New master password must be at least 12 characters long.';
        }

        if ($data['new_password'] !== $data['new_password_confirm']) {
            $errors[] = 'New passwords do not match.';
        }

        if ($data['current_password'] === $data['new_password']) {
            $errors[] = 'New master password must be different from the current one.';
        }

        return $errors;
    }
}