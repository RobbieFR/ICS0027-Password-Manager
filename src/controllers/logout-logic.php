<?php
require_once __DIR__ . '/../bootstrap.php';

$_SESSION['flash_message'] = [
    'type' => 'success',
    'title' => 'Logged Out',
    'message' => 'You have been successfully logged out.'
];

if (isset($_SESSION['encryption_key'])) {
    $_SESSION['encryption_key'] = str_repeat("\0", strlen($_SESSION['encryption_key']));
}

unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['encryption_key']);

session_regenerate_id(true);

header('Location: /login');
exit();