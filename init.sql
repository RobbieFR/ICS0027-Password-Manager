CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    master_password_hash VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(255) DEFAULT NULL,
    verification_token VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS vault_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    encrypted_url TEXT NOT NULL,
    encrypted_username TEXT NOT NULL,
    encrypted_password TEXT NOT NULL,
    iv VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS submission_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    context_type VARCHAR(50) NOT NULL,
    context_id VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) NOT NULL,
    submitted_payload TEXT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL
);