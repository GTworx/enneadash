<?php
// config.php - Application Configuration

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'enneagram_app');
define('DB_USER', 'root');
define('DB_PASS', 'pass123');

// Brevo (formerly Sendinblue) Transactional Email API Key
// Replace this with your actual Brevo API key: https://app.brevo.com/settings/keys/api
define('BREVO_API_KEY', 'your_brevo_api_key');

// Verified sender email address in your Brevo account (https://app.brevo.com/senders)
define('EMAIL_FROM', 'garima.agw01@gmail.com');

// Public host URL used in emails. When testing locally, Google Image Proxy 
// cannot access localhost. Set this to a public tunnel URL (e.g. ngrok/localtunnel)
// to make the dynamic images load correctly. For production, set to your live website domain.
define('EMAIL_BASE_URL', '');// e.g. 'https://your-ngrok-subdomain.ngrok-free.app'

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get a PDO database connection.
 */
function get_db_connection() {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Return JSON error response if this is an API call
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit(1);
        }
        die("Database connection failed: " . $e->getMessage());
    }
}

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
 * Sends an automatic onboarding email to a newly created user with credentials and assessment link.
 */
if (!function_exists('send_onboarding_email')) {
    function send_onboarding_email($to_email, $to_name) {
        if (defined('EMAIL_BASE_URL') && !empty(EMAIL_BASE_URL)) {
            $baseUrl = rtrim(EMAIL_BASE_URL, '/');
        } else {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $subFolder = rtrim(dirname($scriptName), '/\\');
            $baseUrl = "{$proto}://{$host}" . $subFolder;
        }
        $assessmentUrl = "{$baseUrl}/";
        
        $subject = "Welcome to EnneaDash Voice - Your Account Credentials";
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Welcome to EnneaDash Voice</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #090b14; font-family: \'Plus Jakarta Sans\', Segoe UI, Tahoma, Geneva, Verdana, sans-serif; color: #f8fafc;">
            <div style="max-width: 600px; margin: 40px auto; background: #0f172a; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(156, 39, 176, 0.2)); padding: 32px 24px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <h1 style="margin: 0; font-size: 26px; color: #ffffff; font-weight: 800;">
                        Ennea<span style="color: #6366f1;">Dash</span> <span style="background: linear-gradient(135deg, #00f2fe, #4facfe); color: #040914; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 4px; vertical-align: middle; letter-spacing: 1px;">VOICE</span>
                    </h1>
                    <p style="color: #94a3b8; font-size: 14px; margin-top: 8px; margin-bottom: 0;">Voice-Enabled Enneagram Assessment Portal</p>
                </div>
                
                <div style="padding: 32px 28px;">
                    <h2 style="color: #ffffff; font-size: 20px; margin-top: 0; margin-bottom: 16px;">Welcome aboard, ' . htmlspecialchars($to_name) . '!</h2>
                    <p style="color: #cbd5e1; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
                        An administrator has created your EnneaDash Voice account. You can now log in to discover your Enneagram personality archetype using our interactive assessment platform.
                    </p>
                    
                    <div style="background-color: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 28px;">
                        <h3 style="margin-top: 0; margin-bottom: 14px; font-size: 15px; color: #00f2fe; text-transform: uppercase; letter-spacing: 0.5px;">Your Account Credentials</h3>
                        <div style="font-size: 14px; color: #f8fafc; margin-bottom: 10px;">
                            <span style="color: #94a3b8; display: inline-block; width: 120px;">Email Address:</span>
                            <strong>' . htmlspecialchars($to_email) . '</strong>
                        </div>
                        <div style="font-size: 14px; color: #f8fafc;">
                            <span style="color: #94a3b8; display: inline-block; width: 120px;">Default Password:</span>
                            <strong style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-family: monospace; letter-spacing: 1px; color: #ffffff;">ed@123</strong>
                        </div>
                    </div>
                    
                    <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 28px;">
                        <strong>Security Reminder:</strong> Upon your first sign-in with your default password, you will be prompted to update your password for account security.
                    </p>
                    
                    <div style="text-align: center; margin-bottom: 24px;">
                        <a href="' . htmlspecialchars($assessmentUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #6366f1, #9c27b0); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
                            Start Assessment Now &rarr;
                        </a>
                    </div>
                </div>
                
                <div style="background-color: rgba(0, 0, 0, 0.2); padding: 20px 28px; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 12px; color: #64748b;">
                    <p style="margin: 0;">&copy; 2026 EnneaDash Voice. Built with premium design systems and Web Speech AI.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return send_email_via_brevo($to_email, $to_name, $subject, $htmlContent);
    }
}
