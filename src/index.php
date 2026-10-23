<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/':
        header('Location: /login');
        exit();
        
    case '/login':
        require __DIR__ . '/controllers/login-logic.php';
        require __DIR__ . '/views/login.phtml';
        break;
        
    case '/register':
        require __DIR__ . '/controllers/register-logic.php';
        require __DIR__ . '/views/register.phtml';
        break;
        
    case '/vault':
        require __DIR__ . '/controllers/vault-logic.php';
        break;
        
    case '/logout':
        require __DIR__ . '/controllers/logout-logic.php';
        break;
    
    case '/settings':
        require __DIR__ . '/controllers/settings-logic.php';
        break;
        
    default:
        http_response_code(404);
        echo "404 - Not found";
        break;
}