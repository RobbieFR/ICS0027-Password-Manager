<?php

class SeedService {
    public static function ensureDemoAccount(PDO $pdo): void {
        $lockFile = sys_get_temp_dir() . '/taltech_vault_seeded.lock';
        if (file_exists($lockFile)) {
            return;
        }

        $demoEmail = 'demo@taltech.ee';
        $demoPassword = 'TalTech2026!Secure';

        $userModel = new User($pdo);
        $existingUser = $userModel->findByEmail($demoEmail);

        if (!$existingUser) {
            if ($userModel->createUser($demoEmail, $demoPassword)) {
                $user = $userModel->findByEmail($demoEmail);
                if ($user) {
                    $key = EncryptionService::deriveKey($demoPassword, $user['kdf_salt']);
                    $vaultService = new VaultService($pdo, new EncryptionService());

                    $sampleEntries = [
                        [
                            'url'      => 'https://ois2.ut.ee',
                            'username' => 'robbie.berlioz@taltech.ee',
                            'password' => 'Ois2#Portal!9876'
                        ],
                        [
                            'url'      => 'https://moodle.taltech.ee',
                            'username' => '266036IV',
                            'password' => 'Moodle$SecCourse2026'
                        ],
                        [
                            'url'      => 'https://github.com',
                            'username' => 'RobbieFR',
                            'password' => 'GitHub_SSH_Key#42!'
                        ]
                    ];

                    foreach ($sampleEntries as $entry) {
                        $vaultService->addEntry(
                            (int)$user['user_id'],
                            $entry['url'],
                            $entry['username'],
                            $entry['password'],
                            $key
                        );
                    }

                    $key = str_repeat("\0", strlen($key));
                    unset($key);
                }
            }
        }

        @touch($lockFile);
    }
}