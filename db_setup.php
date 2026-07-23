<?php
$host = '127.0.0.1';
$dbname = 'enneagram_app';
$credentials = [
    ['username' => 'root', 'password' => 'pass123'],
    ['username' => 'root', 'password' => 'password'],
    ['username' => 'root', 'password' => ''],
];

$pdo = null;
$errors = [];
foreach ($credentials as $cred) {
    try {
        $username = $cred['username'];
        $password = $cred['password'];
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // Connected successfully!
    } catch (PDOException $e) {
        $errors[] = "User: $username, Pass: '$password' -> " . $e->getMessage();
    }
}

if (!$pdo) {
    echo "DB Connection Errors:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

try {
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' ensured.\n";
    
    // Connect to the specific database
    $pdo->exec("USE `$dbname`");

    // Drop tables in reverse order of foreign keys
    $pdo->exec("DROP TABLE IF EXISTS user_feedbacks");
    $pdo->exec("DROP TABLE IF EXISTS enneagram_reports");
    $pdo->exec("DROP TABLE IF EXISTS exam_answers");
    $pdo->exec("DROP TABLE IF EXISTS exam_sessions");
    $pdo->exec("DROP TABLE IF EXISTS user_profiles");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("DROP TABLE IF EXISTS questions");
    echo "Existing tables dropped for fresh configuration.\n";
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        email_id VARCHAR(255) UNIQUE,
        password_hash VARCHAR(255),
        force_password_change TINYINT(1) DEFAULT 0,
        created_at DATETIME,
        updated_at DATETIME
    )");
    echo "Table 'users' ensured.\n";

    // Create user_profiles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT,
        name VARCHAR(255),
        age_group ENUM('18-25','26-35','36-45','46-55','56-65'),
        gender VARCHAR(50),
        phone_number VARCHAR(50) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        assessment_override VARCHAR(20) DEFAULT 'default',
        created_at DATETIME,
        updated_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Table 'user_profiles' ensured.\n";

    // Create exam_sessions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_sessions (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT,
        age_group ENUM('18-25','26-35','36-45','46-55','56-65') DEFAULT NULL,
        gdpr_consent_given TINYINT(1),
        consent_timestamp DATETIME,
        status ENUM('in_progress','completed'),
        created_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Table 'exam_sessions' ensured.\n";

    // Create questions table with age_group
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        prompt_text TEXT,
        target_type TINYINT,
        age_group ENUM('18-25','26-35','36-45','46-55','56-65') NOT NULL,
        created_at DATETIME
    )");
    echo "Table 'questions' ensured.\n";

    // Create exam_answers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_answers (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        session_id BIGINT,
        question_id INT,
        answer_text TEXT,
        input_mode ENUM('text','voice'),
        created_at DATETIME,
        FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");
    echo "Table 'exam_answers' ensured.\n";

    // Create enneagram_reports table
    $pdo->exec("CREATE TABLE IF NOT EXISTS enneagram_reports (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        session_id BIGINT,
        user_id BIGINT,
        enneagram_type TINYINT,
        wing_1 TINYINT,
        wing_2 TINYINT,
        raw_scores JSON,
        is_dominant_tied TINYINT(1) DEFAULT 0,
        created_at DATETIME,
        FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Table 'enneagram_reports' ensured.\n";

    // Create user_feedbacks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_feedbacks (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        user_id BIGINT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        attachment_name VARCHAR(255) DEFAULT NULL,
        status ENUM('Submitted', 'In Review', 'Resolved') DEFAULT 'Submitted',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Table 'user_feedbacks' ensured.\n";

    echo "Database setup completed successfully.\n";
} catch(PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
    exit(1);
}
