<?php

class User {
    private $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function findById(int $id) {
        $sql = "SELECT * FROM users WHERE user_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function findByEmail(string $email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function createUser(string $email, string $password): bool {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $kdfSalt = bin2hex(random_bytes(16));
        $sql = "INSERT INTO users (email, master_password_hash, kdf_salt) VALUES (:email, :master_password_hash, :kdf_salt)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'email' => $email,
            'master_password_hash' => $passwordHash,
            'kdf_salt' => $kdfSalt
        ]);
    }
    
    public function updatePasswordAndSalt(int $userId, string $newPassword, string $newKdfSalt): bool {
        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $sql = "UPDATE users SET master_password_hash = :password_hash, kdf_salt = :kdf_salt WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'password_hash' => $passwordHash,
            'kdf_salt'      => $newKdfSalt,
            'id'            => $userId
        ]);
    }

    public function deleteUser(int $userId): bool {
        $sql = "DELETE FROM users WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $userId]);
    }
    
    public function getUserEmail(int $userId) {
        $sql = "SELECT email FROM users WHERE user_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['email'] ?? null;
    }


}