<?php

require_once __DIR__ . '/../bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $rateLimit = new RateLimitService($pdo);
    if (!$rateLimit->checkLimit(0, 'login', 5, 15)) {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Trop de tentatives échouées. Veuillez patienter 15 minutes.'];
        header('Location: /login');
        exit();
    }

    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $rateLimit->logAttempt(0, 'login', '', false, $identifier);
        $_SESSION['email'] = $user['email'];
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Erreur', 'message' => 'Veuillez remplir tous les champs.'];
        header('Location: /login');
        exit();
    }
    
    $userModel = new User($pdo);
    $user = $userModel->findByEmail($identifier);

    if ($user && password_verify($password, $user['master_password_hash'])) {

        $rateLimit->logAttempt($user['id'], 'login', '', true, $identifier);

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['flash_message'] = ['type' => 'success', 'title' => 'Connexion réussie', 'message' => 'Bienvenue, ' . htmlspecialchars($user['email']) . ' !'];
        $redirectTo = $_POST['redirect_to'] ?? '/vault';
        $safeRedirectUrl = '/';
        if (strpos($redirectTo, '//') === 0) {
        } elseif (strpos($redirectTo, '/') === 0) {
            $safeRedirectUrl = $redirectTo;
        }
        header('Location: ' . $safeRedirectUrl);
        exit();
    } else {
        $userIdForLog = $user ? $user['id'] : 0;
        $rateLimit->logAttempt($userIdForLog, 'login', '', false, $identifier);

        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Échec de la connexion', 'message' => 'Identifiant ou mot de passe incorrect.'];
        header('Location: /login');
        exit();
    }
}