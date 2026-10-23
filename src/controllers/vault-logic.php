<?php
require_once __DIR__ . '/../bootstrap.php';

require_page_login();

$key = isset($_SESSION['encryption_key']) ? base64_decode($_SESSION['encryption_key'], true) : false;

if ($key === false || strlen($key) !== 32) {
    unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['encryption_key']);
    session_regenerate_id(true);
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'title'   => 'Session Expired',
        'message' => 'Encryption key not found. Please log in again.'
    ];
    session_write_close();
    header('Location: /login');
    exit();
}

$encryptionService = new EncryptionService();
$vaultService = new VaultService($pdo, $encryptionService);
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';

    if ($action === 'reveal') {
        header('Content-Type: application/json; charset=UTF-8');
        $entryId = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);

        if ($entryId === false || $entryId === null || $entryId <= 0) {
            $key = str_repeat("\0", strlen($key));
            unset($key);
            session_write_close();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid entry ID.']);
            exit();
        }

        $decryptedPassword = $vaultService->getDecryptedPassword($entryId, $userId, $key);
        $key = str_repeat("\0", strlen($key));
        unset($key);
        session_write_close();

        if ($decryptedPassword === null) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Credential not found.']);
            exit();
        }

        $response = json_encode(['status' => 'ok', 'password' => $decryptedPassword]);
        $decryptedPassword = str_repeat("\0", strlen($decryptedPassword));
        unset($decryptedPassword);

        echo $response;
        exit();
    } elseif ($action === 'add') {
        $data = [
            'url'      => trim($_POST['url'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];

        $errors = ValidationService::validateVaultEntry($data, false);

        if (!empty($errors)) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Validation Error',
                'message' => implode('<br>', $errors)
            ];
        } else {
            if ($vaultService->addEntry($userId, $data['url'], $data['username'], $data['password'], $key)) {
                $_SESSION['flash_message'] = [
                    'type'    => 'success',
                    'title'   => 'Success',
                    'message' => 'Credential encrypted and added to the vault.'
                ];
            } else {
                $_SESSION['flash_message'] = [
                    'type'    => 'error',
                    'title'   => 'Error',
                    'message' => 'Unable to save the credential.'
                ];
            }
        }

        $data['password'] = str_repeat("\0", strlen($data['password']));
        if (isset($_POST['password'])) {
            $_POST['password'] = str_repeat("\0", strlen($_POST['password']));
        }
        $key = str_repeat("\0", strlen($key));
        unset($data, $_POST['password'], $key);

        session_write_close();
        header('Location: /vault');
        exit();
    } elseif ($action === 'edit') {
        $entryId = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);
        $data = [
            'url'      => trim($_POST['url'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];

        $errors = ValidationService::validateVaultEntry($data, true);

        if ($entryId === false || $entryId === null || $entryId <= 0) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Error',
                'message' => 'Invalid credential selected for update.'
            ];
        } elseif (!empty($errors)) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Validation Error',
                'message' => implode('<br>', $errors)
            ];
        } else {
            if ($vaultService->updateEntry($entryId, $userId, $data['url'], $data['username'], $data['password'], $key)) {
                $_SESSION['flash_message'] = [
                    'type'    => 'success',
                    'title'   => 'Updated',
                    'message' => 'Credential updated and re-encrypted.'
                ];
            } else {
                $_SESSION['flash_message'] = [
                    'type'    => 'error',
                    'title'   => 'Error',
                    'message' => 'Unable to update the credential.'
                ];
            }
        }

        $data['password'] = str_repeat("\0", strlen($data['password']));
        if (isset($_POST['password'])) {
            $_POST['password'] = str_repeat("\0", strlen($_POST['password']));
        }
        $key = str_repeat("\0", strlen($key));
        unset($data, $_POST['password'], $key);

        session_write_close();
        header('Location: /vault');
        exit();
    } elseif ($action === 'delete') {
        $entryId = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);

        if ($entryId !== false && $entryId !== null && $entryId > 0 && $vaultService->deleteEntry($entryId, $userId)) {
            $_SESSION['flash_message'] = [
                'type'    => 'success',
                'title'   => 'Deleted',
                'message' => 'Entry removed from the vault.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Error',
                'message' => 'Entry not found or unauthorized deletion.'
            ];
        }

        $key = str_repeat("\0", strlen($key));
        unset($key);

        session_write_close();
        header('Location: /vault');
        exit();
    }
}

$entries = $vaultService->getUserEntries($userId, $key, false);

$key = str_repeat("\0", strlen($key));
unset($key);

require __DIR__ . '/../views/vault.phtml';