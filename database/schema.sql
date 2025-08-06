-- DATABASE: hostel_test

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,

    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    student_no VARCHAR(20) UNIQUE NOT NULL,
    parent_id INT NOT NULL,
    class_id INT,
    picture VARCHAR(255), -- path to student picture
    gender ENUM('Male', 'Female'),
    religion VARCHAR(50),
    race VARCHAR(50),
    qr_code VARCHAR(255), -- path to QR code file
    qr_token VARCHAR(255), -- secure token for QR code
    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE inout_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    action ENUM('in', 'out') NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    guard_id INT NULL,  -- FIXED
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

-- Notification logs table
CREATE TABLE notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    parent_id INT NOT NULL,
    guard_id INT NOT NULL,
    action ENUM('in', 'out') NOT NULL,
    whatsapp_link TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (guard_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Password reset requests table
CREATE TABLE password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    new_password VARCHAR(255) NULL, -- Generated password after acceptance
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL, -- Admin/Superadmin who processed the request
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- Add indexes for faster scanning and queries
CREATE INDEX idx_student_qr ON students(qr_code);
CREATE INDEX idx_student_token ON students(qr_token);
CREATE INDEX idx_student_student_no ON students(student_no);
CREATE INDEX idx_student_class ON students(class_id);
CREATE INDEX idx_parent_tokens_token ON parent_tokens(token);
CREATE INDEX idx_inout_logs_student ON inout_logs(student_id);
CREATE INDEX idx_inout_logs_timestamp ON inout_logs(timestamp);
CREATE INDEX idx_password_reset_status ON password_reset_requests(status);
CREATE INDEX idx_password_reset_user ON password_reset_requests(user_id);


-- Profile table for user pictures and date of birth
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    picture VARCHAR(255), -- path to profile picture
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role ENUM('superadmin', 'admin', 'guard', 'parent') NOT NULL,
    PRIMARY KEY (user_id, role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
