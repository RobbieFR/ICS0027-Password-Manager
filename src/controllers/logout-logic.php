<?php

require_once __DIR__ . '/../bootstrap.php';

$_SESSION['flash_message'] = [
    'type' => 'success',
    'title' => 'Déconnexion',
    'message' => 'Vous avez été déconnecté avec succès.'
];

unset($_SESSION['user_id']);
unset($_SESSION['email']);

session_regenerate_id(true);

header('Location: /login');
exit();