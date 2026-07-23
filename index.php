<?php
require_once __DIR__ . '/config.php';

/**
 * Sends an email using Brevo (formerly Sendinblue) Transactional API.
 */
if (!function_exists('send_email_via_brevo')) {
    function send_email_via_brevo($to_email, $to_name, $subject, $html_content) {
        $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
        if (empty($api_key) || strpos($api_key, 'xkeysib-mock') === 0 || strlen($api_key) < 20) {
            throw new Exception("Brevo API Key is not configured in config.php. Please set a valid API key (must start with xkeysib-).");
        }
        $sender_email = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@enneadash.voice';
        
        $url = 'https://api.brevo.com/v3/smtp/email';
        $data = [
            'sender' => [
                'name' => 'EnneaDash Voice',
                'email' => $sender_email
            ],
            'to' => [
                [
                    'email' => $to_email,
                    'name' => $to_name
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $html_content
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $api_key,
            'content-type: application/json'
        ]);
        
        $response = curl_exec($ch);
        if ($response === false) {
            $error_msg = curl_error($ch);
            throw new Exception("cURL connection error: " . $error_msg);
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($http_code >= 200 && $http_code < 300) {
            return true;
        } else {
            $error_data = json_decode($response, true);
            $error_msg = isset($error_data['message']) ? $error_data['message'] : ($response ? $response : 'Unknown error');
            throw new Exception("Brevo API returned error (HTTP $http_code): $error_msg");
        }
    }
}

/**
 * Analyze text input and return score 1-5 (or 0 if empty)
 */
function analyze_text_input($text) {
    $text = trim($text);
    if ($text === '') {
        return 0.0;
    }
    // Count words
    $words = preg_split('/\s+/', $text);
    $word_count = count(array_filter($words));
    if ($word_count === 0) return 0.0;
    
    // Detailed analysis based on length
    if ($word_count > 15) {
        return 5.0;
    } elseif ($word_count > 8) {
        return 4.0;
    } elseif ($word_count > 3) {
        return 3.0;
    } else {
        return 2.0; // Minimal response
    }
}

/**
 * Analyze voice input transcript and return score 1-5 (or 0 if empty)
 */
function analyze_voice_input($voice_text) {
    return analyze_text_input($voice_text);
}

/**
 * Validate password policy: exactly 6 characters, at least one capital letter, one number, and one special character.
 */
function validate_password_policy($password) {
    if (strlen($password) !== 6) {
        return "Password must be exactly 6 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one capital letter (A-Z).";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one numeric digit (0-9).";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must contain at least one special character.";
    }
    return null;
}

// Helper function to ensure force_password_change schema exists
function ensure_user_auth_schema($pdo) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'force_password_change'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0");
        }
    } catch (Throwable $e) {}
}

// Helper function to ensure user_feedbacks schema exists
function ensure_feedback_schema($pdo) {
    try {
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
    } catch (Throwable $e) {}
}

// Route request
if (!function_exists('calculateAndSaveReport')) {
    function calculateAndSaveReport($pdo, $session_id, $user_id) {
        // Ensure required database columns exist
        try {
            $pdo->exec("ALTER TABLE exam_sessions ADD COLUMN age_group VARCHAR(20) DEFAULT NULL");
        } catch (Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE enneagram_reports ADD COLUMN is_dominant_tied TINYINT(1) DEFAULT 0");
        } catch (Throwable $e) {}

        // Fetch age_group directly from exam_sessions for this assessment session
        $s_stmt = $pdo->prepare("SELECT age_group FROM exam_sessions WHERE id = ?");
        $s_stmt->execute([$session_id]);
        $sess = $s_stmt->fetch();
        $age_group = ($sess && !empty($sess['age_group'])) ? $sess['age_group'] : null;

        // Fallback to user profile if session record does not have age_group set
        if (empty($age_group)) {
            $p_stmt = $pdo->prepare("SELECT age_group FROM user_profiles WHERE user_id = ?");
            $p_stmt->execute([$user_id]);
            $profile = $p_stmt->fetch();
            $age_group = ($profile && !empty($profile['age_group'])) ? $profile['age_group'] : '18-25'; // Fallback

            // Backfill session record
            $up_stmt = $pdo->prepare("UPDATE exam_sessions SET age_group = ? WHERE id = ?");
            $up_stmt->execute([$age_group, $session_id]);
        }

        // Fetch questions for this age group to map target types
        $q_stmt = $pdo->prepare("SELECT id, target_type FROM questions WHERE age_group = ?");
        $q_stmt->execute([$age_group]);
        $questions = [];
        foreach ($q_stmt->fetchAll() as $q) {
            $questions[$q['id']] = $q['target_type'];
        }
        
        // Fetch all answers for this session
        $a_stmt = $pdo->prepare("SELECT question_id, answer_text, input_mode FROM exam_answers WHERE session_id = ?");
        $a_stmt->execute([$session_id]);
        $answers = $a_stmt->fetchAll();
        
        if (empty($answers)) {
            return null;
        }

        // Official Enneagram Multi-Modal Weighted Scoring Algorithm
        $type_weighted_sums = array_fill(1, 9, 0.0);
        $type_question_counts = array_fill(1, 9, 0);
        $type_expression_depths = array_fill(1, 9, 0.0);
        
        foreach ($answers as $ans) {
            $q_id = $ans['question_id'];
            $type = $questions[$q_id] ?? null;
            if (!$type) {
                error_log("Warning: Answer for question_id {$q_id} in session {$session_id} could not be mapped to a target question type (age_group: {$age_group}).");
                continue;
            }

            $decoded = json_decode($ans['answer_text'], true);
            
            $scale_val = isset($decoded['score']) ? intval($decoded['score']) : 0;
            $text_input = isset($decoded['text_input']) ? trim($decoded['text_input']) : '';
            $voice_input = isset($decoded['voice_input']) ? trim($decoded['voice_input']) : '';
            
            // Legacy fallback
            if ($text_input === '' && $voice_input === '' && isset($decoded['reason'])) {
                $legacy_reason = trim($decoded['reason']);
                if (($ans['input_mode'] ?? 'text') === 'voice') {
                    $voice_input = $legacy_reason;
                } else {
                    $text_input = $legacy_reason;
                }
            }
            
            // Evaluate text reasoning score (continuous float 1.0 to 5.0)
            $text_score = 0.0;
            if ($text_input !== '') {
                $words = count(array_filter(preg_split('/\s+/', $text_input)));
                $chars = strlen($text_input);
                $text_score = min(5.0, 1.0 + ($words * 0.25) + ($chars * 0.005));
            }
            
            // Evaluate voice transcript score (continuous float 1.0 to 5.0)
            $voice_score = 0.0;
            if ($voice_input !== '') {
                $words = count(array_filter(preg_split('/\s+/', $voice_input)));
                $chars = strlen($voice_input);
                $voice_score = min(5.0, 1.0 + ($words * 0.3) + ($chars * 0.006));
            }

            // Multi-modal weighted combination
            if ($scale_val > 0 && $text_score > 0 && $voice_score > 0) {
                $q_score = (0.50 * $scale_val) + (0.25 * $text_score) + (0.25 * $voice_score);
            } else if ($scale_val > 0 && ($text_score > 0 || $voice_score > 0)) {
                $qual = max($text_score, $voice_score);
                $q_score = (0.65 * $scale_val) + (0.35 * $qual);
            } else if ($scale_val > 0) {
                $q_score = floatval($scale_val);
            } else if ($text_score > 0 || $voice_score > 0) {
                $q_score = max($text_score, $voice_score);
            } else {
                $q_score = 0.0;
            }

            $type_weighted_sums[$type] += $q_score;
            $type_question_counts[$type]++;
            
            $total_text = trim($text_input . ' ' . $voice_input);
            $type_expression_depths[$type] += (strlen($total_text) * 0.0001);
        }

        // Calculate Average Ratings and Weighted Scores (10-point scale)
        // Note: expression depth is NOT added to the persisted/display score
        $display_scores = array_fill(1, 9, 0.0);
        $raw_avg_scores = array_fill(1, 9, 0.0);
        
        for ($t = 1; $t <= 9; $t++) {
            $count = $type_question_counts[$t];
            if ($count > 0) {
                $avg = $type_weighted_sums[$t] / $count;
                $raw_avg_scores[$t] = $avg * 2.0;
                $display_scores[$t] = round($avg * 2.0, 2);
            }
        }

        // Rank all 9 types:
        // Primary sort key: display_scores
        // Secondary sort key: type_expression_depths (used ONLY for ranking tie-breaking)
        $types = range(1, 9);
        usort($types, function($a, $b) use ($display_scores, $raw_avg_scores, $type_expression_depths) {
            if ($display_scores[$a] != $display_scores[$b]) {
                return ($display_scores[$a] > $display_scores[$b]) ? -1 : 1;
            }
            if ($type_expression_depths[$a] != $type_expression_depths[$b]) {
                return ($type_expression_depths[$a] > $type_expression_depths[$b]) ? -1 : 1;
            }
            return $a <=> $b;
        });
        $dominant_type = $types[0];

        // Detect genuine tie for dominant type (comparing display_scores)
        $is_dominant_tied = false;
        $dominant_score = $display_scores[$dominant_type];
        if ($dominant_score > 0) {
            foreach ($display_scores as $t => $score) {
                if ($t !== $dominant_type && $score == $dominant_score) {
                    $is_dominant_tied = true;
                    break;
                }
            }
        }

        // Calculate adjacent wings for dominant type
        $wing1 = ($dominant_type == 1) ? 9 : ($dominant_type - 1);
        $wing2 = ($dominant_type == 9) ? 1 : ($dominant_type + 1);

        $score_w1 = $display_scores[$wing1] ?? 0;
        $score_w2 = $display_scores[$wing2] ?? 0;

        // Dominant wing is the adjacent type with the higher weighted score
        $w1 = ($score_w1 >= $score_w2) ? $wing1 : $wing2;
        $w2 = ($w1 == $wing1) ? $wing2 : $wing1;

        // Final scores to persist in DB (mapped 1..9)
        $final_scores = [];
        for ($t = 1; $t <= 9; $t++) {
            $final_scores[$t] = $display_scores[$t];
        }

        // Save Enneagram report with is_dominant_tied flag
        $stmt = $pdo->prepare("INSERT INTO enneagram_reports (session_id, user_id, enneagram_type, wing_1, wing_2, raw_scores, is_dominant_tied, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $session_id,
            $user_id,
            $dominant_type,
            $w1,
            $w2,
            json_encode($final_scores),
            $is_dominant_tied ? 1 : 0
        ]);
        $report_id = $pdo->lastInsertId();
        
        // Update session status to completed
        $stmt = $pdo->prepare("UPDATE exam_sessions SET status = 'completed' WHERE id = ?");
        $stmt->execute([$session_id]);

        return $report_id;
    }
}

if (!function_exists('getLatestOrGenerateReportForUser')) {
    function getLatestOrGenerateReportForUser($pdo, $session_id, $user_id) {
        if ($session_id) {
            $repStmt = $pdo->prepare("SELECT id FROM enneagram_reports WHERE session_id = ? ORDER BY id DESC LIMIT 1");
            $repStmt->execute([$session_id]);
            $existing_rep = $repStmt->fetch();
            if ($existing_rep) {
                return (int)$existing_rep['id'];
            }

            // Try generating report from existing answers if session had answers
            $rep_id = calculateAndSaveReport($pdo, $session_id, $user_id);
            if ($rep_id) {
                return (int)$rep_id;
            }
        }
        
        // Fallback to latest report of the user
        $userRepStmt = $pdo->prepare("SELECT id FROM enneagram_reports WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $userRepStmt->execute([$user_id]);
        $user_rep = $userRepStmt->fetch();
        if ($user_rep) {
            return (int)$user_rep['id'];
        }
        
        return null;
    }
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '/';

// Admin Dashboard Route
if ($path === '/admin' || $path === '/admin.php') {
    require_once __DIR__ . '/admin.php';
    exit;
}

// API Router
if (strpos($path, '/api/') === 0) {
    header('Content-Type: application/json');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    try {
        $pdo = get_db_connection();
        ensure_user_auth_schema($pdo);
        ensure_feedback_schema($pdo);
        
        // --- AUTH ROUTES ---
        if ($path === '/api/auth/me') {
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT u.id, u.email_id, u.force_password_change, p.name, p.age_group, p.gender 
                                       FROM users u 
                                       LEFT JOIN user_profiles p ON u.id = p.user_id 
                                       WHERE u.id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                if ($user) {
                    $user['force_password_change'] = (bool)($user['force_password_change'] ?? 0);
                }
                
                // Check if they have an active session
                $session_stmt = $pdo->prepare("SELECT id, status FROM exam_sessions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $session_stmt->execute([$_SESSION['user_id']]);
                $active_session = $session_stmt->fetch();
                
                echo json_encode([
                    'logged_in' => true,
                    'user' => $user,
                    'active_session' => $active_session
                ]);
            } else {
                echo json_encode(['logged_in' => false]);
            }
            exit;
        }
        
        if ($path === '/api/auth/register' && $method === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $age_group = $_POST['age_group'] ?? '';
            $gender = trim($_POST['gender'] ?? '');
            
            if (empty($email) || empty($password) || empty($name) || empty($age_group) || empty($gender)) {
                http_response_code(400);
                echo json_encode(['error' => 'All registration fields are required.']);
                exit;
            }
            
            $passwordErr = validate_password_policy($password);
            if ($passwordErr) {
                http_response_code(400);
                echo json_encode(['error' => $passwordErr]);
                exit;
            }
            
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_id = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Username/Email already exists.']);
                exit;
            }
            
            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email_id, password_hash, force_password_change, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())");
            $stmt->execute([$email, $hash]);
            $user_id = $pdo->lastInsertId();
            
            // Insert profile
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, name, age_group, gender, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$user_id, $name, $age_group, $gender]);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            
            echo json_encode(['success' => true, 'message' => 'Registration successful.']);
            exit;
        }
        
        if ($path === '/api/auth/login' && $method === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required.']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT u.id, u.password_hash, u.force_password_change, p.name, p.assessment_override 
                                   FROM users u 
                                   LEFT JOIN user_profiles p ON u.id = p.user_id 
                                   WHERE u.email_id = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $access_override = $user['assessment_override'] ?? 'full_access';
                if ($access_override === 'block_access' || $access_override === 'blocked') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Your assessment access has been blocked by an administrator.']);
                    exit;
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'] ?? 'User';
                $forceChange = (bool)($user['force_password_change'] ?? 0);
                $_SESSION['force_password_change'] = $forceChange;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful.',
                    'force_change' => $forceChange
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid email or password.']);
            }
            exit;
        }

        if ($path === '/api/auth/forgot-password' && $method === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Please enter your registered email address.']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT u.id, u.email_id, p.name FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.email_id = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                http_response_code(400);
                echo json_encode(['error' => 'No registered user account found with this email address.']);
                exit;
            }
            
            $tempPassword = 'ed@123';
            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 1, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$hash, $user['id']]);
            
            $toEmail = $user['email_id'];
            $toName = !empty($user['name']) ? $user['name'] : 'Participant';
            $subject = 'EnneaDash Voice - Temporary Password Reset';
            $htmlBody = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; background-color: #0f172a; color: #f8fafc; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <h2 style="color: #c084fc; margin-top: 0;">Password Reset Notification</h2>
                <p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                <p>Your password for EnneaDash Voice has been temporarily reset to default credentials:</p>
                <div style="background-color: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); padding: 16px; border-radius: 12px; text-align: center; margin: 20px 0;">
                    <span style="font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #38bdf8;">' . $tempPassword . '</span>
                </div>
            ';
            
            try {
                send_email_via_brevo($toEmail, $toName, $subject, $htmlBody);
            } catch (Throwable $e) {
                // Email delivery attempt completed
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Password temporarily reset to "ed@123" and sent via Brevo email. Please sign in with ed@123 to change your password.'
            ]);
            exit;
        }

        if ($path === '/api/auth/change-password' && $method === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized. Please login first.']);
                exit;
            }
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                http_response_code(400);
                echo json_encode(['error' => 'All password fields are required.']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                echo json_encode(['error' => 'New password and confirm password do not match.']);
                exit;
            }
            
            if ($newPassword === 'ed@123') {
                http_response_code(400);
                echo json_encode(['error' => 'You cannot reuse the default temporary password. Please choose a new unique password.']);
                exit;
            }
            
            $passwordErr = validate_password_policy($newPassword);
            if ($passwordErr) {
                http_response_code(400);
                echo json_encode(['error' => $passwordErr]);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect.']);
                exit;
            }
            
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 0, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$newHash, $_SESSION['user_id']]);
            
            $_SESSION['force_password_change'] = false;
            
            echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
            exit;
        }
        
        if ($path === '/api/auth/logout' && $method === 'POST') {
            session_destroy();
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($path === '/api/exam/config') {
            $mode = 'hybrid';
            $user_id = $_SESSION['user_id'] ?? null;
            try {
                if ($user_id) {
                    $stmt = $pdo->prepare("SELECT assessment_override FROM user_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user_override = $stmt->fetchColumn();
                    
                    if ($user_override === 'block_access' || $user_override === 'blocked') {
                        http_response_code(403);
                        echo json_encode(['error' => 'Your assessment access has been blocked by an administrator.']);
                        exit;
                    } else if ($user_override === 'voice_only') {
                        $mode = 'voice';
                    } else if ($user_override === 'typing_only') {
                        $mode = 'typing';
                    } else if ($user_override === 'scale_only') {
                        $mode = 'scale';
                    } else if ($user_override === 'full_access') {
                        $mode = 'hybrid';
                    } else {
                        $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'active_mode'");
                        $stmt->execute();
                        $db_mode = $stmt->fetchColumn();
                        if ($db_mode) $mode = $db_mode;
                    }
                } else {
                    $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'active_mode'");
                    $stmt->execute();
                    $db_mode = $stmt->fetchColumn();
                    if ($db_mode) $mode = $db_mode;
                }
            } catch (Throwable $e) {}
            echo json_encode(['active_mode' => $mode]);
            exit;
        }
        
        // --- SESSION & CONSENT ROUTES (Requires Auth) ---
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Please login.']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Enforce Assessment Access Override block if set for user
        $stmt_override = $pdo->prepare("SELECT assessment_override FROM user_profiles WHERE user_id = ?");
        $stmt_override->execute([$user_id]);
        $user_access_override = $stmt_override->fetchColumn();
        if ($user_access_override === 'block_access' || $user_access_override === 'blocked') {
            http_response_code(403);
            echo json_encode(['error' => 'Your assessment access has been blocked by an administrator.']);
            exit;
        }
        
        if ($path === '/api/exam/consent' && $method === 'POST') {
            $consent = isset($_POST['consent']) && $_POST['consent'] === '1' ? 1 : 0;
            if (!$consent) {
                http_response_code(400);
                echo json_encode(['error' => 'GDPR Consent is required to start the assessment.']);
                exit;
            }
            
            // Check if there is an in-progress session
            $stmt = $pdo->prepare("SELECT id FROM exam_sessions WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $session_id = $existing['id'];
            } else {
                // Fetch age_group from user profile at session creation time
                $p_stmt = $pdo->prepare("SELECT age_group FROM user_profiles WHERE user_id = ?");
                $p_stmt->execute([$user_id]);
                $profile = $p_stmt->fetch();
                $age_group = $profile ? $profile['age_group'] : '18-25'; // Fallback

                // Create a new session with age_group
                try {
                    $pdo->exec("ALTER TABLE exam_sessions ADD COLUMN age_group VARCHAR(20) DEFAULT NULL");
                } catch (Throwable $e) {}
                $stmt = $pdo->prepare("INSERT INTO exam_sessions (user_id, age_group, gdpr_consent_given, consent_timestamp, status, created_at) VALUES (?, ?, 1, NOW(), 'in_progress', NOW())");
                $stmt->execute([$user_id, $age_group]);
                $session_id = $pdo->lastInsertId();
            }
            
            echo json_encode(['success' => true, 'session_id' => $session_id]);
            exit;
        }
        
        if ($path === '/api/exam/questions') {
            // Fetch active session to get age_group from exam_sessions
            $session_stmt = $pdo->prepare("SELECT id, age_group FROM exam_sessions WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
            $session_stmt->execute([$user_id]);
            $active_sess = $session_stmt->fetch();

            $age_group = ($active_sess && !empty($active_sess['age_group'])) ? $active_sess['age_group'] : null;
            if (empty($age_group)) {
                $stmt = $pdo->prepare("SELECT age_group FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $profile = $stmt->fetch();
                $age_group = $profile ? $profile['age_group'] : '18-25'; // Fallback
            }
            
            $stmt = $pdo->prepare("SELECT id, prompt_text, target_type FROM questions WHERE age_group = ? ORDER BY id ASC");
            $stmt->execute([$age_group]);
            $questions = $stmt->fetchAll();

            // Fetch existing answers for active session if any
            $saved_answers = new stdClass();
            if ($active_sess) {
                $a_stmt = $pdo->prepare("SELECT question_id, answer_text FROM exam_answers WHERE session_id = ?");
                $a_stmt->execute([$active_sess['id']]);
                $saved_answers = [];
                foreach ($a_stmt->fetchAll() as $ans_row) {
                    $decoded = json_decode($ans_row['answer_text'], true);
                    $saved_answers[$ans_row['question_id']] = [
                        'score' => isset($decoded['score']) ? intval($decoded['score']) : 0,
                        'text_input' => isset($decoded['text_input']) ? $decoded['text_input'] : '',
                        'voice_input' => isset($decoded['voice_input']) ? $decoded['voice_input'] : ''
                    ];
                }
            }

            echo json_encode(['questions' => $questions, 'saved_answers' => $saved_answers]);
            exit;
        }
        
        if ($path === '/api/exam/answer' && $method === 'POST') {
            $session_id = $_POST['session_id'] ?? null;
            $question_id = $_POST['question_id'] ?? null;
            
            $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
            $text_input = trim($_POST['text_input'] ?? '');
            $voice_input = trim($_POST['voice_input'] ?? '');
            $input_mode = $_POST['input_mode'] ?? 'text'; // 'text' or 'voice'
            
            if (!$session_id || !$question_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Session ID and Question ID are required.']);
                exit;
            }
            
            $answer_data = json_encode([
                'score' => $score,
                'text_input' => $text_input,
                'voice_input' => $voice_input
            ]);
            
            // Check if answer already exists
            $stmt = $pdo->prepare("SELECT id FROM exam_answers WHERE session_id = ? AND question_id = ?");
            $stmt->execute([$session_id, $question_id]);
            $existing_ans = $stmt->fetch();
            
            if ($existing_ans) {
                $stmt = $pdo->prepare("UPDATE exam_answers SET answer_text = ?, input_mode = ?, created_at = NOW() WHERE id = ?");
                $stmt->execute([$answer_data, $input_mode, $existing_ans['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO exam_answers (session_id, question_id, answer_text, input_mode, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$session_id, $question_id, $answer_data, $input_mode]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($path === '/api/exam/submit' && $method === 'POST') {
            $session_id = $_POST['session_id'] ?? null;
            if (!$session_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Session ID is required.']);
                exit;
            }
            
            // Verify session
            $stmt = $pdo->prepare("SELECT id, status, age_group FROM exam_sessions WHERE id = ? AND user_id = ?");
            $stmt->execute([$session_id, $user_id]);
            $session_rec = $stmt->fetch();
            if (!$session_rec || $session_rec['status'] !== 'in_progress') {
                $report_id = getLatestOrGenerateReportForUser($pdo, $session_id, $user_id);
                http_response_code(403);
                echo json_encode([
                    'error' => 'Invalid or inactive session.',
                    'session_inactive' => true,
                    'report_id' => $report_id
                ]);
                exit;
            }
            
            // Read age_group from exam_sessions
            $age_group = (!empty($session_rec['age_group'])) ? $session_rec['age_group'] : null;
            if (empty($age_group)) {
                $stmt = $pdo->prepare("SELECT age_group FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $profile = $stmt->fetch();
                $age_group = $profile ? $profile['age_group'] : '18-25'; // Fallback
            }

            // Fetch questions for this age group to map target types
            $q_stmt = $pdo->prepare("SELECT id, target_type FROM questions WHERE age_group = ?");
            $q_stmt->execute([$age_group]);
            $questions = [];
            foreach ($q_stmt->fetchAll() as $q) {
                $questions[$q['id']] = $q['target_type'];
            }
            
            // Fetch all answers for this session
            $a_stmt = $pdo->prepare("SELECT question_id, answer_text FROM exam_answers WHERE session_id = ?");
            $a_stmt->execute([$session_id]);
            $answers = $a_stmt->fetchAll();
            
            // Ensure all questions for this age group are answered
            if (count($answers) < count($questions)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Assessment incomplete.',
                    'answered' => count($answers),
                    'total' => count($questions)
                ]);
                exit;
            }
            
            $report_id = calculateAndSaveReport($pdo, $session_id, $user_id);
            
            echo json_encode([
                'success' => true,
                'report_id' => $report_id
            ]);
            exit;
        }
        
        if ($path === '/api/exam/reports') {
            $stmt = $pdo->prepare("SELECT r.id, r.enneagram_type, r.wing_1, r.wing_2, r.raw_scores, r.is_dominant_tied, r.created_at, s.id AS session_id 
                                   FROM enneagram_reports r
                                   JOIN exam_sessions s ON r.session_id = s.id
                                   WHERE r.user_id = ?
                                   ORDER BY r.created_at DESC");
            $stmt->execute([$user_id]);
            $reports = $stmt->fetchAll();
            
            // Decode scores and format flags
            foreach ($reports as &$rep) {
                $rep['raw_scores'] = json_decode($rep['raw_scores'], true);
                $rep['is_dominant_tied'] = (bool)($rep['is_dominant_tied'] ?? false);
            }
            
            echo json_encode(['reports' => $reports]);
            exit;
        }

        if ($path === '/api/exam/send_report_email' && $method === 'POST') {
            $report_id = $_POST['report_id'] ?? null;
            $email = trim($_POST['email'] ?? '');
            
            if (!$report_id || empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Report ID and email address are required.']);
                exit;
            }
            
            // Verify report belongs to user
            $stmt = $pdo->prepare("SELECT r.id, r.session_id, r.enneagram_type, r.wing_1, r.wing_2, r.raw_scores, r.created_at, p.name 
                                   FROM enneagram_reports r
                                   JOIN user_profiles p ON r.user_id = p.user_id
                                   WHERE r.id = ? AND r.user_id = ?");
            $stmt->execute([$report_id, $user_id]);
            $report = $stmt->fetch();
            
            if (!$report) {
                http_response_code(403);
                echo json_encode(['error' => 'Report not found or access denied.']);
                exit;
            }
            
            $domType = intval($report['enneagram_type']);
            $wing1 = intval($report['wing_1']);
            $wing2 = intval($report['wing_2']);
            $raw_scores = json_decode($report['raw_scores'], true);
            $user_name = htmlspecialchars($report['name']);
            
            // Full psychological mappings
            $enneagramTypes = [
                1 => [
                    "name" => "The Perfectionist",
                    "core_orientation" => "Doing the right thing, maintaining integrity and high standards.",
                    "description" => "Perfectionists are rational, idealistic, principled, orderly, and self-controlled. Striving for perfection, they can be critical of themselves and others, always looking for improvement.",
                    "key_traits" => "Ethical, organized, structured, conscientious, self-correcting.",
                    "key_drivers" => "Being objective, accurate, improving oneself, and living with integrity.",
                    "biggest_fear" => "Being corrupt, flawed, evil, or physically/morally defective.",
                    "core_values" => "Integrity, excellence, responsibility, truthfulness, order.",
                    "decision_making_style" => "Structured and rational, relying on rules, ethical guidelines, and analytical correctness.",
                    "stress_reactions" => "Becomes moody, critical, and resentful under pressure (moves to Type 4).",
                    "security_triggers" => "Becomes more spontaneous, relaxed, and creative when safe (moves to Type 7).",
                    "core_fear" => "Being bad, corrupt, or wrong.",
                    "core_desire" => "To be good, to have integrity, and to be balanced.",
                    "core_weakness" => "Anger (resentment that is constantly repressed into self-control).",
                    "soul_message" => "You are good as you are.",
                    "growth_arrow_desc" => "In integration (growth), the Perfectionist moves towards Type 7, embracing spontaneity, joy, and lightheartedness.",
                    "stress_arrow_desc" => "In disintegration (stress), the Perfectionist moves towards Type 4, experiencing feelings of alienation, self-pity, and moodiness.",
                    "growth_action" => "Practice self-compassion and learn to accept imperfections as valuable parts of human growth.",
                    "relationship_action" => "Avoid holding partners to impossible standards; express appreciation for their efforts.",
                    "career_action" => "Delegate tasks confidently and avoid micromanaging project details.",
                    "stress_action" => "Take deep breaths, allow yourself to play, and schedule guilt-free downtime weekly.",
                    "daily_habit" => "Consciously pause once a day to notice something that is perfectly fine just as it is."
                ],
                2 => [
                    "name" => "The Caregiver",
                    "core_orientation" => "Expressing warmth, offering help, and cultivating affection.",
                    "description" => "Caregivers are demonstrative, generous, people-pleasing, and possessive. They sincerely want to feel loved, useful, and appreciated, occasionally neglecting their own boundaries.",
                    "key_traits" => "Empathetic, nurturing, warm, supportive, altruistic.",
                    "key_drivers" => "Connecting with others, feeling needed, expressing affection, and defending the vulnerable.",
                    "biggest_fear" => "Being unwanted, unworthy of love, or completely discarded.",
                    "core_values" => "Unconditional love, generosity, relationships, service, compassion.",
                    "decision_making_style" => "Relationship-centric, prioritizing the emotional impacts and needs of classmates or colleagues.",
                    "stress_reactions" => "Becomes aggressive, demanding, and overly critical under pressure (moves to Type 8).",
                    "security_triggers" => "Becomes self-reflective, creative, and introspective when secure (moves to Type 4).",
                    "core_fear" => "Being unloved or unwanted for who they are.",
                    "core_desire" => "To feel loved and appreciated.",
                    "core_weakness" => "Pride (denying their own needs while over-emphasizing their helpfulness to others).",
                    "soul_message" => "You are wanted and worthy of love.",
                    "growth_arrow_desc" => "In growth, the Helper/Caregiver integrates towards Type 4, developing healthy self-care, creative expression, and authentic feelings.",
                    "stress_arrow_desc" => "In stress, the Helper/Caregiver disintegrates towards Type 8, becoming controlling, confrontational, and demanding.",
                    "growth_action" => "Set clear boundaries and practice saying 'no' when you are emotionally exhausted.",
                    "relationship_action" => "Express your personal needs directly instead of expecting others to read your mind.",
                    "career_action" => "Focus on your assigned job scope instead of taking on others' workloads out of obligation.",
                    "stress_action" => "Step back, enjoy moments of isolation, and recharge through introspective creative activities.",
                    "daily_habit" => "Write down three personal needs you have today and meet at least one of them."
                ],
                3 => [
                    "name" => "The Performer",
                    "core_orientation" => "Striving for success, outstanding achievements, and efficiency.",
                    "description" => "Performers (or Achievers) are adaptable, ambitious, driven, and highly image-conscious. They value productivity, competency, and achieving goals that bring validation.",
                    "key_traits" => "Goal-oriented, self-assured, efficient, energetic, charismatic.",
                    "key_drivers" => "Being admired, distinguishing themselves, earning prestige, and avoiding failure.",
                    "biggest_fear" => "Being worthless, incompetent, ineffective, or a failure.",
                    "core_values" => "Success, productivity, distinction, competence, professional excellence.",
                    "decision_making_style" => "Pragmatic, logical, and fast-paced, focusing entirely on execution and results.",
                    "stress_reactions" => "Becomes disengaged, passive-aggressive, or sluggish under stress (moves to Type 9).",
                    "security_triggers" => "Becomes cooperative, loyal, and community-minded when safe (moves to Type 6).",
                    "core_fear" => "Being worthless or having no inherent value.",
                    "core_desire" => "To feel valuable, successful, and respected.",
                    "core_weakness" => "Deceit (crafting a successful image rather than showing their authentic self).",
                    "soul_message" => "You are valued for who you are, not what you achieve.",
                    "growth_arrow_desc" => "In growth, the Achiever integrates towards Type 6, becoming more cooperative, loyal, and committed to group well-being.",
                    "stress_arrow_desc" => "In stress, the Achiever disintegrates towards Type 9, shutting down and becoming lethargic or directionless.",
                    "growth_action" => "Value relationships and teamwork over individual metrics or social status.",
                    "relationship_action" => "Share your failures and fears with trusted loved ones to cultivate authenticity.",
                    "career_action" => "Balance hard work with strategic pauses; allow collaborators to take the lead occasionally.",
                    "stress_action" => "Recognize when you are running on empty; disconnect from devices and sleep.",
                    "daily_habit" => "Spend ten minutes reflecting on your day without measuring your productivity."
                ],
                4 => [
                    "name" => "The Individualist",
                    "core_orientation" => "Expressing authentic identity, expressing depth, and appreciating aesthetics.",
                    "description" => "Individualists are expressive, dramatic, self-absorbed, and temperamental. They value authenticity and unique creative expression, seeking meaning in all aspects of life.",
                    "key_traits" => "Intuitive, authentic, sensitive, expressive, introspective.",
                    "key_drivers" => "Creating beauty, understanding deep emotions, staying true to oneself, and honoring feelings.",
                    "biggest_fear" => "Having no unique identity or personal significance.",
                    "core_values" => "Authenticity, aesthetic beauty, emotional depth, true individuality, self-expression.",
                    "decision_making_style" => "Intuitive and emotional, strongly guided by how choices align with internal values.",
                    "stress_reactions" => "Becomes clingy, dependent, and overly people-pleasing under pressure (moves to Type 2).",
                    "security_triggers" => "Becomes objective, organized, and active when safe (moves to Type 1).",
                    "core_fear" => "Having no identity or significance.",
                    "core_desire" => "To cultivate a unique identity and find significance.",
                    "core_weakness" => "Envy (feeling that everyone else possesses qualities they lack).",
                    "soul_message" => "You are seen and appreciated for your unique beauty.",
                    "growth_arrow_desc" => "In growth, the Individualist integrates towards Type 1, translating feelings into objective action, discipline, and order.",
                    "stress_arrow_desc" => "In stress, the Individualist disintegrates towards Type 2, seeking validation and becoming overly dependent on others.",
                    "growth_action" => "Build healthy routines and structures to ground your complex emotional world.",
                    "relationship_action" => "Avoid getting caught in cycles of pull-and-push dynamics; appreciate stable, quiet affection.",
                    "career_action" => "Commit to completing projects even when your creative inspiration temporarily fades.",
                    "stress_action" => "Channel intense emotions into structured journaling, exercising, or volunteering.",
                    "daily_habit" => "Focus on active tasks and execute one objective chore first thing each morning."
                ],
                5 => [
                    "name" => "The Investigator",
                    "core_orientation" => "Acquiring knowledge, understanding mechanisms, and protecting energy.",
                    "description" => "Investigators are perceptive, innovative, secretive, and detached. They specialize in deep analysis, requiring quiet independence and mental clarity to recharge.",
                    "key_traits" => "Analytical, insightful, independent, private, conceptual.",
                    "key_drivers" => "Obtaining mastery, processing facts, maintaining autonomy, and escaping emotional noise.",
                    "biggest_fear" => "Being overwhelmed, helpless, incapable, or ignorant.",
                    "core_values" => "Mastery, rationality, independence, deep knowledge, clarity.",
                    "decision_making_style" => "Highly objective, data-driven, and systematic, minimizing emotional interference.",
                    "stress_reactions" => "Becomes hyperactive, distracted, and scattered under stress (moves to Type 7).",
                    "security_triggers" => "Becomes self-assured, assertive, and physically active when safe (moves to Type 8).",
                    "core_fear" => "Being useless, helpless, or incapable.",
                    "core_desire" => "To be capable, competent, and fully knowledgeable.",
                    "core_weakness" => "Avarice (hoarding info, time, and emotional energy to avoid dependency).",
                    "soul_message" => "Your presence is capable and welcome in this world.",
                    "growth_arrow_desc" => "In growth, the Investigator integrates towards Type 8, stepping into leadership and assertive, confident physical action.",
                    "stress_arrow_desc" => "In stress, the Investigator disintegrates towards Type 7, escaping into theory, distraction, or frantic mental rabbit holes.",
                    "growth_action" => "Share your thoughts early and step out of isolation to collaborate in physical groups.",
                    "relationship_action" => "Practice sharing your emotional states directly rather than withdrawing into protective silence.",
                    "career_action" => "Trust your competence and launch projects before you feel 100% prepared.",
                    "stress_action" => "Engage your body through physical exercise to pull energy down from your head.",
                    "daily_habit" => "Have a brief, casual conversation with someone about something unrelated to work."
                ],
                6 => [
                    "name" => "The Loyalist",
                    "core_orientation" => "Ensuring safety, maintaining trust, and building secure alliances.",
                    "description" => "Loyalists are engaging, responsible, anxious, and suspicious. They seek stable guidance, support systems, and consistency to alleviate underlying anxiety.",
                    "key_traits" => "Reliable, committed, alert, trustworthy, collaborative.",
                    "key_drivers" => "Belonging to a trusted group, anticipating hazards, obtaining safety, and defending policies.",
                    "biggest_fear" => "Being without support, guidance, or security; being abandoned.",
                    "core_values" => "Trustworthiness, security, community loyalty, preparation, responsibility.",
                    "decision_making_style" => "Collaborative and risk-averse, consulting trust systems and planning contingencies.",
                    "stress_reactions" => "Becomes competitive, image-conscious, and workaholic under stress (moves to Type 3).",
                    "security_triggers" => "Becomes relaxed, optimistic, and experimental when safe (moves to Type 9).",
                    "core_fear" => "Being unsupported, guide-less, or abandoned.",
                    "core_desire" => "To have security and support.",
                    "core_weakness" => "Fear (continually planning for the worst possibilities to preempt anxiety).",
                    "soul_message" => "You are safe, supported, and guided.",
                    "growth_arrow_desc" => "In growth, the Loyalist integrates towards Type 9, finding inner calm, trusting life, and letting go of constant scanning.",
                    "stress_arrow_desc" => "In stress, the Loyalist disintegrates towards Type 3, acting driven, defensive, and projecting a false, competent mask.",
                    "growth_action" => "Develop confidence in your own authority and trust your primary instincts.",
                    "relationship_action" => "Avoid testing your partner's loyalty; express your vulnerabilities openly instead.",
                    "career_action" => "Acknowledge progress and success instead of focusing only on what could go wrong.",
                    "stress_action" => "Limit news intake and practice mindfulness techniques to quiet catastrophic loops.",
                    "daily_habit" => "Identify one situation today where you can trust the natural flow of outcomes."
                ],
                7 => [
                    "name" => "The Enthusiast",
                    "core_orientation" => "Seeking excitement, options, versatility, and avoiding discomfort.",
                    "description" => "Enthusiasts are spontaneous, versatile, distractible, and quick-thinking. They seek positive experiences, constantly planning future options to outrun inner pain.",
                    "key_traits" => "Optimistic, playful, quick-witted, adventurous, versatile.",
                    "key_drivers" => "Staying stimulated, keeping options open, experiencing pleasure, and avoiding boredom/sorrow.",
                    "biggest_fear" => "Being deprived, pain-bound, trapped in negativity, or limited.",
                    "core_values" => "Freedom, joy, optimism, abundance, lifelong learning.",
                    "decision_making_style" => "Fast and expansive, prioritizing possibilities, novel ideas, and positive opportunities.",
                    "stress_reactions" => "Becomes critical, perfectionistic, and demanding under stress (moves to Type 1).",
                    "security_triggers" => "Becomes focused, quiet, and deeply analytical when safe (moves to Type 5).",
                    "core_fear" => "Being deprived, trapped, or stuck in pain.",
                    "core_desire" => "To be free, happy, and fully satisfied.",
                    "core_weakness" => "Gluttony (insatiable craving for future plans and fresh, exciting stimulations).",
                    "soul_message" => "You will be completely provided for.",
                    "growth_arrow_desc" => "In growth, the Enthusiast integrates towards Type 5, developing focus, deep analytical capacity, and calm patience.",
                    "stress_arrow_desc" => "In stress, the Enthusiast disintegrates towards Type 1, becoming dogmatic, irritable, and structural.",
                    "growth_action" => "Practice staying in the present moment, even when experiencing mild discomfort or boredom.",
                    "relationship_action" => "Commit to deep, serious conversations and showing up during difficult emotional seasons.",
                    "career_action" => "See projects through to completion before launching into the next attractive idea.",
                    "stress_action" => "Slow down your speech, schedule moments of silence, and restrict multitasking.",
                    "daily_habit" => "Stay with a simple, quiet task for twenty consecutive minutes without checking your phone."
                ],
                8 => [
                    "name" => "The Challenger",
                    "core_orientation" => "Expressing strength, asserting control, and protecting resources.",
                    "description" => "Challengers are self-confident, strong, assertive, and protective. They stand up for beliefs, resist manipulation, and guard their personal vulnerabilities.",
                    "key_traits" => "Direct, protective, decisive, powerful, truth-seeking.",
                    "key_drivers" => "Being self-reliant, protecting their inner circle, dominating spaces, and staying strong.",
                    "biggest_fear" => "Being controlled, harmed, weak, or dependent on others.",
                    "core_values" => "Strength, justice, honesty, control, self-reliance.",
                    "decision_making_style" => "Decisive and action-oriented, preferring intuitive, swift execution that demonstrates leadership.",
                    "stress_reactions" => "Becomes quiet, withdrawn, and hyper-observant under pressure (moves to Type 5).",
                    "security_triggers" => "Becomes open-hearted, caring, and protective of others when safe (moves to Type 2).",
                    "core_fear" => "Being controlled, harmed, or vulnerable.",
                    "core_desire" => "To protect themselves and determine their own path.",
                    "core_weakness" => "Lust (intensity of force, desire to dominate and possess life experiences).",
                    "soul_message" => "You will not be harmed; it is safe to open your heart.",
                    "growth_arrow_desc" => "In growth, the Challenger integrates towards Type 2, displaying gentle care, empathy, and open-hearted vulnerability.",
                    "stress_arrow_desc" => "In stress, the Challenger disintegrates towards Type 5, withdrawing, hoarding energy, and analyzing threat vectors.",
                    "growth_action" => "Practice letting down your defenses and trusting others with your personal vulnerabilities.",
                    "relationship_action" => "Soften your style of communication and listen actively without planning a counterargument.",
                    "career_action" => "Encourage others to lead and build consensus rather than directing by sheer force of will.",
                    "stress_action" => "Recognize when anger is masking fatigue, and check in with your quiet feelings.",
                    "daily_habit" => "Consciously cede control over a small daily choice (such as choosing a restaurant) to someone else."
                ],
                9 => [
                    "name" => "The Peacemaker",
                    "core_orientation" => "Maintaining inner calm, resolving conflicts, and adapting to others.",
                    "description" => "Peacemakers are receptive, reassuring, agreeable, and complacent. They avoid conflict to maintain peace, occasionally minimizing their own views.",
                    "key_traits" => "Easygoing, harmonious, accommodating, patient, diplomatic.",
                    "key_drivers" => "Maintaining peace, avoiding tension, holding stability, and uniting groups.",
                    "biggest_fear" => "Fragmentation, separation, conflict, being overlooked, or cut off.",
                    "core_values" => "Harmony, peace of mind, stability, inclusivity, patience.",
                    "decision_making_style" => "Deliberate and consensus-driven, striving to make sure all perspectives feel valued.",
                    "stress_reactions" => "Becomes anxious, reactive, and hyper-vigilant under pressure (moves to Type 6).",
                    "security_triggers" => "Becomes highly focused, efficient, and self-developing when safe (moves to Type 3).",
                    "core_fear" => "Loss of connection, conflict, and separation.",
                    "core_desire" => "To have inner stability and peace of mind.",
                    "core_weakness" => "Sloth (unwillingness to show presence and assert personal desires).",
                    "soul_message" => "Your presence matters in this world.",
                    "growth_arrow_desc" => "In growth, the Peacemaker integrates towards Type 3, taking proactive steps, asserting presence, and achieving goals.",
                    "stress_arrow_desc" => "In stress, the Peacemaker disintegrates towards Type 6, becoming anxious, suspicious, and hyper-planning.",
                    "growth_action" => "Acknowledge your own anger as a source of energy, and express your opinions directly.",
                    "relationship_action" => "Avoid saying 'yes' when you want to say 'no'; hold space for healthy friction.",
                    "career_action" => "Prioritize your own actions first and speak up in meetings to share your insights.",
                    "stress_action" => "Identify chores you have been postponing and execute one immediately.",
                    "daily_habit" => "Speak up clearly and share your choice when asked 'What do you want to do?'"
                ]
            ];

            $wing_archetypes = [
                "1w9" => "The Idealist", "1w2" => "The Activist",
                "2w1" => "The Companion", "2w3" => "The Host/Hostess",
                "3w2" => "The Star", "3w4" => "The Professional",
                "4w3" => "The Aristocrat", "4w5" => "The Bohemian",
                "5w4" => "The Iconoclast", "5w6" => "The Troubleshooter",
                "6w5" => "The Defender", "6w7" => "The Buddy",
                "7w6" => "The Pathfinder", "7w8" => "The Realist",
                "8w7" => "The Independent", "8w9" => "The Bear",
                "9w8" => "The Referee", "9w1" => "The Dreamer"
            ];
            
            $desires_map = [
                1 => ['desire' => 'good', 'desDescription' => 'perfect'],
                2 => ['desire' => 'loved', 'desDescription' => 'needed'],
                3 => ['desire' => 'valued', 'desDescription' => 'successful'],
                4 => ['desire' => 'authentic', 'desDescription' => 'significant'],
                5 => ['desire' => 'competent', 'desDescription' => 'capable'],
                6 => ['desire' => 'secure', 'desDescription' => 'safe'],
                7 => ['desire' => 'satisfied', 'desDescription' => 'fulfilled'],
                8 => ['desire' => 'independent', 'desDescription' => 'strong'],
                9 => ['desire' => 'peaceful', 'desDescription' => 'harmonious']
            ];

            $dom_meta = $enneagramTypes[$domType] ?? $enneagramTypes[9];
            $wing1_meta = $enneagramTypes[$wing1] ?? $enneagramTypes[1];
            $wing2_meta = $enneagramTypes[$wing2] ?? $enneagramTypes[1];
            
            // Calculate active wing and description
            $score_w1 = $raw_scores[$wing1] ?? ($raw_scores[(string)$wing1] ?? 0);
            $score_w2 = $raw_scores[$wing2] ?? ($raw_scores[(string)$wing2] ?? 0);
            $active_wing = $score_w1 >= $score_w2 ? $wing1 : $wing2;
            $wing_key = "{$domType}w{$active_wing}";
            $archetype = $wing_archetypes[$wing_key] ?? "Enneagram Archetype";
            
            $wing_desc = "Your dominant score is driven by Type {$domType}, with a strong secondary influence from your wing, Type {$active_wing}. This creates the unique personality archetype known as '{$archetype}'. This blend guides how you navigate challenges, balancing the core drives of {$dom_meta['name']} with the traits of " . ($enneagramTypes[$active_wing]['name'] ?? 'Wing Type') . ".";

            // Enneagram Integration / Disintegration Math Maps
            $growth_map = [1 => 7, 2 => 4, 3 => 6, 4 => 1, 5 => 8, 6 => 9, 7 => 5, 8 => 2, 9 => 3];
            $stress_map = [1 => 4, 2 => 8, 3 => 9, 4 => 2, 5 => 7, 6 => 3, 7 => 1, 8 => 5, 9 => 6];
            
            $stressNode = $stress_map[$domType] ?? 6;
            $growthNode = $growth_map[$domType] ?? 3;

            // Build dynamic URLs pointing directly to our new imaging endpoints
            if (defined('EMAIL_BASE_URL') && !empty(EMAIL_BASE_URL)) {
                $baseUrl = rtrim(EMAIL_BASE_URL, '/');
            } else {
                $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                
                // Fetch subdirectory context if executing inside a nested path
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                $subFolder = rtrim(dirname($scriptName), '/\\');
                
                $baseUrl = "{$proto}://{$host}" . $subFolder;
            }
            
            $dominant_type_diagram_url = "{$baseUrl}/generate_animated_enneagram.php?dominant_type={$domType}&stress_type={$stressNode}&growth_type={$growthNode}";
            $wings_image_url = "{$baseUrl}/generate_wing_chart.php?core={$domType}&core_desire=" . urlencode($desires_map[$domType]['desire']) . "&left_wing={$wing1}&left_desire=" . urlencode($desires_map[$wing1]['desDescription']) . "&right_wing={$wing2}&right_desire=" . urlencode($desires_map[$wing2]['desDescription']);

            // Load test.html template file
            $templatePath = __DIR__ . '/test.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template file (test.html) is missing.");
            }
            $emailBody = file_get_contents($templatePath);

            // Replaces placeholders by querying the mysql images table for the logo URL
            $logoUrl = "https://raw.githubusercontent.com/Garima2019/enneadash_voice1/main/logo.jpg";
            try {
                $stmtLogo = $pdo->prepare("SELECT image_url FROM images WHERE file_name = 'logo.jpg' LIMIT 1");
                $stmtLogo->execute();
                $logoRow = $stmtLogo->fetch();
                if ($logoRow && !empty($logoRow['image_url'])) {
                    $logoUrl = $logoRow['image_url'];
                }
            } catch (Throwable $dbErr) {
                // Fallback to static resolution if database fails
                if (defined('EMAIL_BASE_URL') && !empty(EMAIL_BASE_URL)) {
                    $logoUrl = "{$baseUrl}/logo.jpg";
                }
            }

            $placeholders = [
                '{{user_name}}' => $user_name,
                '{{logo_url}}' => htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'),
                '{{dominant_type_diagram_url}}' => htmlspecialchars($dominant_type_diagram_url, ENT_QUOTES, 'UTF-8'),
                '{{dominant_type}}' => "Type {$domType} - " . $dom_meta['name'],
                '{{core_orientation}}' => htmlspecialchars($dom_meta['core_orientation'], ENT_QUOTES, 'UTF-8'),
                '{{dominant_type_description}}' => htmlspecialchars($dom_meta['description'], ENT_QUOTES, 'UTF-8'),
                '{{key_traits}}' => htmlspecialchars($dom_meta['key_traits'], ENT_QUOTES, 'UTF-8'),
                '{{key_drivers}}' => htmlspecialchars($dom_meta['key_drivers'], ENT_QUOTES, 'UTF-8'),
                '{{biggest_fear}}' => htmlspecialchars($dom_meta['biggest_fear'], ENT_QUOTES, 'UTF-8'),
                '{{core_values}}' => htmlspecialchars($dom_meta['core_values'], ENT_QUOTES, 'UTF-8'),
                '{{decision_making_style}}' => htmlspecialchars($dom_meta['decision_making_style'], ENT_QUOTES, 'UTF-8'),
                '{{stress_reactions}}' => htmlspecialchars($dom_meta['stress_reactions'], ENT_QUOTES, 'UTF-8'),
                '{{security_triggers}}' => htmlspecialchars($dom_meta['security_triggers'], ENT_QUOTES, 'UTF-8'),
                '{{core_fear}}' => htmlspecialchars($dom_meta['core_fear'], ENT_QUOTES, 'UTF-8'),
                '{{core_desire}}' => htmlspecialchars($dom_meta['core_desire'], ENT_QUOTES, 'UTF-8'),
                '{{core_weakness}}' => htmlspecialchars($dom_meta['core_weakness'], ENT_QUOTES, 'UTF-8'),
                '{{soul_message}}' => htmlspecialchars($dom_meta['soul_message'], ENT_QUOTES, 'UTF-8'),
                '{{growth_arrow_desc}}' => htmlspecialchars($dom_meta['growth_arrow_desc'], ENT_QUOTES, 'UTF-8'),
                '{{stress_arrow_desc}}' => htmlspecialchars($dom_meta['stress_arrow_desc'], ENT_QUOTES, 'UTF-8'),
                '{{wings_image_url}}' => htmlspecialchars($wings_image_url, ENT_QUOTES, 'UTF-8'),
                '{{wing_archetype}}' => htmlspecialchars($wing_key . " - " . $archetype),
                '{{wing_description}}' => htmlspecialchars($wing_desc, ENT_QUOTES, 'UTF-8'),
                '{{growth_action}}' => htmlspecialchars($dom_meta['growth_action'], ENT_QUOTES, 'UTF-8'),
                '{{relationship_action}}' => htmlspecialchars($dom_meta['relationship_action'], ENT_QUOTES, 'UTF-8'),
                '{{career_action}}' => htmlspecialchars($dom_meta['career_action'], ENT_QUOTES, 'UTF-8'),
                '{{stress_action}}' => htmlspecialchars($dom_meta['stress_action'], ENT_QUOTES, 'UTF-8'),
                '{{daily_habit}}' => htmlspecialchars($dom_meta['daily_habit'], ENT_QUOTES, 'UTF-8')
            ];

            foreach ($placeholders as $placeholder => $value) {
                $emailBody = str_replace($placeholder, $value, $emailBody);
            }

            // Build Score breakdown loop rows
            $scoreRowsHtml = "";
            for ($t = 1; $t <= 9; $t++) {
                $rawScore = isset($raw_scores[$t]) ? (float)$raw_scores[$t] : (isset($raw_scores[(string)$t]) ? (float)$raw_scores[(string)$t] : 0.0);
                
                // Scale out-of-10 score back to 0-5 rating
                $normalizedRating = number_format(($rawScore / 10.0) * 5.0, 2);
                $percentage = number_format($rawScore * 10.0, 1);
                
                $typeName = "Type {$t} - " . ($enneagramTypes[$t]['name'] ?? 'Unknown');
                if ($t === $domType) {
                    $typeName .= " <strong>(Dominant)</strong>";
                }

                $scoreRowsHtml .= "<tr style=\"border-bottom: 1px solid #f1f5f9;\">\n";
                $scoreRowsHtml .= "    <td style=\"padding: 10px; color: #334155; font-weight: 500;\">{$typeName}</td>\n";
                $scoreRowsHtml .= "    <td align=\"center\" style=\"padding: 10px; color: #64748b;\">{$normalizedRating} / 5.00</td>\n";
                $scoreRowsHtml .= "    <td align=\"right\" style=\"padding: 10px; color: #0f172a; font-weight: 600;\">{$percentage}%</td>\n";
                $scoreRowsHtml .= "</tr>\n";
            }
            $emailBody = preg_replace('/\{\{#each score_breakdown\}\}.*?\{\{\/each\}\}/s', $scoreRowsHtml, $emailBody);

            $subject = "Your EnneaDash Report - Type {$domType} - " . $dom_meta['name'];
            
            try {
                send_email_via_brevo($email, $user_name, $subject, $emailBody);
                // Save locally for preview/review purposes during testing
                file_put_contents(__DIR__ . '/test_email_output.html', $emailBody);
                echo json_encode(['success' => true, 'message' => 'Report emailed successfully to ' . $email]);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }

        // --- FEEDBACK ROUTES (Requires Auth) ---
        if (($path === '/api/feedback/submit' || $path === '/api/feedback/create') && $method === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($title) || empty($description)) {
                http_response_code(400);
                echo json_encode(['error' => 'Feedback title and description are required.']);
                exit;
            }

            if (strlen($title) > 100) {
                http_response_code(400);
                echo json_encode(['error' => 'Feedback title must not exceed 100 characters.']);
                exit;
            }

            if (strlen($description) > 2000) {
                http_response_code(400);
                echo json_encode(['error' => 'Description must not exceed 2000 characters.']);
                exit;
            }

            $attachment_path = null;
            $attachment_name = null;

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['attachment'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    echo json_encode(['error' => 'File upload failed. Code: ' . $file['error']]);
                    exit;
                }

                // File size check: 10 MB limit (10,485,760 bytes)
                if ($file['size'] > 10485760) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Attachment file size exceeds 10 MB limit.']);
                    exit;
                }

                $original_filename = basename($file['name']);
                $ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc'];
                if (!in_array($ext, $allowed_extensions, true)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid file extension. Only .jpg, .jpeg, .png, .pdf, and .doc files are allowed.']);
                    exit;
                }

                $upload_dir = __DIR__ . '/uploads/feedback/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $unique_name = 'fb_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target_path = $upload_dir . $unique_name;

                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to save uploaded file.']);
                    exit;
                }

                $attachment_path = 'uploads/feedback/' . $unique_name;
                $attachment_name = $original_filename;
            }

            $stmt = $pdo->prepare("INSERT INTO user_feedbacks (user_id, title, description, attachment_path, attachment_name, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'Submitted', NOW(), NOW())");
            $stmt->execute([$user_id, $title, $description, $attachment_path, $attachment_name]);
            $feedback_id = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully!',
                'feedback_id' => (int)$feedback_id
            ]);
            exit;
        }

        if ($path === '/api/feedback/list' && $method === 'GET') {
            $stmt = $pdo->prepare("SELECT id, user_id, title, description, attachment_path, attachment_name, status, created_at, updated_at FROM user_feedbacks WHERE user_id = ? ORDER BY created_at DESC, id DESC");
            $stmt->execute([$user_id]);
            $feedbacks = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'feedbacks' => $feedbacks
            ]);
            exit;
        }

        if (($path === '/api/feedback/view' || $path === '/api/feedback/detail') && $method === 'GET') {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Feedback ID parameter is required.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, user_id, title, description, attachment_path, attachment_name, status, created_at, updated_at FROM user_feedbacks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $feedback = $stmt->fetch();

            if (!$feedback) {
                http_response_code(404);
                echo json_encode(['error' => 'Feedback not found or access denied.']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'feedback' => $feedback
            ]);
            exit;
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found.']);
        exit;
        
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
        exit;
    }
}

// Serve Frontend SPA
$logoUrl = "https://raw.githubusercontent.com/Garima2019/enneadash_voice1/main/New%20folder/enneagram-9.png";
try {
    $pdo = get_db_connection();
    $stmtLogo = $pdo->query("SELECT image_url FROM images WHERE file_name = 'enneagram-9.png' LIMIT 1");
    $logoRow = $stmtLogo->fetch();
    if ($logoRow && !empty($logoRow['image_url'])) {
        $logoUrl = $logoRow['image_url'];
    }
} catch (Throwable $e) {
    if (file_exists(__DIR__ . '/New folder/enneagram-9.png')) {
        $logoUrl = '/New folder/enneagram-9.png';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnneaDash Voice - Voice-Enabled Enneagram Assessment</title>
    <script>
        window.LOGO_URL = <?php echo json_encode($logoUrl); ?>;
        // Check theme preference from localStorage or fallback to default (dark)
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <meta name="description" content="Discover your personality type using our modern, premium, voice-enabled Enneagram Assessment dashboard.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="/app.css">
</head>
<body>
    <div id="app" class="app-container">
        <!-- Floating Ambient Background Orbs -->
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
        <div class="ambient-orb orb-3"></div>

        <!-- Main Navigation Header -->
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
                <nav id="nav-actions">
                    <!-- Dynamically populated via JS -->
                </nav>
            </div>
        </header>

        <!-- Main Workspace Area -->
        <main class="app-main">
            <!-- Screen containers will be mounted here -->
            <div id="screen-mount" class="screen-mount-container">
                <div class="loader-spinner">
                    <div class="spinner"></div>
                    <p>Loading application...</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="app-footer">
            <p>&copy; 2026 EnneaDash Voice. Built with premium design systems and Web Speech AI.</p>
        </footer>
    </div>

    <!-- Application Logic Script -->
    <script src="/app.js"></script>
</body>
</html>
