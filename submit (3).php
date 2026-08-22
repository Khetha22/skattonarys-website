<?php
// ═══════════════════════════════════════════════════════════════
//   SIHLE KHUMALO ATTORNEYS INC - Form Submission Handler
//   Lives at: public_html/admin/submit.php
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json');

// Disable direct error printing to keep JSON responses clean
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. Imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

// 2. Safely Load Config File
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 3. Fallback Constants (In case config.php values are missing)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'sihlekv5x5u1_my_new_database');
if (!defined('DB_USER')) define('DB_USER', 'sihlekv5x5u1_ncubehk');
if (!defined('DB_PASS')) define('DB_PASS', 'Khetha2288');
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'mail.sihlekhumaloattorneysinc.co.za');
if (!defined('SMTP_USER')) define('SMTP_USER', 'noreply@sihlekhumaloattorneysinc.co.za');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'Khumalo#');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('FIRM_NAME')) define('FIRM_NAME', 'Sihle Khumalo Attorneys Inc');
if (!defined('FIRM_EMAIL')) define('FIRM_EMAIL', 'info@sihlekhumaloattorneysinc.co.za');

// 4. Standalone Helper Functions (Guards against missing function errors)
if (!function_exists('clean')) {
    function clean($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getIP')) {
    function getIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return $pdo;
    }
}

// 5. Safely Load PHPMailer Classes
$phpmailerDir = file_exists(__DIR__ . '/PHPMailer/src/Exception.php') 
    ? __DIR__ . '/PHPMailer/src/' 
    : __DIR__ . '/../PHPMailer/src/';

if (file_exists($phpmailerDir . 'Exception.php')) {
    require_once $phpmailerDir . 'Exception.php';
    require_once $phpmailerDir . 'PHPMailer.php';
    require_once $phpmailerDir . 'SMTP.php';
}

function respond($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Ensure POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    respond(false, 'Invalid request method.');
}

// ── 1. Sanitise & Validate Input ────────────────────────────────
$name    = clean($_POST['name']    ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = clean($_POST['phone']   ?? '');
$matter  = clean($_POST['matter']  ?? ($_POST['service'] ?? ''));
$message = clean($_POST['message'] ?? '');

// Honeypot field (bot trap)
if (!empty($_POST['website_url'] ?? '')) {
    respond(true);
}

if (empty($name) || empty($email) || empty($message)) {
    respond(false, 'Please fill in your name, email, and message.');
}

if (!$email) {
    respond(false, 'Please enter a valid email address.');
}

// ── 2. Save to Database ─────────────────────────────────────────
try {
    $db = getDB();
    if ($db) {
        $sql = "INSERT INTO enquiries (full_name, email, phone, matter_type, message, status, ip_address)
                VALUES (?, ?, ?, ?, ?, 'new', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $email, $phone, $matter, $message, getIP()]);
    }
} catch (\Throwable $dbError) {
    error_log("[SKA] DB Error: " . $dbError->getMessage());
    // Continue execution so user still gets an email / response
}

// ── 3. Build Email Body ─────────────────────────────────────────
$submitted = date('d F Y \a\t H:i');

$htmlBody = "
<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;'>
  <div style='background:#1B4332;padding:20px 30px;border-radius:4px 4px 0 0;'>
    <h2 style='color:#fff;margin:0;'>New Website Enquiry</h2>
    <p style='color:#aaa;margin:5px 0 0;font-size:13px;'>" . FIRM_NAME . "</p>
  </div>
  <div style='border:1px solid #ddd;border-top:none;padding:25px 30px;border-radius:0 0 4px 4px;'>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:8px 0;font-weight:bold;width:130px;vertical-align:top;'>Name:</td><td style='padding:8px 0;'>" . htmlspecialchars($name) . "</td></tr>
      <tr><td style='padding:8px 0;font-weight:bold;vertical-align:top;'>Email:</td><td style='padding:8px 0;'>" . htmlspecialchars($email) . "</td></tr>
      <tr><td style='padding:8px 0;font-weight:bold;vertical-align:top;'>Phone:</td><td style='padding:8px 0;'>" . (empty($phone) ? '<em style="color:#999;">Not provided</em>' : htmlspecialchars($phone)) . "</td></tr>
      <tr><td style='padding:8px 0;font-weight:bold;vertical-align:top;'>Matter Type:</td><td style='padding:8px 0;'>" . (empty($matter) ? '<em style="color:#999;">Not specified</em>' : htmlspecialchars($matter)) . "</td></tr>
      <tr><td style='padding:8px 0;font-weight:bold;vertical-align:top;'>Submitted:</td><td style='padding:8px 0;'>{$submitted}</td></tr>
    </table>
    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
    <p style='font-weight:bold;margin-bottom:8px;'>Message:</p>
    <p style='background:#f8f8f8;padding:15px;border-left:3px solid #1B4332;margin:0;line-height:1.6;'>"
      . nl2br(htmlspecialchars($message)) .
    "</p>
  </div>
  <p style='color:#999;font-size:11px;text-align:center;margin-top:15px;'>
    Sent automatically from sihlekhumaloattorneysinc.co.za
  </p>
</body>
</html>";

$plainBody = "NEW WEBSITE ENQUIRY\n"
           . "===================\n"
           . "Name:        {$name}\n"
           . "Email:       {$email}\n"
           . "Phone:       {$phone}\n"
           . "Matter Type: {$matter}\n"
           . "Submitted:   {$submitted}\n\n"
           . "Message:\n{$message}\n";

// ── 4. Send Email via PHPMailer ──────────────────────────────────
$mailSent = false;

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;
        
        if (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom(SMTP_USER, FIRM_NAME);
        $mail->addAddress(FIRM_EMAIL, FIRM_NAME);
        $mail->addReplyTo($email, $name);
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "New Enquiry [" . ($matter ?: 'General') . "] from {$name}";
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody;
        
        $mail->send();
        $mailSent = true;

    } catch (\Throwable $mailError) {
        error_log("[SKA] PHPMailer Error: " . $mailError->getMessage());
    }
}

// ── 5. Fallback: php mail() ──────────────────────────────────────
if (!$mailSent) {
    $fallbackHeaders  = "MIME-Version: 1.0\r\n";
    $fallbackHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $fallbackHeaders .= "From: " . FIRM_NAME . " <" . SMTP_USER . ">\r\n";
    $fallbackHeaders .= "Reply-To: {$name} <{$email}>\r\n";

    $fallbackResult = mail(
        FIRM_EMAIL,
        "New Enquiry [" . ($matter ?: 'General') . "] from {$name}",
        $htmlBody,
        $fallbackHeaders
    );

    if ($fallbackResult) {
        error_log("[SKA] php mail() fallback succeeded.");
        $mailSent = true;
    } else {
        error_log("[SKA] Both SMTP and php mail() failed.");
    }
}

// ── 6. Respond with JSON ─────────────────────────────────────────
respond(true, 'Your enquiry has been sent successfully.');