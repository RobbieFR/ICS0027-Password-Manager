<?php

class VaultService {
    private PDO $pdo;
    private EncryptionService $encryptionService;

    public function __construct(PDO $pdo, ?EncryptionService $encryptionService = null) {
        $this->pdo = $pdo;
        $this->encryptionService = $encryptionService ?? new EncryptionService();
    }

    public function addEntry(int $userId, string $url, string $username, string $password, string $key): bool {
        $encryptedData = $this->encryptionService::encryptEntry($url, $username, $password, $key);

        $sql = "INSERT INTO vault_entries (user_id, encrypted_url, encrypted_username, encrypted_password, iv) 
                VALUES (:user_id, :encrypted_url, :encrypted_username, :encrypted_password, :iv)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':user_id'            => $userId,
            ':encrypted_url'      => $encryptedData['encrypted_url'],
            ':encrypted_username' => $encryptedData['encrypted_username'],
            ':encrypted_password' => $encryptedData['encrypted_password'],
            ':iv'                 => $encryptedData['iv']
        ]);
    }

    public function getUserEntries(int $userId, string $key, bool $includePasswords = false): array {
        $sql = "SELECT entry_id, user_id, encrypted_url, encrypted_username, encrypted_password, iv 
                FROM vault_entries 
                WHERE user_id = :user_id 
                ORDER BY entry_id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $decryptedEntries = [];
        foreach ($rows as $row) {
            $decrypted = $this->encryptionService::decryptEntry($row, $key);
            if ($decrypted['url'] !== false && $decrypted['username'] !== false && $decrypted['password'] !== false) {
                if (!$includePasswords) {
                    $decrypted['password'] = str_repeat("\0", strlen($decrypted['password']));
                    unset($decrypted['password']);
                }
                $decryptedEntries[] = $decrypted;
            }
        }

        return $decryptedEntries;
    }

    public function getDecryptedPassword(int $entryId, int $userId, string $key): ?string {
        $sql = "SELECT entry_id, encrypted_url, encrypted_username, encrypted_password, iv 
                FROM vault_entries 
                WHERE entry_id = :entry_id AND user_id = :user_id 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':entry_id' => $entryId,
            ':user_id'  => $userId
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $decrypted = $this->encryptionService::decryptEntry($row, $key);
        if ($decrypted['password'] === false) {
            return null;
        }

        return $decrypted['password'];
    }

    public function updateEntry(int $entryId, int $userId, string $url, string $username, string $password, string $key): bool {
        $passwordToEncrypt = $password;

        if ($passwordToEncrypt === '') {
            $existingPassword = $this->getDecryptedPassword($entryId, $userId, $key);
            if ($existingPassword === null) {
                return false;
            }
            $passwordToEncrypt = $existingPassword;
        }

        $encryptedData = $this->encryptionService::encryptEntry($url, $username, $passwordToEncrypt, $key);
        $passwordToEncrypt = str_repeat("\0", strlen($passwordToEncrypt));
        unset($passwordToEncrypt);

        $sql = "UPDATE vault_entries 
                SET encrypted_url = :encrypted_url, 
                    encrypted_username = :encrypted_username, 
                    encrypted_password = :encrypted_password, 
                    iv = :iv 
                WHERE entry_id = :entry_id AND user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':encrypted_url'      => $encryptedData['encrypted_url'],
            ':encrypted_username' => $encryptedData['encrypted_username'],
            ':encrypted_password' => $encryptedData['encrypted_password'],
            ':iv'                 => $encryptedData['iv'],
            ':entry_id'           => $entryId,
            ':user_id'            => $userId
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function deleteEntry(int $entryId, int $userId): bool {
        $sql = "DELETE FROM vault_entries WHERE entry_id = :entry_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':entry_id' => $entryId,
            ':user_id'  => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function reencryptUserEntries(int $userId, string $oldKey, string $newKey): bool {
        $entries = $this->getUserEntries($userId, $oldKey, true);
        $sql = "UPDATE vault_entries 
                SET encrypted_url = :encrypted_url, 
                    encrypted_username = :encrypted_username, 
                    encrypted_password = :encrypted_password, 
                    iv = :iv 
                WHERE entry_id = :entry_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);

        foreach ($entries as $entry) {
            $encryptedData = $this->encryptionService::encryptEntry(
                $entry['url'],
                $entry['username'],
                $entry['password'],
                $newKey
            );
            $stmt->execute([
                ':encrypted_url'      => $encryptedData['encrypted_url'],
                ':encrypted_username' => $encryptedData['encrypted_username'],
                ':encrypted_password' => $encryptedData['encrypted_password'],
                ':iv'                 => $encryptedData['iv'],
                ':entry_id'           => (int)$entry['entry_id'],
                ':user_id'            => $userId
            ]);
        }

        return true;
    }
}