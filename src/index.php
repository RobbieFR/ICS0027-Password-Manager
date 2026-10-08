<?php
// On récupère l'URL demandée (ex: /login)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/':
        // Redirige la racine vers la page de connexion
        header('Location: /login');
        exit();
        
    case '/login':
        // Charge la logique backend puis l'interface
        require __DIR__ . '/controllers/login-logic.php';
        require __DIR__ . '/views/login.phtml';
        break;
        
    case '/register':
        require __DIR__ . '/controllers/register-logic.php';
        require __DIR__ . '/views/register.phtml';
        break;
        
    case '/vault':
        // Le contrôleur vault-logic se charge d'inclure la vue vault.phtml
        require __DIR__ . '/controllers/vault-logic.php';
        break;
        
    case '/logout':
        require __DIR__ . '/controllers/logout-logic.php';
        break;
        
    default:
        http_response_code(404);
        echo "404 - Not found";
        break;
}