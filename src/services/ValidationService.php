<?php

class ValidationService {
    public static function validateRegistration(array $data): array {
        $errors = [];
        if (empty($data['email']) || empty($data['password'])) {
            $errors[] = 'Veuillez remplir tous les champs.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Le format de l\'adresse e-mail est invalide.';
        }

        if (strlen($data['password']) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        return $errors;
    }
}