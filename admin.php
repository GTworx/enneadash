<?php
// admin.php - Modern Enneagram Admin Control Portal
require_once __DIR__ . '/config.php';

// 1. Self-Healing Schema Verification
function ensure_admin_schema($pdo) {
    if (!$pdo) return;
    try {
        // Create settings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL
        )");
        
        // Ensure user_profiles table exists
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

        // Fetch existing columns for user_profiles without prepared placeholders
        $existingProfileCols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM user_profiles");
        if ($stmt) {
            while ($colRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingProfileCols[strtolower($colRow['Field'])] = true;
            }
        }

        // Add detail columns to user_profiles if missing
        $columns = [
            'phone_number' => 'VARCHAR(50) DEFAULT NULL',
            'department' => 'VARCHAR(100) DEFAULT NULL',
            'assessment_override' => "VARCHAR(20) DEFAULT 'default'"
        ];
        foreach ($columns as $col => $definition) {
            if (empty($existingProfileCols[strtolower($col)])) {
                $pdo->exec("ALTER TABLE user_profiles ADD COLUMN `$col` $definition");
            }
        }

        // Migrate phone data if legacy 'phone' column exists
        if (!empty($existingProfileCols['phone'])) {
            $pdo->exec("UPDATE user_profiles SET phone_number = phone WHERE (phone_number IS NULL OR phone_number = '') AND phone IS NOT NULL AND phone != ''");
        }

        // Ensure input_type exists in exam_answers
        $existingAnswerCols = [];
        $stmtAns = $pdo->query("SHOW COLUMNS FROM exam_answers");
        if ($stmtAns) {
            while ($ansRow = $stmtAns->fetch(PDO::FETCH_ASSOC)) {
                $existingAnswerCols[strtolower($ansRow['Field'])] = true;
            }
        }
        if (!empty($existingAnswerCols) && empty($existingAnswerCols['input_type'])) {
            if (!empty($existingAnswerCols['input_mode'])) {
                $pdo->exec("ALTER TABLE exam_answers ADD COLUMN input_type ENUM('text','voice') DEFAULT 'text'");
                $pdo->exec("UPDATE exam_answers SET input_type = input_mode WHERE input_type IS NULL OR input_type = 'text'");
            } else {
                $pdo->exec("ALTER TABLE exam_answers ADD COLUMN input_type ENUM('text','voice') DEFAULT 'text'");
            }
        }

        // Ensure user_feedbacks table exists & includes admin_notes column
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_feedbacks (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            attachment_path VARCHAR(255) DEFAULT NULL,
            attachment_name VARCHAR(255) DEFAULT NULL,
            status ENUM('Submitted', 'Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Submitted',
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $existingFbCols = [];
        $stmtFb = $pdo->query("SHOW COLUMNS FROM user_feedbacks");
        if ($stmtFb) {
            while ($fbColRow = $stmtFb->fetch(PDO::FETCH_ASSOC)) {
                $existingFbCols[strtolower($fbColRow['Field'])] = true;
            }
        }
        if (empty($existingFbCols['admin_notes'])) {
            $pdo->exec("ALTER TABLE user_feedbacks ADD COLUMN admin_notes TEXT DEFAULT NULL");
        }
        try {
            $pdo->exec("ALTER TABLE user_feedbacks MODIFY COLUMN status ENUM('Submitted', 'Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Submitted'");
        } catch (Throwable $statusErr) {}
    } catch (Throwable $e) {
        error_log("Schema verification error: " . $e->getMessage());
    }
}

// Disallow error outputs and clean buffers before sending CSV headers to prevent corruption
if (isset($_GET['action']) && ($_GET['action'] === 'download_sample_csv' || $_GET['action'] === 'download_csv')) {
    ini_set('display_errors', '0');
    error_reporting(0);
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $pdo = get_db_connection();
    if ($pdo) {
        ensure_admin_schema($pdo);
    }
    
    if ($_GET['action'] === 'download_sample_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=enneagram_bulk_upload_sample.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Email', 'Age Group', 'Gender', 'Phone', 'Department'], ',', '"', '\\');
        fputcsv($output, ['John Doe', 'john.doe@example.com', '26-35', 'Male', '+1234567890', 'Engineering'], ',', '"', '\\');
        fputcsv($output, ['Jane Smith', 'jane.smith@example.com', '18-25', 'Female', '+9876543210', 'Marketing'], ',', '"', '\\');
        fclose($output);
        exit;
    }
    
    if ($_GET['action'] === 'download_csv') {
        try {
            $stmt = $pdo->query("SELECT p.name, u.email_id as email, p.age_group, p.gender, 
                                        COALESCE(CONCAT('Type ', r.enneagram_type, IF(r.wing_1 IS NOT NULL AND r.wing_1 > 0, CONCAT('w', r.wing_1), '')), 'Incomplete') as final_type,
                                        p.phone_number, p.department
                                 FROM user_profiles p
                                 JOIN users u ON p.user_id = u.id
                                 LEFT JOIN (
                                     SELECT user_id, enneagram_type, wing_1 
                                     FROM enneagram_reports 
                                     WHERE id IN (SELECT MAX(id) FROM enneagram_reports GROUP BY user_id)
                                 ) r ON u.id = r.user_id
                                 ORDER BY p.name ASC");
            if (!$stmt) {
                // Fallback query if no reports exist or join fails
                $stmt = $pdo->query("SELECT p.name, u.email_id as email, p.age_group, p.gender, 
                                            'Incomplete' as final_type, p.phone_number, p.department
                                     FROM user_profiles p
                                     JOIN users u ON p.user_id = u.id
                                     ORDER BY p.name ASC");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=enneagram_participants_export.csv');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Email', 'Age Group', 'Gender', 'Phone', 'Department', 'Final Type'], ',', '"', '\\');
            
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['name'],
                    $row['email'],
                    $row['age_group'],
                    $row['gender'],
                    $row['phone_number'] ?? '',
                    $row['department'] ?? '',
                    $row['final_type']
                ], ',', '"', '\\');
            }
            fclose($output);
            exit;
        } catch (Throwable $e) {
            header('Content-Type: text/plain');
            echo "Error exporting CSV: " . $e->getMessage();
            exit;
        }
    }
}

// Perform initial check if DB is configured
$is_db_connected = false;
$db_error_message = '';
try {
    $pdo = get_db_connection();
    if ($pdo) {
        $is_db_connected = true;
        ensure_admin_schema($pdo);
    }
} catch (Throwable $e) {
    $db_error_message = $e->getMessage();
}

// 2. Admin Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'admin_logout') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        unset($_SESSION['admin_logged_in']);
    }
    header("Location: admin.php");
    exit;
}

// 3. Admin Authentication POST Process
$login_error = null;
if (isset($_POST['admin_email']) && isset($_POST['admin_password'])) {
    $email = trim($_POST['admin_email']);
    $password = $_POST['admin_password'];
    
    if ($email === 'admin@enneadash.com' && $password === 'admin@123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid administrator credentials.";
    }
}

$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Retrieve Logo URL matching User Portal
$logoUrl = '/New folder/enneagram-9.png';
try {
    if ($is_db_connected && $pdo) {
        $stmtLogo = $pdo->query("SELECT image_url FROM images WHERE file_name = 'enneagram-9.png' LIMIT 1");
        if ($stmtLogo) {
            $logoRow = $stmtLogo->fetch();
            if ($logoRow && !empty($logoRow['image_url'])) {
                $logoUrl = $logoRow['image_url'];
            }
        }
    }
} catch (Throwable $e) {}

// 4. Render Login Page if Unauthenticated
if (!$is_admin_logged_in) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - EnneaDash Voice</title>
        <script>
            window.LOGO_URL = <?php echo json_encode($logoUrl); ?>;
            (function() {
                const savedTheme = localStorage.getItem('theme') || 'dark';
                if (savedTheme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="app.css">
        <script src="https://unpkg.com/lucide@latest"></script>
        <style>
            body {
                background-color: var(--bg-primary);
                color: var(--text-primary);
                font-family: 'Plus Jakarta Sans', sans-serif;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                margin: 0;
            }
            .login-wrapper {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
            }
            .login-card {
                width: 100%;
                max-width: 440px;
                padding: 40px;
                position: relative;
                z-index: 10;
                border-radius: var(--radius-lg);
            }
            .login-header-icon {
                width: 60px;
                height: 60px;
                border-radius: 18px;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
                border: 1px solid var(--surface-glass-border);
                color: var(--accent-indigo);
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px auto;
                box-shadow: var(--shadow-glow);
            }
            .login-header-icon img,
            .login-header-icon svg {
                width: 36px;
                height: 36px;
                object-fit: contain;
            }
            .login-title {
                font-size: 1.75rem;
                text-align: center;
                margin-bottom: 8px;
                background: linear-gradient(to right, var(--title-gradient-start), var(--title-gradient-end));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .login-subtitle {
                text-align: center;
                font-size: 0.95rem;
                color: var(--text-secondary);
                margin-bottom: 32px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-label {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 8px;
            }
            .form-control {
                width: 100%;
                padding: 14px 18px;
                border-radius: var(--radius-md);
                border: 1px solid var(--surface-glass-border);
                background: var(--input-bg);
                color: var(--text-primary);
                font-family: inherit;
                font-size: 0.95rem;
                transition: var(--transition-smooth);
                outline: none;
            }
            .form-control:focus {
                border-color: var(--accent-indigo);
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
                background: var(--input-bg-focus);
            }
            .btn-login {
                width: 100%;
                padding: 14px;
                margin-top: 10px;
                font-size: 1rem;
            }
            .error-banner {
                background-color: rgba(239, 68, 68, 0.12);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: #ef4444;
                padding: 12px 16px;
                border-radius: var(--radius-md);
                font-size: 0.9rem;
                font-weight: 500;
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
        </style>
    </head>
    <body>
        <!-- Ambient Background Orbs -->
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
        <div class="ambient-orb orb-3"></div>

        <!-- Top Navigation Header -->
        <header class="app-header glass" style="max-width: 1300px; margin: 24px auto 0 auto; width: calc(100% - 48px);">
            <div class="logo">
                <span class="logo-icon"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="logo-img-nav"></span>
                <span class="logo-text">Ennea<span class="highlight">Dash</span> <span class="voice-badge">VOICE</span></span>
            </div>
            <div class="header-right">
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Toggle Theme" title="Toggle Theme">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>
                </button>
            </div>
        </header>

        <main class="login-wrapper">
            <div class="login-card glass">
                <div class="login-header-icon">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Enneagram Logo" class="login-logo-img">
                </div>
                <h1 class="login-title">Admin Control Portal</h1>
                <p class="login-subtitle">EnneaDash Voice Assessment Engine</p>
                
                <?php if ($login_error): ?>
                    <div class="error-banner">
                        <i data-lucide="alert-circle" style="width:18px; height:18px; flex-shrink:0;"></i>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="admin.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control" required placeholder="admin@enneadash.com" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="admin_password">Security Password</label>
                        <input type="password" id="admin_password" name="admin_password" class="form-control" required placeholder="••••••••••••" autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-login">
                        <i data-lucide="log-in" style="width:18px; height:18px;"></i>
                        <span>Sign In to Dashboard</span>
                    </button>
                </form>
            </div>
        </main>

        <footer class="app-footer" style="padding: 20px; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
            <p>&copy; 2026 EnneaDash Voice Admin Engine. All rights reserved.</p>
        </footer>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.lucide) lucide.createIcons();
                initTheme();
            });

            function initTheme() {
                const themeToggle = document.getElementById('theme-toggle');
                if (!themeToggle) return;
                themeToggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// 5. Backend Operations (AJAX Actions & Post Handling)
if ($is_db_connected && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // A. AJAX Toggle / Save Setting
    if ($action === 'save_setting' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $key = trim($_POST['key'] ?? '');
        $val = trim($_POST['value'] ?? '');
        if (!empty($key)) {
            $stmt = $pdo->prepare("REPLACE INTO admin_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $val]);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    // B. Bulk CSV Upload
    if ($action === 'upload_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['csv_file']['tmp_name'];
            $file = fopen($tmpPath, 'r');
            $header = fgetcsv($file); // Skip header line
            
            $insertedCount = 0;
            $updatedCount = 0;
            $emailSentCount = 0;
            
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 2) continue;
                $name = trim($row[0] ?? '');
                $email = trim($row[1] ?? '');
                $age_group = trim($row[2] ?? '18-25');
                $gender = trim($row[3] ?? 'Male');
                $phone_number = trim($row[4] ?? '');
                $department = trim($row[5] ?? '');
                
                if (!empty($email) && !empty($name)) {
                    // Check user existence
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email_id = ?");
                    $stmt->execute([$email]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        $user_id = $existing['id'];
                        $stmtUp = $pdo->prepare("UPDATE user_profiles SET name = ?, age_group = ?, gender = ?, phone_number = ?, department = ?, updated_at = NOW() WHERE user_id = ?");
                        $stmtUp->execute([$name, $age_group, $gender, $phone_number, $department, $user_id]);
                        $stmtUserUp = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                        $stmtUserUp->execute([$user_id]);
                        $updatedCount++;
                    } else {
                        $default_pass = 'ed@123';
                        $stmtIn = $pdo->prepare("INSERT INTO users (email_id, password_hash, force_password_change, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
                        $stmtIn->execute([$email, password_hash($default_pass, PASSWORD_DEFAULT)]);
                        $user_id = $pdo->lastInsertId();
                        
                        $stmtProf = $pdo->prepare("INSERT INTO user_profiles (user_id, name, age_group, gender, phone_number, department, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                        $stmtProf->execute([$user_id, $name, $age_group, $gender, $phone_number, $department]);
                        $insertedCount++;

                        // Automatically send onboarding email with login credentials and assessment link
                        try {
                            send_onboarding_email($email, $name);
                            $emailSentCount++;
                        } catch (Throwable $mailErr) {
                            error_log("Failed to send onboarding email to {$email}: " . $mailErr->getMessage());
                        }
                    }
                }
            }
            fclose($file);
            $_SESSION['admin_flash'] = "Bulk upload successful: {$insertedCount} new users created ({$emailSentCount} onboarding emails sent), {$updatedCount} profiles updated.";
        } else {
            $_SESSION['admin_flash_error'] = "Failed to process CSV file upload.";
        }
        header("Location: admin.php");
        exit;
    }
    
    // C. Assessment Access Override Update
    if ($action === 'update_override' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $user_id = intval($_POST['user_id'] ?? 0);
        $override = trim($_POST['override'] ?? 'full_access');
        
        $valid_overrides = ['full_access', 'voice_only', 'typing_only', 'scale_only', 'block_access'];
        if (!in_array($override, $valid_overrides)) {
            $override = 'full_access';
        }
        
        if ($user_id > 0) {
            $stmt = $pdo->prepare("UPDATE user_profiles SET assessment_override = ? WHERE user_id = ?");
            $stmt->execute([$override, $user_id]);
            echo json_encode(['success' => true, 'override' => $override]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Invalid user parameter.']);
        exit;
    }
    
    // D. Delete User Profile
    if ($action === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            try {
                $pdo->prepare("DELETE FROM user_profiles WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM enneagram_reports WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM exam_answers WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM user_progress WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                echo json_encode(['success' => true]);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }
    
    // E. Create User Account manually
    if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($password)) {
            $password = 'ed@123';
        }
        
        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Name and email are required.']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_id = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Email address already exists.']);
                exit;
            }
            
            $stmtIn = $pdo->prepare("INSERT INTO users (email_id, password_hash, force_password_change, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
            $stmtIn->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
            $user_id = $pdo->lastInsertId();
            
            $stmtProf = $pdo->prepare("INSERT INTO user_profiles (user_id, name, age_group, gender, created_at, updated_at) VALUES (?, ?, '18-25', 'Not specified', NOW(), NOW())");
            $stmtProf->execute([$user_id, $name]);
            
            // Send onboarding email
            try {
                send_onboarding_email($email, $name);
            } catch (Throwable $mailErr) {
                error_log("Failed to send onboarding email to {$email}: " . $mailErr->getMessage());
            }

            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // F. Get Detailed User Report Summary Modal
    if ($action === 'get_user_report' && isset($_GET['user_id'])) {
        header('Content-Type: application/json');
        $user_id = intval($_GET['user_id']);
        
        $stmtU = $pdo->prepare("SELECT p.name, u.email_id as email FROM user_profiles p JOIN users u ON p.user_id = u.id WHERE u.id = ?");
        $stmtU->execute([$user_id]);
        $user = $stmtU->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found.']);
            exit;
        }
        
        $stmtR = $pdo->prepare("SELECT enneagram_type, wing_1, wing_2, raw_scores, created_at FROM enneagram_reports WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmtR->execute([$user_id]);
        $report = $stmtR->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            $domType = intval($report['enneagram_type']);
            $w1 = intval($report['wing_1']);
            $w2 = intval($report['wing_2']);
            $rawScores = !empty($report['raw_scores']) ? json_decode($report['raw_scores'], true) : [];
            
            $scoreW1 = isset($rawScores[$w1]) ? intval($rawScores[$w1]) : 0;
            $scoreW2 = isset($rawScores[$w2]) ? intval($rawScores[$w2]) : 0;
            $activeWing = ($scoreW1 > 0 || $scoreW2 > 0) ? ($scoreW1 >= $scoreW2 ? $w1 : $w2) : $w1;
            $hasWing = ($activeWing > 0);

            echo json_encode([
                'success' => true,
                'user' => $user,
                'completed' => true,
                'report' => [
                    'enneagram_type' => $domType,
                    'wing' => $hasWing ? $activeWing : null,
                    'final_result' => 'Type ' . $domType . ($hasWing ? ' w' . $activeWing : ''),
                    'date' => date('M j, Y H:i', strtotime($report['created_at']))
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'user' => $user,
                'completed' => false
            ]);
        }
        exit;
    }

    // H. Get All Feedbacks API (Admin)
    if ($action === 'get_all_feedbacks' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        try {
            $stmt = $pdo->query("
                SELECT f.id, f.user_id, f.title, f.description, f.attachment_path, f.attachment_name, f.status, f.admin_notes, f.created_at, f.updated_at,
                       COALESCE(NULLIF(TRIM(p.name), ''), u.email_id, CONCAT('User #', u.id)) as user_name,
                       u.email_id as user_email
                FROM user_feedbacks f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                ORDER BY f.id DESC
            ");
            $feedbacks = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            echo json_encode(['success' => true, 'feedbacks' => $feedbacks]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // I. Get Feedback Detail API (Admin)
    if ($action === 'get_feedback_detail' && isset($_GET['id'])) {
        header('Content-Type: application/json');
        $id = intval($_GET['id']);
        try {
            $stmt = $pdo->prepare("
                SELECT f.id, f.user_id, f.title, f.description, f.attachment_path, f.attachment_name, f.status, f.admin_notes, f.created_at, f.updated_at,
                       COALESCE(NULLIF(TRIM(p.name), ''), u.email_id, CONCAT('User #', u.id)) as user_name,
                       u.email_id as user_email
                FROM user_feedbacks f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE f.id = ?
            ");
            $stmt->execute([$id]);
            $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$feedback) {
                echo json_encode(['success' => false, 'error' => 'Feedback record not found.']);
                exit;
            }

            $feedback['attachment_exists'] = false;
            if (!empty($feedback['attachment_path'])) {
                $fullPath = __DIR__ . '/' . ltrim($feedback['attachment_path'], '/');
                $feedback['attachment_exists'] = file_exists($fullPath);
            }

            echo json_encode(['success' => true, 'feedback' => $feedback]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // J. Update Feedback Status & Admin Notes (Admin)
    if ($action === 'update_feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $id = intval($_POST['feedback_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Submitted');
        $admin_notes = trim($_POST['admin_notes'] ?? '');

        $valid_statuses = ['Submitted', 'Open', 'In Progress', 'Resolved', 'Closed'];
        if (!in_array($status, $valid_statuses, true)) {
            $status = 'Submitted';
        }

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE user_feedbacks SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $admin_notes, $id]);
                echo json_encode(['success' => true, 'id' => $id, 'status' => $status, 'admin_notes' => $admin_notes]);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Invalid feedback ID.']);
        exit;
    }

    // K. Delete Feedback (Admin)
    if ($action === 'delete_feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $id = intval($_POST['feedback_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_feedbacks WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Invalid feedback ID.']);
        exit;
    }
}

// Fetch Admin Settings & Dynamic Dashboard Data
$active_mode = 'voice';
$setting_assessment = true;
$setting_wings = true;
$setting_variants = true;
$setting_tritype = false;
$setting_autosave = true;

$total_profiles = 0;
$completed_assessments = 0;
$active_users_today = 0;
$user_records = [];

if ($is_db_connected && $pdo) {
    try {
        $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM admin_settings");
        if ($stmtSettings) {
            $settingsData = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            if (isset($settingsData['active_mode'])) $active_mode = $settingsData['active_mode'];
            if (isset($settingsData['assessment'])) $setting_assessment = ($settingsData['assessment'] === 'true');
            if (isset($settingsData['wings'])) $setting_wings = ($settingsData['wings'] === 'true');
            if (isset($settingsData['variants'])) $setting_variants = ($settingsData['variants'] === 'true');
            if (isset($settingsData['tritype'])) $setting_tritype = ($settingsData['tritype'] === 'true');
            if (isset($settingsData['autosave'])) $setting_autosave = ($settingsData['autosave'] === 'true');
        }

        $total_profiles = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $completed_assessments = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM enneagram_reports")->fetchColumn();
        
        $active_users_today = $pdo->query("
            SELECT COUNT(DISTINCT user_id) FROM (
                SELECT user_id FROM enneagram_reports WHERE DATE(created_at) = CURDATE()
                UNION
                SELECT user_id FROM exam_sessions WHERE DATE(created_at) = CURDATE()
                UNION
                SELECT s.user_id FROM exam_answers a JOIN exam_sessions s ON a.session_id = s.id WHERE DATE(a.created_at) = CURDATE()
            ) active_today_users
        ")->fetchColumn();

        $stmt = $pdo->query("SELECT u.id, 
                                    COALESCE(NULLIF(TRIM(p.name), ''), u.email_id, CONCAT('User #', u.id)) as name, 
                                    u.email_id as email, 
                                    p.age_group, 
                                    p.gender, 
                                    p.phone_number, 
                                    p.department, 
                                    COALESCE(p.assessment_override, 'default') as assessment_override,
                                    COALESCE(CONCAT('Type ', r.enneagram_type, IF(r.wing_1 IS NOT NULL AND r.wing_1 > 0, CONCAT(' w', r.wing_1), '')), 'Incomplete') as final_type
                             FROM users u
                             LEFT JOIN user_profiles p ON u.id = p.user_id
                             LEFT JOIN (
                                 SELECT user_id, enneagram_type, wing_1 
                                 FROM enneagram_reports 
                                 WHERE id IN (SELECT MAX(id) FROM enneagram_reports GROUP BY user_id)
                             ) r ON u.id = r.user_id
                             ORDER BY u.id DESC");
        $user_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $feedback_records = [];
        $total_feedbacks = 0;
        $open_feedbacks_count = 0;
        $stmtFbList = $pdo->query("
            SELECT f.id, f.user_id, f.title, f.description, f.attachment_path, f.attachment_name, f.status, f.admin_notes, f.created_at, f.updated_at,
                   COALESCE(NULLIF(TRIM(p.name), ''), u.email_id, CONCAT('User #', u.id)) as user_name,
                   u.email_id as user_email
            FROM user_feedbacks f
            LEFT JOIN users u ON f.user_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            ORDER BY f.id DESC
        ");
        if ($stmtFbList) {
            $feedback_records = $stmtFbList->fetchAll(PDO::FETCH_ASSOC);
            $total_feedbacks = count($feedback_records);
            foreach ($feedback_records as $fbRow) {
                $st = strtolower($fbRow['status'] ?? '');
                if ($st === 'submitted' || $st === 'open' || str_contains($st, 'progress')) {
                    $open_feedbacks_count++;
                }
            }
        }
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enneagram Admin Dashboard - EnneaDash Voice</title>
    <script>
        window.LOGO_URL = <?php echo json_encode($logoUrl); ?>;
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-app: #090b14;
            --bg-card: rgba(18, 22, 47, 0.55);
            --bg-card-hover: rgba(26, 32, 66, 0.7);
            --color-primary: #8b5cf6;
            --color-primary-light: rgba(139, 92, 246, 0.15);
            --color-primary-dark: #7c3aed;
            --color-accent-indigo: #6366f1;
            
            --color-success: #10b981;
            --color-success-light: rgba(16, 185, 129, 0.12);
            --color-warning: #f59e0b;
            --color-warning-light: rgba(245, 158, 11, 0.12);
            --color-danger: #ef4444;
            --color-danger-light: rgba(239, 68, 68, 0.15);
            --color-border: rgba(255, 255, 255, 0.08);
            
            --text-main: #f8fafc;
            --text-heading: #ffffff;
            --text-muted: #94a3b8;
            --text-light: #64748b;
            
            --radius-card: 20px;
            --radius-pill: 9999px;
            --shadow-premium: 0 20px 40px rgba(0, 0, 0, 0.37), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            --shadow-glow: 0 8px 32px 0 rgba(99, 102, 241, 0.2);
            --input-bg: rgba(255, 255, 255, 0.03);
            --table-header-bg: rgba(255, 255, 255, 0.03);
            --table-row-hover: rgba(255, 255, 255, 0.03);
            --modal-overlay-bg: rgba(5, 7, 15, 0.75);
            --dropdown-bg: rgba(16, 19, 38, 0.95);
        }

        [data-theme="light"] {
            --bg-app: #f8fafc;
            --bg-card: rgba(255, 255, 255, 0.85);
            --bg-card-hover: #ffffff;
            --color-primary: #8b5cf6;
            --color-primary-light: rgba(139, 92, 246, 0.1);
            --color-primary-dark: #7c3aed;
            --color-accent-indigo: #6366f1;
            
            --color-success: #10b981;
            --color-success-light: rgba(16, 185, 129, 0.1);
            --color-warning: #d97706;
            --color-warning-light: rgba(217, 119, 6, 0.1);
            --color-danger: #ef4444;
            --color-danger-light: rgba(239, 68, 68, 0.1);
            --color-border: rgba(15, 23, 42, 0.08);
            
            --text-main: #0f172a;
            --text-heading: #0f172a;
            --text-muted: #475569;
            --text-light: #64748b;
            
            --shadow-premium: 0 10px 30px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-glow: 0 8px 32px 0 rgba(99, 102, 241, 0.08);
            --input-bg: rgba(15, 23, 42, 0.03);
            --table-header-bg: rgba(15, 23, 42, 0.03);
            --table-row-hover: rgba(15, 23, 42, 0.02);
            --modal-overlay-bg: rgba(15, 23, 42, 0.5);
            --dropdown-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .dashboard-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Top Header Navbar Overrides */
        .admin-nav-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--surface-glass-border);
            background: var(--surface-glass);
            transition: var(--transition-smooth);
        }

        .admin-profile:hover {
            border-color: var(--accent-indigo);
            background: var(--btn-secondary-hover-bg);
        }

        .admin-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-indigo), var(--color-primary));
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .admin-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .admin-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--dropdown-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--surface-glass-border);
            border-radius: 12px;
            box-shadow: var(--shadow-glass);
            width: 180px;
            z-index: 100;
            display: none;
            flex-direction: column;
            padding: 6px;
        }

        .admin-dropdown-menu.show {
            display: flex;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 0.85rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition-smooth);
        }

        .dropdown-item:hover {
            background-color: var(--color-primary-light);
        }

        /* KPI Cards Grid Layout */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: 1fr; }
        }

        .kpi-card {
            background: var(--surface-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--surface-glass-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-glass);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition-smooth);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-indigo);
        }

        .kpi-icon-wrapper {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kpi-icon-wrapper.kpi-primary {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            color: var(--accent-indigo);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .kpi-icon-wrapper.kpi-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .kpi-icon-wrapper.kpi-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(217, 119, 6, 0.2) 100%);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .kpi-icon-wrapper svg {
            width: 26px;
            height: 26px;
        }

        .kpi-details {
            display: flex;
            flex-direction: column;
        }

        .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
        }

        .kpi-title {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Main Dashboard Sections & Utilities Grid Layout */
        .dashboard-sections {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .utilities-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        .utilities-grid .card {
            margin-bottom: 0;
        }

        @media (max-width: 900px) {
            .utilities-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Two-Column Main Layout fallback */
        .main-layout {
            display: grid;
            grid-template-columns: 1.85fr 1fr;
            gap: 28px;
        }

        @media (max-width: 1024px) {
            .main-layout { grid-template-columns: 1fr; }
        }

        /* Cards */
        .card {
            background: var(--surface-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--surface-glass-border);
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow-glass);
            margin-bottom: 28px;
            transition: var(--transition-smooth);
        }

        .card-header {
            margin-bottom: 20px;
        }

        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title svg {
            width: 22px;
            height: 22px;
            color: var(--accent-indigo);
        }

        .card-subtitle {
            font-size: 0.88rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Search input bar */
        .search-input {
            width: 100%;
            padding: 12px 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--surface-glass-border);
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: var(--transition-smooth);
        }

        .search-input:focus {
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        /* Assessment Config cards */
        .config-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 1024px) {
            .config-cards-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .config-cards-grid { grid-template-columns: 1fr; }
        }

        .config-card {
            background: var(--input-bg);
            border: 1px solid var(--surface-glass-border);
            border-radius: 14px;
            padding: 18px;
            cursor: pointer;
            transition: var(--transition-smooth);
            display: flex;
            flex-direction: column;
        }

        .config-card:hover {
            border-color: var(--accent-indigo);
            transform: translateY(-2px);
        }

        .config-card.selected {
            border: 2px solid var(--accent-indigo);
            background: var(--color-primary-light);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
        }

        .config-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .config-card p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        /* Switch UI component */
        .settings-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--surface-glass-border);
        }

        .settings-row:last-child {
            border-bottom: none;
        }

        .settings-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            display: block;
            margin-bottom: 2px;
        }

        .settings-description {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--input-bg);
            transition: var(--transition-smooth);
            border-radius: var(--radius-pill);
            border: 1px solid var(--surface-glass-border);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: var(--text-secondary);
            transition: var(--transition-smooth);
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--accent-indigo);
            border-color: var(--accent-indigo);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
            background-color: #ffffff;
        }

        /* Database Status Alert Bar */
        .db-status-bar {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #10b981;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        /* Table formatting */
        .table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--surface-glass-border);
            border-radius: 14px;
            background: var(--input-bg);
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .user-table th {
            background-color: var(--table-header-bg);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 14px 18px;
            border-bottom: 1px solid var(--surface-glass-border);
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
        }

        .user-table td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--surface-glass-border);
            vertical-align: middle;
        }

        .user-table tr:hover td {
            background-color: var(--table-row-hover);
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        .badge-type-pill {
            background-color: var(--color-primary-light);
            color: var(--accent-indigo);
            padding: 4px 12px;
            border-radius: var(--radius-pill);
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid rgba(99, 102, 241, 0.25);
            display: inline-block;
        }

        .badge-incomplete-pill {
            background-color: var(--input-bg);
            color: var(--text-muted);
            padding: 4px 12px;
            border-radius: var(--radius-pill);
            font-size: 0.78rem;
            font-weight: 500;
            border: 1px solid var(--surface-glass-border);
            display: inline-block;
        }

        .badge-status-pill {
            padding: 4px 12px;
            border-radius: var(--radius-pill);
            font-size: 0.76rem;
            font-weight: 700;
            display: inline-block;
        }

        .badge-status-pill.submitted, .badge-status-pill.open {
            background-color: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-status-pill.in-progress, .badge-status-pill.in_progress {
            background-color: rgba(99, 102, 241, 0.15);
            color: #6366f1;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .badge-status-pill.resolved {
            background-color: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-status-pill.closed {
            background-color: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .user-name-bold {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .cell-select {
            border: 1px solid var(--surface-glass-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.82rem;
            color: var(--text-primary);
            background: var(--input-bg);
            transition: var(--transition-smooth);
            outline: none;
            cursor: pointer;
            width: 100%;
        }

        .cell-select:focus {
            border-color: var(--accent-indigo);
        }

        /* Modal Overlay & Styling */
        .modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--modal-overlay-bg);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--surface-glass-border);
            box-shadow: var(--shadow-glass);
            width: 100%;
            max-width: 600px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--surface-glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex-grow: 1;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .admin-toast {
            background: var(--surface-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--surface-glass-border);
            color: var(--text-primary);
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 500;
            box-shadow: var(--shadow-glass);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
            animation: slideIn 0.25s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(20px); }
        }
    </style>
</head>
<body>

    <!-- Ambient Background Orbs -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>

    <div class="dashboard-container">
        <!-- Top Navigation Header -->
        <header class="app-header glass">
            <div class="logo">
                <span class="logo-icon"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="logo-img-nav"></span>
                <span class="logo-text">Ennea<span class="highlight">Dash</span> <span class="voice-badge">VOICE</span></span>
            </div>
            
            <div class="header-right">
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Toggle Theme" title="Toggle Theme">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>
                </button>
                <div class="admin-profile" id="admin-profile-toggle" onclick="toggleAdminDropdown(event)" style="position: relative;">
                    <div class="admin-avatar">AD</div>
                    <span class="admin-name">Admin</span>
                    <div class="admin-dropdown-menu" id="admin-dropdown">
                        <a href="admin.php?action=admin_logout" class="dropdown-item">
                            <i data-lucide="log-out" style="width:14px; height:14px;"></i> Logout
                        </a>
                    </div>
                </div>
                <a href="admin.php?action=admin_logout" class="btn btn-danger" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 9999px;">
                    <i data-lucide="log-out" style="width:14px; height:14px;"></i> Logout
                </a>
            </div>
        </header>

        <!-- KPI Grid -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon-wrapper kpi-primary">
                    <i data-lucide="users"></i>
                </div>
                <div class="kpi-details">
                    <span class="kpi-value"><?php echo number_format($total_profiles); ?></span>
                    <span class="kpi-title">Total Profiles</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon-wrapper kpi-success">
                    <i data-lucide="check-circle"></i>
                </div>
                <div class="kpi-details">
                    <span class="kpi-value"><?php echo number_format($completed_assessments); ?></span>
                    <span class="kpi-title">Assessments Completed</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon-wrapper kpi-warning">
                    <i data-lucide="activity"></i>
                </div>
                <div class="kpi-details">
                    <span class="kpi-value"><?php echo number_format($active_users_today); ?></span>
                    <span class="kpi-title">Active Users Today</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-wrapper" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3);">
                    <i data-lucide="message-square"></i>
                </div>
                <div class="kpi-details">
                    <span class="kpi-value"><?php echo number_format($total_feedbacks); ?></span>
                    <span class="kpi-title">User Feedbacks (<?php echo $open_feedbacks_count; ?> Pending)</span>
                </div>
            </div>
        </section>

        <!-- Main Responsive Layout Sections -->
        <div class="dashboard-sections">
                <!-- User Profiles Table Card -->
                <section class="card user-management-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2 class="card-title">
                                <i data-lucide="users"></i> User Profiles
                                <span style="font-size:12px; font-weight:600; background-color:var(--color-primary-light); color:var(--accent-indigo); padding:2px 10px; border-radius:12px; margin-left:8px; border:1px solid rgba(99, 102, 241, 0.2);" id="user-count-badge">
                                    Showing 5 of <?php echo count($user_records); ?>
                                </span>
                            </h2>
                            <p class="card-subtitle">Compiled registered participant records, cognitive reports, and active session statuses.</p>
                        </div>
                        <div>
                            <button id="view-all-users-btn" class="btn btn-secondary" onclick="toggleViewAllUsers()" style="font-size:13px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                                <i data-lucide="list" style="width:14px; height:14px;"></i> View All (<?php echo count($user_records); ?>)
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <input type="text" id="user-search-input" class="search-input" placeholder="Search participants by name, email, or archetype..." oninput="filterUserTable()">
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>User Details</th>
                                    <th>Gender/Age</th>
                                    <th>Cognitive Archetype</th>
                                    <th>Assessment Access Override</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($user_records)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 32px;">
                                        <i data-lucide="info" style="width: 24px; height: 24px; margin-bottom: 8px; display: inline-block;"></i>
                                        <p>No participant records found in the database.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <tr id="no-search-results-row" style="display: none;">
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 32px;">
                                            <i data-lucide="search-x" style="width: 24px; height: 24px; margin-bottom: 8px; display: inline-block;"></i>
                                            <p>No matching users found for your search query.</p>
                                        </td>
                                    </tr>
                                    <?php foreach ($user_records as $u): ?>
                                    <tr class="user-row" id="user-row-<?php echo $u['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>" 
                                        data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>" 
                                        data-archetype="<?php echo htmlspecialchars($u['final_type'], ENT_QUOTES); ?>">
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:4px;">
                                                <span class="user-name-bold"><?php echo htmlspecialchars($u['name']); ?></span>
                                                <span style="font-size:12px; color:var(--text-secondary);"><?php echo htmlspecialchars($u['email']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size:13px; color:var(--text-primary);"><?php echo htmlspecialchars($u['gender'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($u['age_group'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($u['final_type'] !== 'Incomplete'): ?>
                                                <span class="badge-type-pill"><?php echo htmlspecialchars($u['final_type']); ?></span>
                                            <?php else: ?>
                                                <span class="badge-incomplete-pill">Incomplete</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="cell-select" onchange="updateOverrideSetting(<?php echo $u['id']; ?>, this.value)">
                                                <option value="voice_only" <?php echo ($u['assessment_override'] === 'voice_only') ? 'selected' : ''; ?>>Voice Only</option>
                                                <option value="typing_only" <?php echo ($u['assessment_override'] === 'typing_only') ? 'selected' : ''; ?>>Typing Only</option>
                                                <option value="full_access" <?php echo ($u['assessment_override'] === 'full_access' || $u['assessment_override'] === 'default' || empty($u['assessment_override'])) ? 'selected' : ''; ?>>Full Access (Voice + Typing)</option>
                                                <option value="scale_only" <?php echo ($u['assessment_override'] === 'scale_only') ? 'selected' : ''; ?>>1-5 Scale Only</option>
                                                <option value="block_access" <?php echo ($u['assessment_override'] === 'block_access' || $u['assessment_override'] === 'blocked') ? 'selected' : ''; ?>>Block Access</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:8px;">
                                                <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px;" onclick="reviewUserReport(<?php echo $u['id']; ?>)">
                                                    <i data-lucide="eye" style="width:14px; height:14px;"></i> View Report
                                                </button>
                                                <button class="btn btn-danger" style="padding:6px 12px; font-size:12px;" onclick="restartUserSession(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')">
                                                    <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i> Reset Session
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Feedback Management Card -->
                <section class="card feedback-management-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2 class="card-title">
                                <i data-lucide="message-square"></i> Feedback Management
                                <span style="font-size:12px; font-weight:600; background-color:var(--color-primary-light); color:var(--accent-indigo); padding:2px 10px; border-radius:12px; margin-left:8px; border:1px solid rgba(99, 102, 241, 0.2);" id="feedback-count-badge">
                                    Showing <?php echo min(5, count($feedback_records)); ?> of <?php echo count($feedback_records); ?>
                                </span>
                            </h2>
                            <p class="card-subtitle">Review, manage, track, and update user-submitted issues and feature feedback.</p>
                        </div>
                        <div>
                            <button id="view-all-feedback-btn" class="btn btn-secondary" onclick="toggleViewAllFeedbacks()" style="font-size:13px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                                <i data-lucide="list" style="width:14px; height:14px;"></i> View All (<?php echo count($feedback_records); ?>)
                            </button>
                        </div>
                    </div>

                    <!-- Search and Status Filter controls -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <input type="text" id="feedback-search-input" class="search-input" placeholder="Search feedback by ID, title, user name, or email..." oninput="filterFeedbackTable()">
                        <select id="feedback-status-filter" class="cell-select" onchange="filterFeedbackTable()" style="height: 44px;">
                            <option value="all">All Statuses</option>
                            <option value="submitted">Submitted</option>
                            <option value="open">Open</option>
                            <option value="in progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="table-wrapper">
                        <table class="user-table" id="admin-feedback-table">
                            <thead>
                                <tr>
                                    <th>Feedback ID</th>
                                    <th>Title</th>
                                    <th>Raised By</th>
                                    <th>Raised On</th>
                                    <th>Status</th>
                                    <th>Attachment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedback_records)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 32px;">
                                        <i data-lucide="info" style="width: 24px; height: 24px; margin-bottom: 8px; display: inline-block;"></i>
                                        <p>No user feedback submissions found in the database.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <tr id="no-feedback-results-row" style="display: none;">
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 32px;">
                                            <i data-lucide="search-x" style="width: 24px; height: 24px; margin-bottom: 8px; display: inline-block;"></i>
                                            <p>No matching feedback items found for your search query.</p>
                                        </td>
                                    </tr>
                                    <?php foreach ($feedback_records as $index => $fb): 
                                        $fbStatus = $fb['status'] ?? 'Submitted';
                                        $statusClass = strtolower(str_replace(' ', '-', $fbStatus));
                                        $raisedDate = date('M j, Y H:i', strtotime($fb['created_at']));
                                    ?>
                                    <tr class="feedback-row" id="feedback-row-<?php echo $fb['id']; ?>"
                                        data-id="<?php echo $fb['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($fb['title'], ENT_QUOTES); ?>"
                                        data-user="<?php echo htmlspecialchars($fb['user_name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($fb['user_email'], ENT_QUOTES); ?>"
                                        data-status="<?php echo strtolower(htmlspecialchars($fbStatus, ENT_QUOTES)); ?>">
                                        <td>
                                            <span style="font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--accent-indigo);">#<?php echo $fb['id']; ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-primary); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($fb['title']); ?>">
                                                <?php echo htmlspecialchars($fb['title']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                                <span style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($fb['user_name']); ?></span>
                                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($fb['user_email']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo $raisedDate; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($fbStatus); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($fb['attachment_path'])): 
                                                $attPath = '/' . ltrim($fb['attachment_path'], '/');
                                                $rawAttName = !empty($fb['attachment_name']) ? $fb['attachment_name'] : 'File';
                                                $attDisplayName = (strlen($rawAttName) > 16) ? substr($rawAttName, 0, 13) . '...' : $rawAttName;
                                            ?>
                                                <a href="<?php echo htmlspecialchars($attPath); ?>" target="_blank" class="feedback-attachment-link" style="font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; color: var(--accent-indigo); text-decoration: underline;" title="<?php echo htmlspecialchars($rawAttName); ?>">
                                                    <i data-lucide="paperclip" style="width: 12px; height: 12px;"></i>
                                                    <?php echo htmlspecialchars($attDisplayName); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size: 0.78rem; color: var(--text-muted);">No File</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px;">
                                                <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openAdminFeedbackModal(<?php echo $fb['id']; ?>)">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> View Feedback
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <!-- 3. Utilities & Bulk User Upload Section (Positioned below Feedback Management) -->
            <div class="utilities-grid">
                <!-- Bulk User Upload Card ("Upload User Details") -->
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i data-lucide="upload"></i> Bulk User Upload</h2>
                        <p class="card-subtitle">Import participant accounts in bulk using CSV.</p>
                    </div>
                    <form action="admin.php?action=upload_csv" method="POST" enctype="multipart/form-data">
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <input type="file" name="csv_file" accept=".csv" required style="font-size:12px; padding:10px; border:1px dashed var(--surface-glass-border); border-radius:10px; background:var(--input-bg); color:var(--text-primary); width:100%; cursor:pointer;">
                            <button type="submit" class="btn btn-primary" style="width:100%;">
                                <i data-lucide="upload" style="width:16px; height:16px;"></i> Upload CSV
                            </button>
                            <a href="admin.php?action=download_sample_csv" class="btn btn-secondary" style="width:100%; font-size:12px; display:inline-flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
                                <i data-lucide="download" style="width:14px; height:14px;"></i> Download CSV Sample
                            </a>
                        </div>
                    </form>
                </section>

                <!-- System Utilities & Reports Card -->
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i data-lucide="file-text"></i> System Utilities & Reports</h2>
                        <p class="card-subtitle">Export records and system analytics.</p>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button class="btn btn-secondary" style="width:100%; justify-content:flex-start;" onclick="downloadCSV()">
                            <i data-lucide="file-text" style="width:16px; height:16px;"></i> Download Full CSV Export
                        </button>
                    </div>
                </section>
            </div>

            <!-- 4. Assessment Configuration Card -->
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title"><i data-lucide="sliders"></i> Assessment Configuration</h2>
                    <p class="card-subtitle">Select active engine parameters and default voice assessment settings.</p>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:12px; display:block;">Active Assessment Input Mode</label>
                    <div class="config-cards-grid">
                        <div class="config-card <?php echo ($active_mode === 'typing') ? 'selected' : ''; ?>" id="config-typing" onclick="selectConfigMode('typing')">
                            <h3>Typing Option</h3>
                            <p>Participants respond using Likert 1-5 scale ratings and text explanations.</p>
                        </div>
                        <div class="config-card <?php echo ($active_mode === 'voice') ? 'selected' : ''; ?>" id="config-voice" onclick="selectConfigMode('voice')">
                            <h3>Voice Note Option</h3>
                            <p>Participants respond using Likert 1-5 scale ratings and voice note recordings.</p>
                        </div>
                        <div class="config-card <?php echo ($active_mode === 'scale') ? 'selected' : ''; ?>" id="config-scale" onclick="selectConfigMode('scale')">
                            <h3>1-5 Scale Option</h3>
                            <p>Participants respond strictly using the 1 to 5 numerical scale agreement rating.</p>
                        </div>
                        <div class="config-card <?php echo ($active_mode === 'hybrid') ? 'selected' : ''; ?>" id="config-hybrid" onclick="selectConfigMode('hybrid')">
                            <h3>All Options</h3>
                            <p>Combines 1-5 scale ratings, typing text explanations, and voice note recordings.</p>
                        </div>
                    </div>
                </div>

                <div class="settings-list">
                    <div class="settings-row">
                        <div class="settings-info">
                            <span class="settings-label">Assessment Enabled</span>
                            <span class="settings-description">Allow participants to take or resume Enneagram assessments.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-assessment" onchange="saveToggleSetting('assessment')">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="settings-row">
                        <div class="settings-info">
                            <span class="settings-label">Wings Analysis</span>
                            <span class="settings-description">Include adjacent wing calculation in the final report output.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-wings" onchange="saveToggleSetting('wings')">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="settings-row">
                        <div class="settings-info">
                            <span class="settings-label">Instinctual Variants</span>
                            <span class="settings-description">Calculate Self-Preservation, Social, and Sexual subtype dynamics.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-variants" onchange="saveToggleSetting('variants')">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="settings-row">
                        <div class="settings-info">
                            <span class="settings-label">Tritype Breakdown</span>
                            <span class="settings-description">Generate three-center cognitive archetype distribution.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-tritype" onchange="saveToggleSetting('tritype')">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="settings-row">
                        <div class="settings-info">
                            <span class="settings-label">Auto-save Progress</span>
                            <span class="settings-description">Automatically preserve participant responses per question.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-autosave" onchange="saveToggleSetting('autosave')">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="app-footer" style="padding: 24px 0 0 0; text-align: center; font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--surface-glass-border); margin-top: 40px;">
            <p>&copy; 2026 EnneaDash Voice Assessment Engine. Built with premium design systems.</p>
        </footer>
    </div>

    <!-- User Report Modal -->
    <div class="modal" id="data-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modal-user-name">User Report Details</h3>
                    <p style="font-size: 12px; color: var(--text-secondary);" id="modal-user-email"></p>
                </div>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Dynamically populated via JS -->
            </div>
        </div>
    </div>

    <!-- Admin Feedback Detail & Edit Modal -->
    <div class="modal" id="admin-feedback-modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="message-square" style="color: var(--accent-indigo);"></i>
                    <span id="afb-modal-title">Feedback Details</span>
                </h3>
                <button class="modal-close" onclick="closeAdminFeedbackModal()">&times;</button>
            </div>
            <div class="modal-body" id="afb-modal-body">
                <!-- Dynamically populated via JS -->
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid var(--surface-glass-border); display: flex; justify-content: flex-end; gap: 10px; background: var(--bg-card);">
                <button type="button" class="btn btn-secondary" onclick="closeAdminFeedbackModal()">Cancel</button>
                <button type="button" id="afb-save-btn" class="btn btn-primary" onclick="saveAdminFeedbackChanges()">
                    <i data-lucide="save" style="width: 14px; height: 14px;"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
            initTheme();
            loadSettings();
            updateFeedbackTableVisibility();
        });

        function initTheme() {
            const themeToggle = document.getElementById('theme-toggle');
            if (!themeToggle) return;
            themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                showAdminToast(`Switched to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'info');
            });
        }

        function toggleAdminDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('admin-dropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        window.addEventListener('click', () => {
            const dropdown = document.getElementById('admin-dropdown');
            if (dropdown) dropdown.classList.remove('show');
        });

        function closeModal() {
            document.getElementById('data-modal').classList.remove('show');
        }

        function filterUserTable() {
            const input = document.getElementById('user-search-input');
            if (!input) return;
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.user-table tbody tr.user-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }

        function saveToggleSetting(key) {
            const checkbox = document.getElementById(`toggle-${key}`);
            if (checkbox && window.settingsLoaded) {
                const checked = checkbox.checked;
                const formData = new FormData();
                formData.append('key', key);
                formData.append('value', checked ? 'true' : 'false');
                
                fetch('admin.php?action=save_setting', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAdminToast('Setting saved successfully in database.', 'success');
                    } else {
                        showAdminToast('Failed to save setting.', 'error');
                        checkbox.checked = !checked;
                    }
                })
                .catch(() => {
                    showAdminToast('Network connection failed.', 'error');
                    checkbox.checked = !checked;
                });
            }
        }

        function selectConfigMode(mode) {
            const modes = ['typing', 'voice', 'scale', 'hybrid'];
            modes.forEach(m => {
                const card = document.getElementById(`config-${m}`);
                if (card) card.classList.remove('selected');
            });

            const selectedCard = document.getElementById(`config-${mode}`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
                
                if (window.settingsLoaded) {
                    const formData = new FormData();
                    formData.append('key', 'active_mode');
                    formData.append('value', mode);
                    
                    fetch('admin.php?action=save_setting', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAdminToast(`Assessment mode switched to ${mode}`, 'success');
                        } else {
                            showAdminToast('Failed to save assessment mode.', 'error');
                        }
                    });
                }
            }
        }

        let showingAllUsers = false;

        function toggleViewAllUsers() {
            showingAllUsers = !showingAllUsers;
            const searchInput = document.getElementById('user-search-input');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
            }
            updateUserTableVisibility();
        }

        function filterUserTable() {
            updateUserTableVisibility();
        }

        function updateUserTableVisibility() {
            const searchInput = document.getElementById('user-search-input');
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const rows = document.querySelectorAll('.user-row');
            const viewAllBtn = document.getElementById('view-all-users-btn');
            const countBadge = document.getElementById('user-count-badge');
            const noResultsRow = document.getElementById('no-search-results-row');
            
            let visibleCount = 0;
            const totalRows = rows.length;

            rows.forEach((row, index) => {
                const name = (row.getAttribute('data-name') || '').toLowerCase();
                const email = (row.getAttribute('data-email') || '').toLowerCase();
                const archetype = (row.getAttribute('data-archetype') || '').toLowerCase();

                const matches = (query === '') || name.includes(query) || email.includes(query) || archetype.includes(query);

                if (query.length > 0) {
                    if (matches) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    if (showingAllUsers || index < 5) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = (query.length > 0 && visibleCount === 0) ? '' : 'none';
            }

            if (viewAllBtn) {
                if (query.length > 0) {
                    viewAllBtn.style.display = 'none';
                } else {
                    viewAllBtn.style.display = 'inline-flex';
                    if (showingAllUsers) {
                        viewAllBtn.innerHTML = `<i data-lucide="chevron-up" style="width:14px; height:14px;"></i> Show Top 5`;
                    } else {
                        viewAllBtn.innerHTML = `<i data-lucide="list" style="width:14px; height:14px;"></i> View All (${totalRows})`;
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }

            if (countBadge) {
                if (query.length > 0) {
                    countBadge.textContent = `${visibleCount} Found`;
                } else {
                    countBadge.textContent = showingAllUsers ? `${totalRows} Total` : `Showing 5 of ${totalRows}`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateUserTableVisibility();
        });

        function loadSettings() {
            window.settingsLoaded = false;
            const activeMode = '<?php echo $active_mode; ?>';
            selectConfigMode(activeMode);

            const settings = {
                'assessment': <?php echo $setting_assessment ? 'true' : 'false'; ?>,
                'wings': <?php echo $setting_wings ? 'true' : 'false'; ?>,
                'variants': <?php echo $setting_variants ? 'true' : 'false'; ?>,
                'tritype': <?php echo $setting_tritype ? 'true' : 'false'; ?>,
                'autosave': <?php echo $setting_autosave ? 'true' : 'false'; ?>
            };

            Object.keys(settings).forEach(key => {
                const checkbox = document.getElementById(`toggle-${key}`);
                if (checkbox) checkbox.checked = settings[key];
            });
            window.settingsLoaded = true;
        }

        function updateOverrideSetting(userId, overrideValue) {
            const labels = {
                'voice_only': 'Voice Only',
                'typing_only': 'Typing Only',
                'full_access': 'Full Access (Voice + Typing)',
                'scale_only': '1-5 Scale Only',
                'block_access': 'Block Access'
            };
            const labelName = labels[overrideValue] || overrideValue;
            showAdminToast(`Updating access policy to ${labelName}...`, 'info');
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('override', overrideValue);
            
            fetch('admin.php?action=update_override', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAdminToast(`Access override updated to ${labelName}.`, 'success');
                } else {
                    showAdminToast(data.error || 'Failed to update override.', 'error');
                }
            })
            .catch(() => { showAdminToast('Network connection failed.', 'error'); });
        }

        function reviewUserReport(userId) {
            showAdminToast('Loading diagnostic report...', 'info');
            
            const enneagramDetails = {
                1: { name: "The Reformer", desc: "Rational, idealistic, principled, orderly, and self-controlled. Striving for perfection, they can be critical of themselves and others.", traits: "Ethical, organized, structured, conscientious, self-correcting.", growth: "Moves towards Type 7 embracing spontaneity, joy, and lightheartedness.", comm: "Structured and rational, relying on ethical guidelines and analytical correctness." },
                2: { name: "The Helper", desc: "Demonstrative, generous, people-pleasing, and empathetic. Wanting to feel loved and useful.", traits: "Empathetic, nurturing, warm, supportive, altruistic.", growth: "Moves towards Type 4 developing healthy self-care, creative expression, and authentic feelings.", comm: "Relationship-centric, prioritizing emotional impacts and connection." },
                3: { name: "The Achiever", desc: "Adaptable, ambitious, driven, and goal-oriented. Value productivity, competency, and achieving goals.", traits: "Goal-oriented, self-assured, efficient, energetic, charismatic.", growth: "Moves towards Type 6 becoming more cooperative, loyal, and team-minded.", comm: "Pragmatic, logical, fast-paced, focusing on execution and results." },
                4: { name: "The Individualist", desc: "Expressive, sensitive, authentic, and introspective. Value authenticity and unique creative expression.", traits: "Intuitive, authentic, sensitive, expressive, introspective.", growth: "Moves towards Type 1 translating feelings into objective action, discipline, and order.", comm: "Intuitive and emotional, guided by personal values and emotional depth." },
                5: { name: "The Investigator", desc: "Perceptive, innovative, cerebral, and independent. Specialize in deep analysis and mental clarity.", traits: "Analytical, insightful, independent, private, conceptual.", growth: "Moves towards Type 8 stepping into leadership and confident physical action.", comm: "Objective, data-driven, systematic, and intellectually concise." },
                6: { name: "The Loyalist", desc: "Committed, security-oriented, reliable, and trustworthy. Seeking stable guidance and consistency.", traits: "Reliable, committed, alert, trustworthy, collaborative.", growth: "Moves towards Type 9 finding inner calm, trusting life, and relaxing vigilance.", comm: "Collaborative, risk-conscious, consulting trust systems and planning contingencies." },
                7: { name: "The Enthusiast", desc: "Spontaneous, versatile, optimistic, and quick-thinking. Seeking positive experiences and options.", traits: "Optimistic, playful, quick-witted, adventurous, versatile.", growth: "Moves towards Type 5 developing deep focus, analytical capacity, and patience.", comm: "Fast, expansive, prioritizing novel ideas, future possibilities, and positive opportunities." },
                8: { name: "The Challenger", desc: "Self-confident, strong, assertive, and protective. Standing up for beliefs and resisting manipulation.", traits: "Direct, protective, decisive, powerful, truth-seeking.", growth: "Moves towards Type 2 displaying gentle care, empathy, and open-hearted vulnerability.", comm: "Direct, forceful, decisive, preferring swift execution and strong leadership." },
                9: { name: "The Peacemaker", desc: "Receptive, reassuring, agreeable, and diplomatic. Avoiding conflict to maintain inner peace.", traits: "Easygoing, harmonious, accommodating, patient, diplomatic.", growth: "Moves towards Type 3 asserting presence, prioritizing personal goals, and taking decisive action.", comm: "Harmonious, inclusive, and consensus-driven, ensuring all perspectives are valued." }
            };

            const wingNames = {
                "1w9": "The Idealist", "1w2": "The Activist",
                "2w1": "The Companion", "2w3": "The Host/Hostess",
                "3w2": "The Star", "3w4": "The Professional",
                "4w3": "The Aristocrat", "4w5": "The Bohemian",
                "5w4": "The Iconoclast", "5w6": "The Troubleshooter",
                "6w5": "The Defender", "6w7": "The Buddy",
                "7w6": "The Pathfinder", "7w8": "The Realist",
                "8w7": "The Independent", "8w9": "The Bear",
                "9w8": "The Referee", "9w1": "The Dreamer"
            };

            fetch(`admin.php?action=get_user_report&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal-user-name').innerText = data.user.name + " - Diagnostic Report";
                        document.getElementById('modal-user-email').innerText = data.user.email;
                        
                        const body = document.getElementById('modal-body-content');
                        body.innerHTML = '';
                        
                        if (!data.completed) {
                            body.innerHTML = `
                                <div style="text-align: center; padding: 30px 20px;">
                                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 6px; color:var(--text-primary);">Assessment Incomplete</h3>
                                    <p style="font-size: 13.5px; color: var(--text-secondary);">No completed assessment summary is available for this user.</p>
                                </div>
                            `;
                        } else {
                            const rep = data.report;
                            const domType = rep.enneagram_type;
                            const wingVal = rep.wing;
                            const wingKey = (domType && wingVal) ? `${domType}w${wingVal}` : '';
                            const wingArchetype = wingKey ? (wingNames[wingKey] || 'Enneagram Wing Archetype') : '';
                            const typeInfo = enneagramDetails[domType] || { name: `Type ${domType}`, desc: '', traits: '', growth: '', comm: '' };

                            body.innerHTML = `
                                <!-- Diagnostic Result Header Banner -->
                                <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(168, 85, 247, 0.12)); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 14px; padding: 24px; text-align: center; margin-bottom: 20px;">
                                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--accent-indigo); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">Diagnostic Assessment Summary</div>
                                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--text-primary); margin-bottom: 4px;">
                                        ${rep.final_result}
                                    </div>
                                    <div style="font-size: 1rem; font-weight: 600; color: var(--accent-cyan); margin-bottom: 12px;">
                                        ${typeInfo.name} ${wingArchetype ? '• Wing ' + wingVal + ' (' + wingArchetype + ')' : ''}
                                    </div>
                                    <div style="font-size: 0.82rem; color: var(--text-muted); border-top: 1px solid var(--surface-glass-border); padding-top: 10px; margin-top: 8px;">
                                        <strong>Completion Date &amp; Time:</strong> ${rep.date}
                                    </div>
                                </div>

                                <!-- Diagnostic Report Cards -->
                                <div style="display: flex; flex-direction: column; gap: 14px; text-align: left;">
                                    <!-- 1. Personality Summary -->
                                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 16px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px;">
                                            <span style="color: var(--accent-indigo);">●</span> Personality Summary
                                        </h4>
                                        <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${typeInfo.desc}</p>
                                    </div>

                                    <!-- 2. Dominant Wing Archetype & Description -->
                                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 16px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px;">
                                            <span style="color: var(--accent-cyan);">●</span> Dominant Wing Archetype &amp; Description
                                        </h4>
                                        <p style="font-size: 0.88rem; font-weight: 600; color: var(--accent-cyan); margin: 0 0 4px 0;">${wingKey ? 'Wing ' + wingVal + ' - ' + wingArchetype : 'No Dominant Wing'}</p>
                                        <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                                            ${wingKey ? `Modifies Type ${domType}'s primary motivation with Type ${wingVal}'s key auxiliary traits, embodying "${wingArchetype}".` : 'Operates with balanced wing influences.'}
                                        </p>
                                    </div>

                                    <!-- 3. Core Strengths -->
                                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 16px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px;">
                                            <span style="color: #10b981;">●</span> Core Strengths
                                        </h4>
                                        <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${typeInfo.traits}</p>
                                    </div>

                                    <!-- 4. Growth Directions -->
                                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 16px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px;">
                                            <span style="color: #a855f7;">●</span> Growth Directions
                                        </h4>
                                        <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${typeInfo.growth}</p>
                                    </div>

                                    <!-- 5. Communication Style -->
                                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 16px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0; display: flex; align-items: center; gap: 6px;">
                                            <span style="color: #f59e0b;">●</span> Communication Style
                                        </h4>
                                        <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${typeInfo.comm}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        document.getElementById('data-modal').classList.add('show');
                        lucide.createIcons();
                    } else {
                        showAdminToast(data.error || 'Failed to load report.', 'error');
                    }
                });
        }

        function restartUserSession(userId, userName) {
            if (confirm(`Are you sure you want to restart the assessment session for ${userName}? This will immediately clear their active progress, allowing them to start again from Question 1.`)) {
                showAdminToast(`Restarting session for ${userName}...`, 'info');
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('admin.php?action=restart_user_session', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAdminToast(`Session restarted successfully for ${userName}.`, 'success');
                    } else {
                        showAdminToast(data.error || 'Failed to restart session.', 'error');
                    }
                })
                .catch(() => { showAdminToast('Network connection failed.', 'error'); });
            }
        }

        function showAdminToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = 'admin-toast';
            
            let iconName = 'check-circle';
            let iconColor = '#10B981';
            if (type === 'error') {
                iconName = 'x-circle';
                iconColor = '#EF4444';
            } else if (type === 'info') {
                iconName = 'info';
                iconColor = '#6366F1';
            }
            
            toast.innerHTML = `
                <i data-lucide="${iconName}" style="width:16px; height:16px; color:${iconColor}; flex-shrink:0;"></i>
                <span style="margin-left:8px;">${message}</span>
            `;
            container.appendChild(toast);
            lucide.createIcons();
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.25s cubic-bezier(0.4, 0, 1, 1) forwards';
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 250);
            }, 1000);
        }

        function downloadCSV() {
            showAdminToast('Generating CSV export...', 'info');
            setTimeout(() => { window.location.href = 'admin.php?action=download_csv'; }, 550);
        }

        // --- FEEDBACK MANAGEMENT SCRIPT ---
        let showingAllFeedbacks = false;
        let currentEditingFeedbackId = null;

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateFeedbackTableVisibility() {
            const table = document.getElementById('admin-feedback-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr.feedback-row');
            const noResultsRow = document.getElementById('no-feedback-results-row');
            const searchInput = document.getElementById('feedback-search-input');
            const statusFilter = document.getElementById('feedback-status-filter');
            const viewAllBtn = document.getElementById('view-all-feedback-btn');
            const countBadge = document.getElementById('feedback-count-badge');

            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

            let visibleCount = 0;
            const totalRows = rows.length;

            rows.forEach((row, index) => {
                const id = (row.getAttribute('data-id') || '').toLowerCase();
                const title = (row.getAttribute('data-title') || '').toLowerCase();
                const user = (row.getAttribute('data-user') || '').toLowerCase();
                const email = (row.getAttribute('data-email') || '').toLowerCase();
                const status = (row.getAttribute('data-status') || '').toLowerCase();

                const matchesSearch = !query || id.includes(query) || title.includes(query) || user.includes(query) || email.includes(query);
                const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus) || (selectedStatus === 'submitted' && status === 'open');

                if (matchesSearch && matchesStatus) {
                    if (query.length > 0 || selectedStatus !== 'all') {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        if (showingAllFeedbacks || index < 5) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = ((query.length > 0 || selectedStatus !== 'all') && visibleCount === 0) ? '' : 'none';
            }

            if (viewAllBtn) {
                if (query.length > 0 || selectedStatus !== 'all') {
                    viewAllBtn.style.display = 'none';
                } else {
                    viewAllBtn.style.display = 'inline-flex';
                    if (showingAllFeedbacks) {
                        viewAllBtn.innerHTML = `<i data-lucide="chevron-up" style="width:14px; height:14px;"></i> Show Top 5`;
                    } else {
                        viewAllBtn.innerHTML = `<i data-lucide="list" style="width:14px; height:14px;"></i> View All (${totalRows})`;
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }

            if (countBadge) {
                if (query.length > 0 || selectedStatus !== 'all') {
                    countBadge.textContent = `${visibleCount} Found`;
                } else {
                    countBadge.textContent = showingAllFeedbacks ? `${totalRows} Total` : `Showing ${Math.min(5, totalRows)} of ${totalRows}`;
                }
            }
        }

        function filterFeedbackTable() {
            updateFeedbackTableVisibility();
        }

        function toggleViewAllFeedbacks() {
            showingAllFeedbacks = !showingAllFeedbacks;
            updateFeedbackTableVisibility();
        }

        function openAdminFeedbackModal(feedbackId) {
            showAdminToast(`Loading details for Feedback #${feedbackId}...`, 'info');
            currentEditingFeedbackId = feedbackId;

            fetch(`admin.php?action=get_feedback_detail&id=${feedbackId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const fb = data.feedback;
                        const modal = document.getElementById('admin-feedback-modal');
                        const titleElem = document.getElementById('afb-modal-title');
                        const bodyElem = document.getElementById('afb-modal-body');

                        if (titleElem) titleElem.innerText = `Feedback Details - #${fb.id}`;

                        const createdDate = new Date(fb.created_at || Date.now()).toLocaleString(undefined, {
                            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
                        });
                        const updatedDate = fb.updated_at ? new Date(fb.updated_at).toLocaleString(undefined, {
                            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
                        }) : createdDate;

                        const st = (fb.status || 'Submitted').toLowerCase();
                        let statusClass = 'submitted';
                        if (st.includes('open')) statusClass = 'open';
                        if (st.includes('progress')) statusClass = 'in-progress';
                        if (st.includes('resolve')) statusClass = 'resolved';
                        if (st.includes('close')) statusClass = 'closed';

                        let attachmentHtml = `
                            <div style="background: var(--input-bg); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 12px 16px; font-size: 0.88rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="paperclip" style="width:16px; height:16px;"></i> No attachment uploaded
                            </div>
                        `;

                        if (fb.attachment_path) {
                            const attName = fb.attachment_name || 'Attachment';
                            if (fb.attachment_exists !== false) {
                                attachmentHtml = `
                                    <div style="background: var(--input-bg); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i data-lucide="file-text" style="width: 20px; height: 20px; color: var(--accent-indigo);"></i>
                                            <div>
                                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">${escapeHtml(attName)}</div>
                                                <div style="font-size: 0.78rem; color: var(--text-muted);">Uploaded attachment file</div>
                                            </div>
                                        </div>
                                        <a href="/${fb.attachment_path}" target="_blank" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                            <i data-lucide="download" style="width: 14px; height: 14px;"></i> View / Download
                                        </a>
                                    </div>
                                `;
                            } else {
                                attachmentHtml = `
                                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 10px; padding: 12px 16px; font-size: 0.88rem; color: var(--color-danger); display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="alert-triangle" style="width:16px; height:16px;"></i> Attachment file specified (${escapeHtml(attName)}) but missing from storage server
                                    </div>
                                `;
                            }
                        }

                        bodyElem.innerHTML = `
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--surface-glass-border); border-radius: 12px; padding: 16px;">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">Raised By</span>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(fb.user_name)}</div>
                                    <div style="font-size: 0.82rem; color: var(--text-secondary);">${escapeHtml(fb.user_email)}</div>
                                </div>
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 4px;">Timeline</span>
                                    <div style="font-size: 0.85rem; color: var(--text-primary); font-weight: 600;">Submitted: ${createdDate}</div>
                                    <div style="font-size: 0.80rem; color: var(--text-secondary);">Last Updated: ${updatedDate}</div>
                                </div>
                            </div>

                            <div style="margin-bottom: 18px;">
                                <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Feedback Title</label>
                                <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">${escapeHtml(fb.title)}</div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Description</label>
                                <div style="background: var(--input-bg); border: 1px solid var(--surface-glass-border); border-radius: 10px; padding: 14px 16px; font-size: 0.9rem; color: var(--text-primary); line-height: 1.6; max-height: 180px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;">${escapeHtml(fb.description)}</div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Uploaded Attachment</label>
                                ${attachmentHtml}
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                                <div>
                                    <label for="afb-status-select" style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Update Feedback Status</label>
                                    <select id="afb-status-select" class="cell-select" style="height: 42px;">
                                        <option value="Submitted" ${fb.status === 'Submitted' ? 'selected' : ''}>Submitted</option>
                                        <option value="Open" ${fb.status === 'Open' ? 'selected' : ''}>Open</option>
                                        <option value="In Progress" ${fb.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                                        <option value="Resolved" ${fb.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                                        <option value="Closed" ${fb.status === 'Closed' ? 'selected' : ''}>Closed</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Current Status Badge</label>
                                    <div style="padding: 6px 0;"><span class="badge-status-pill ${statusClass}">${escapeHtml(fb.status || 'Submitted')}</span></div>
                                </div>
                            </div>

                            <div>
                                <label for="afb-admin-notes" style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">Internal Admin Notes &amp; Comments</label>
                                <textarea id="afb-admin-notes" class="form-control" rows="3" placeholder="Add internal diagnostic findings or resolution notes here..." style="font-size: 0.88rem; width: 100%; border-radius: 10px;">${escapeHtml(fb.admin_notes || '')}</textarea>
                            </div>
                        `;

                        modal.classList.add('show');
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    } else {
                        showAdminToast(data.error || 'Failed to fetch feedback details.', 'error');
                    }
                })
                .catch(err => {
                    showAdminToast('Network connection failed.', 'error');
                });
        }

        function closeAdminFeedbackModal() {
            const modal = document.getElementById('admin-feedback-modal');
            if (modal) modal.classList.remove('show');
            currentEditingFeedbackId = null;
        }

        function saveAdminFeedbackChanges() {
            if (!currentEditingFeedbackId) return;

            const statusSelect = document.getElementById('afb-status-select');
            const notesTextarea = document.getElementById('afb-admin-notes');

            const newStatus = statusSelect ? statusSelect.value : 'Submitted';
            const newNotes = notesTextarea ? notesTextarea.value : '';

            showAdminToast(`Saving updates for Feedback #${currentEditingFeedbackId}...`, 'info');

            const formData = new FormData();
            formData.append('feedback_id', currentEditingFeedbackId);
            formData.append('status', newStatus);
            formData.append('admin_notes', newNotes);

            fetch('admin.php?action=update_feedback', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAdminToast(`Feedback #${currentEditingFeedbackId} updated successfully to "${newStatus}"!`, 'success');
                    closeAdminFeedbackModal();

                    // Update row status badge in table
                    const row = document.getElementById(`feedback-row-${currentEditingFeedbackId}`);
                    if (row) {
                        row.setAttribute('data-status', newStatus.toLowerCase());
                        const badge = row.querySelector('.badge-status-pill');
                        if (badge) {
                            badge.textContent = newStatus;
                            const statusClass = newStatus.toLowerCase().replace(' ', '-');
                            badge.className = `badge-status-pill ${statusClass}`;
                        }
                    }
                    updateFeedbackTableVisibility();
                } else {
                    showAdminToast(data.error || 'Failed to update feedback.', 'error');
                }
            })
            .catch(err => {
                showAdminToast('Network connection failed.', 'error');
            });
        }
    </script>
</body>
</html>
