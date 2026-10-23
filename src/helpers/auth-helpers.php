<?php

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): void
{
    if (empty($_SESSION['csrf_token'])) {
        http_response_code(403);
        die('Error: CSRF token not found in session.');
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;

    if ($token === null) {
        http_response_code(403);
        die('Error: CSRF token missing from request.');
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Error: Invalid CSRF token.');
    }
}