<?php
require_once __DIR__ . '/../bootstrap.php';
require_page_login(); // Protège la page : redirige vers /login si non connecté

require __DIR__ . '/../views/vault.phtml';