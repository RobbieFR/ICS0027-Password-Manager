<?php
require_once __DIR__ . '/../bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /vault");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $rateLimit = new RateLimitService($pdo);

    if (!$rateLimit->checkLimit(0, 'login', 5, 15)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Too many failed attempts. Please wait 15 minutes.'
        ];
        header('Location: /login');
        exit();
    }

    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $rateLimit->logAttempt(0, 'login', '', false, $identifier);
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Please fill in all fields.'
        ];
        header('Location: /login');
        exit();
    }
    
    $userModel = new User($pdo);
    $user = $userModel->findByEmail($identifier);

    if ($user && password_verify($password, $user['master_password_hash'])) {
        $rateLimit->logAttempt((int)$user['user_id'], 'login', '', true, $identifier);
        session_regenerate_id(true);

        $derivedKey = EncryptionService::deriveKey($password, $user['kdf_salt']);
        $_SESSION['encryption_key'] = base64_encode($derivedKey);

        $password = str_repeat("\0", strlen($password));
        $derivedKey = str_repeat("\0", strlen($derivedKey));
        unset($password, $derivedKey, $_POST['password']);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'title' => 'Login Successful',
            'message' => 'Welcome, ' . htmlspecialchars($user['email']) . '!'
        ];

        $redirectTo = $_POST['redirect_to'] ?? '/vault';
        $safeRedirectUrl = '/vault';
        if (strpos($redirectTo, '/') === 0 && strpos($redirectTo, '//') !== 0) {
            $safeRedirectUrl = $redirectTo;
        }
        header('Location: ' . $safeRedirectUrl);
        exit();
    } else {
        $password = str_repeat("\0", strlen($password));
        unset($password, $_POST['password']);

        $userIdForLog = $user ? (int)$user['user_id'] : 0;
        $rateLimit->logAttempt($userIdForLog, 'login', '', false, $identifier);
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Login Failed',
            'message' => 'Invalid email or password.'
        ];
        header('Location: /login');
        exit();
    }
}