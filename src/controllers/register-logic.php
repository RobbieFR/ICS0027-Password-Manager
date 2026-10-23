<?php
require_once __DIR__ . '/../bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /vault');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $rateLimit = new RateLimitService($pdo);

    if (!$rateLimit->checkFrequency(0, 'register', 60, 2)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Too many registration attempts. Please wait.'
        ];
        header('Location: /register');
        exit();
    }
    
    $userModel = new User($pdo);
    $data = [
        'email'            => trim($_POST['email'] ?? ''),
        'password'         => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];

    $errors = ValidationService::validateRegistration($data);

    if (!empty($errors)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Validation Error',
            'message' => implode('<br>', $errors)
        ];
    } elseif ($userModel->findByEmail($data['email'])) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'This email is already in use.'
        ];
    } else {
        if ($userModel->createUser($data['email'], $data['password'])) {
            $rateLimit->logAttempt(0, 'register', '', true, $data['email']);
            
            $_SESSION['flash_message'] = [
                'type' => 'success', 
                'title' => 'Account Created!', 
                'message' => 'Your account has been successfully created. You can now log in.'
            ];
            
            header('Location: /login');
            exit();
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'title' => 'Server Error',
                'message' => 'An error occurred while creating your account.'
            ];
        }
    }

    $rateLimit->logAttempt(0, 'register', '', false, $data['email']);
    
    header('Location: /register');
    exit();
}