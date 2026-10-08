<?php

require_once __DIR__ . '/../bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $rateLimit = new RateLimitService($pdo);
    if (!$rateLimit->checkFrequency(0, 'register', 60, 2)) { // 2 comptes max crées/h et par IP
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Trop de tentatives de création de compte. Veuillez patienter.'];
        header('Location: /register');
        exit();
    }
    
    $userModel = new User($pdo);
    $data = [
        'email'    => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];

    $errors = ValidationService::validateRegistration($data);

    if (!empty($errors)) {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur de validation', 'message' => implode('<br>', $errors)];
    } elseif ($userModel->findByEmail($data['email'])) {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Cet e-mail est déjà utilisé.'];
    } else {

        
        if ($userModel->createUser($data['email'], $data['password'])) {

            $rateLimit->logAttempt(0, 'register', '', true, $data['email']);
            
            $_SESSION['flash_message'] = [
                'type' => 'success', 
                'title' => 'Compte créé !', 
                'message' => 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.'
            ];
            
            header('Location: /login');
            exit();
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur serveur', 'message' => 'Une erreur est survenue lors de la création du compte.'];
        }
    }

    $rateLimit->logAttempt(0, 'register', '', false, $data['email']);
    
    header('Location: /register');
    exit();
}