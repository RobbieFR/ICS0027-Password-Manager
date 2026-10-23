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

$userId = (int)$_SESSION['user_id'];
$userModel = new User($pdo);
$encryptionService = new EncryptionService();
$vaultService = new VaultService($pdo, $encryptionService);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';
    $user = $userModel->findById($userId);

    if (!$user) {
        $key = str_repeat("\0", strlen($key));
        unset($key);
        session_write_close();
        header('Location: /logout');
        exit();
    }

    if ($action === 'update_password') {
        $data = [
            'current_password'     => $_POST['current_password'] ?? '',
            'new_password'         => $_POST['new_password'] ?? '',
            'new_password_confirm' => $_POST['new_password_confirm'] ?? ''
        ];

        $errors = ValidationService::validatePasswordChange($data);

        if (!empty($errors)) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Validation Error',
                'message' => implode('<br>', $errors)
            ];
        } elseif (!password_verify($data['current_password'], $user['master_password_hash'])) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Authentication Error',
                'message' => 'Current master password is incorrect.'
            ];
        } else {
            $newSalt = bin2hex(random_bytes(16));
            $newKey = EncryptionService::deriveKey($data['new_password'], $newSalt);

            try {
                $pdo->beginTransaction();
                $vaultService->reencryptUserEntries($userId, $key, $newKey);
                $userModel->updatePasswordAndSalt($userId, $data['new_password'], $newSalt);
                $pdo->commit();

                $_SESSION['encryption_key'] = base64_encode($newKey);
                $_SESSION['flash_message'] = [
                    'type'    => 'success',
                    'title'   => 'Settings Updated',
                    'message' => 'Master password updated and vault re-encrypted.'
                ];
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_message'] = [
                    'type'    => 'error',
                    'title'   => 'Error',
                    'message' => 'Failed to update master password.'
                ];
            }

            $newKey = str_repeat("\0", strlen($newKey));
            unset($newKey);
        }

        foreach (['current_password', 'new_password', 'new_password_confirm'] as $field) {
            $data[$field] = str_repeat("\0", strlen($data[$field]));
            if (isset($_POST[$field])) {
                $_POST[$field] = str_repeat("\0", strlen($_POST[$field]));
            }
        }
        $key = str_repeat("\0", strlen($key));
        unset($data, $key);

        session_write_close();
        header('Location: /settings');
        exit();
    } elseif ($action === 'delete_account') {
        $confirmPassword = $_POST['delete_password'] ?? '';

        if ($confirmPassword === '' || !password_verify($confirmPassword, $user['master_password_hash'])) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'title'   => 'Authentication Error',
                'message' => 'Incorrect master password. Account deletion aborted.'
            ];
            $confirmPassword = str_repeat("\0", strlen($confirmPassword));
            $key = str_repeat("\0", strlen($key));
            unset($confirmPassword, $_POST['delete_password'], $key);

            session_write_close();
            header('Location: /settings');
            exit();
        }

        $confirmPassword = str_repeat("\0", strlen($confirmPassword));
        $key = str_repeat("\0", strlen($key));
        unset($confirmPassword, $_POST['delete_password'], $key);

        $userModel->deleteUser($userId);

        if (isset($_SESSION['encryption_key'])) {
            $_SESSION['encryption_key'] = str_repeat("\0", strlen($_SESSION['encryption_key']));
        }
        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['encryption_key']);
        session_regenerate_id(true);

        $_SESSION['flash_message'] = [
            'type'    => 'success',
            'title'   => 'Account Deleted',
            'message' => 'Your account and all vault entries have been permanently deleted.'
        ];

        session_write_close();
        header('Location: /login');
        exit();
    }
}

$entriesCount = count($vaultService->getUserEntries($userId, $key));
$key = str_repeat("\0", strlen($key));
unset($key);

require __DIR__ . '/../views/settings.phtml';