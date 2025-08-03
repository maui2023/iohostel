-- DATABASE: hostel_test

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin', 'guard', 'parent') NOT NULL,
    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ic_no VARCHAR(20) UNIQUE NOT NULL,
    parent_id INT NOT NULL,
    qr_code VARCHAR(255) NOT NULL, -- path or code value
    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE inout_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    action ENUM('in', 'out') NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    guard_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (guard_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- Optional: token-based parent login (e.g. via WhatsApp link)
CREATE TABLE parent_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Add index for faster scanning
CREATE INDEX idx_student_qr ON students(qr_code);
