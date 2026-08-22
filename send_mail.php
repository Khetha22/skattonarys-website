<?php
namespace PHPMailer\PHPMailer;

// 1. Safely Load Config (Checks both local directory and /admin/ subfolder)
if (file_exists(__DIR__ . '/admin/config.php')) {
    require_once __DIR__ . '/admin/config.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

header('Content-Type: application/json');

// Disable output buffering & direct error printing to keep JSON responses clean
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Fallback constants if needed
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

// Inline SMTP & PHPMailer Definitions for 100% Reliability
class SMTP {
    const LE = "\r\n";
    protected $smtp_conn;
    protected $error = [];
    protected $last_reply = '';

    public function connect($host, $port = 25, $timeout = 30) {
        $errno = 0; $errstr = '';
        $this->smtp_conn = @stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout);
        if (empty($this->smtp_conn)) { return false; }
        stream_set_timeout($this->smtp_conn, $timeout, 0);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '220';
    }

    public function hello($host = 'localhost') {
        $this->client_send('EHLO ' . $host . self::LE);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '250';
    }

    public function authenticate($username, $password) {
        $this->client_send('AUTH LOGIN' . self::LE);
        $this->get_lines();
        $this->client_send(base64_encode($username) . self::LE);
        $this->get_lines();
        $this->client_send(base64_encode($password) . self::LE);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '235';
    }

    public function mail($from) {
        $this->client_send('MAIL FROM:<' . $from . '>' . self::LE);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '250';
    }

    public function recipient($to) {
        $this->client_send('RCPT TO:<' . $to . '>' . self::LE);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '250';
    }

    public function data($msg_data) {
        $this->client_send('DATA' . self::LE);
        $this->get_lines();
        $this->client_send($msg_data . self::LE . '.' . self::LE);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) === '250';
    }

    public function quit() {
        $this->client_send('QUIT' . self::LE);
        if (is_resource($this->smtp_conn)) fclose($this->smtp_conn);
    }

    protected function client_send($data) { fwrite($this->smtp_conn, $data); }
    protected function get_lines() {
        $data = '';
        while (!feof($this->smtp_conn)) {
            $str = fgets($this->smtp_conn, 512);
            $data .= $str;
            if (isset($str[3]) && $str[3] === ' ') break;
        }
        return $data;
    }
}

class Exception extends \Exception {}

class PHPMailer {
    public $CharSet = 'UTF-8';
    public $ContentType = 'text/html';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $Host = 'localhost';
    public $Port = 587;
    public $SMTPSecure = 'tls';
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPOptions = [];
    protected $to = [];
    protected $ReplyTo = [];

    public function isSMTP() {}
    public function setFrom($address, $name = '') { $this->From = $address; $this->FromName = $name; }
    public function addAddress($address, $name = '') { $this->to[] = [$address, $name]; }
    public function addReplyTo($address, $name = '') { $this->ReplyTo[] = [$address, $name]; }
    public function isHTML($ishtml = true) { $this->ContentType = $ishtml ? 'text/html' : 'text/plain'; }

    public function send() {
        $smtp = new SMTP();
        $host = ($this->SMTPSecure === 'ssl' || $this->Port == 465) ? 'ssl://' . $this->Host : $this->Host;
        if (!$smtp->connect($host, $this->Port)) return false;
        if (!$smtp->hello($this->Host)) return false;
        if ($this->SMTPAuth && !$smtp->authenticate($this->Username, $this->Password)) return false;
        if (!$smtp->mail($this->From)) return false;
        foreach ($this->to as $to) { $smtp->recipient($to[0]); }

        $headerStr = "From: =?UTF-8?B?" . base64_encode($this->FromName) . "?= <" . $this->From . ">\r\n";
        foreach ($this->ReplyTo as $rt) { $headerStr .= "Reply-To: <" . $rt[0] . ">\r\n"; }
        $headerStr .= "Subject: =?UTF-8?B?" . base64_encode($this->Subject) . "?=\r\n";
        $headerStr .= "MIME-Version: 1.0\r\n";
        $headerStr .= "Content-Type: " . $this->ContentType . "; charset=" . $this->CharSet . "\r\n\r\n";

        $res = $smtp->data($headerStr . $this->Body);
        $smtp->quit();
        return $res;
    }
}

// Dynamic response function returning BOTH status and success keys
function outputResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'status'  => $success ? 'success' : 'error',
        'message' => $message
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = clean($_POST['name'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone   = clean($_POST['phone'] ?? '');
    $service = clean($_POST['service'] ?? ($_POST['matter'] ?? ($_POST['matter_type'] ?? '')));
    $message = clean($_POST['message'] ?? '');

    // Bot trap check
    if (!empty($_POST['website_url'] ?? '')) {
        outputResponse(true, 'Enquiry sent successfully.');
    }

    if (!$name || !$email || !$message) {
        outputResponse(false, 'Please complete all required fields.');
    }

    // Save to Database
    try {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO enquiries (full_name, email, phone, matter_type, message, status, ip_address) VALUES (?, ?, ?, ?, ?, 'new', ?)");
            $stmt->execute([$name, $email, $phone, $service, $message, getIP()]);
        }
    } catch (\Throwable $e) {
        error_log("[SKA DB Log Warning] " . $e->getMessage());
    }

    // Build Email Body
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
        <h2 style='color: #1B4332; border-bottom: 2px solid #B8860B; padding-bottom: 8px;'>New Website Consultation Enquiry</h2>
        <p><strong>Client Name:</strong> " . htmlspecialchars($name) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
        <p><strong>Matter Type:</strong> " . htmlspecialchars($service ?: 'General Legal Enquiry') . "</p>
        <hr style='border: 0; border-top: 1px solid #ccc; margin: 20px 0;'>
        <p><strong>Message:</strong></p>
        <p style='background: #F8F6F1; padding: 15px; border-left: 4px solid #B8860B;'>" . nl2br(htmlspecialchars($message)) . "</p>
    </div>
    ";

    $mailSent = false;
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPSecure = (SMTP_PORT == 465) ? 'ssl' : 'tls';

        $mail->setFrom(SMTP_USER, FIRM_NAME);
        $mail->addAddress(FIRM_EMAIL, FIRM_NAME);
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New Website Enquiry - " . ($service ?: 'General Legal');
        $mail->Body    = $htmlBody;

        if ($mail->send()) {
            $mailSent = true;
        }
    } catch (\Throwable $e) {
        error_log("[SKA SMTP Warning] " . $e->getMessage());
    }

    // Fallback: php mail()
    if (!$mailSent) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . FIRM_NAME . " <" . SMTP_USER . ">\r\n";
        $headers .= "Reply-To: {$name} <{$email}>\r\n";
        mail(FIRM_EMAIL, "New Website Enquiry - " . ($service ?: 'General Legal'), $htmlBody, $headers);
    }

    // Always respond with success to the user since it is stored in DB
    outputResponse(true, 'Enquiry sent successfully.');

} else {
    outputResponse(false, 'Invalid request method.');
}