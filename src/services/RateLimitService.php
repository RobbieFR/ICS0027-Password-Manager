<?php

class RateLimitService {
    private $pdo;

    private const MAX_FAILURES = 6;
    private const WINDOW_MINUTES = 1;
    // 6 tentatives max par tranche d'1 minute
    // 1 tentatives toutes les 10s

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function checkLimit(int $userId, string $type, int $maxFailures = self::MAX_FAILURES, int $windowMinutes = self::WINDOW_MINUTES): bool {
        $windowTime = date('Y-m-d H:i:s', strtotime('-' . $windowMinutes . ' minutes'));

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userId = ($userId === 0) ? null : $userId;
        
        $sql = "SELECT COUNT(*) FROM submission_logs 
                WHERE (user_id = :user_id OR ip_address = :ip) 
                AND context_type = :type 
                AND status = 'failure'
                AND attempted_at > :window_time";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':ip' => $clientIp,
            ':type' => $type,
            ':window_time' => $windowTime
        ]);

        return (int) $stmt->fetchColumn() < $maxFailures;
    }

    public function checkFrequency(int $userId, string $type, int $windowMinutes, int $maxAttempts = 1): bool {
        $windowTime = date('Y-m-d H:i:s', strtotime('-' . $windowMinutes . ' minutes'));
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $sql = "SELECT COUNT(*) FROM submission_logs 
                WHERE (user_id = :user_id OR ip_address = :ip) 
                AND context_type = :type 
                AND attempted_at > :window_time";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':ip' => $clientIp,
            ':type' => $type,
            ':window_time' => $windowTime
        ]);

        return (int) $stmt->fetchColumn() < $maxAttempts;
    }

    public function logAttempt(int $userId, string $type, string $contextId, bool $isSuccess, ?string $payload = null): void {
        $status = $isSuccess ? 'success' : 'failure';
        $userId = ($userId === 0) ? null : $userId;
        
        $payloadToStore = substr($payload ?? '', 0, 1000); 

        $sql = "INSERT INTO submission_logs (user_id, context_type, context_id, status, submitted_payload, ip_address, attempted_at) 
                VALUES (:user_id, :type, :context_id, :status, :payload, :ip, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':context_id' => $contextId,
            ':status' => $status,
            ':payload' => $payloadToStore,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    }

    public function getTimeRemaining(): int {
        return self::WINDOW_MINUTES;
    }
}