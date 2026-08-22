<?php
// ═══════════════════════════════════════════════════════════════
//  SIHLE KHUMALO ATTORNEYS INC - Global Configuration
// ═══════════════════════════════════════════════════════════════

// Set Timezone for South Africa
date_default_timezone_set('Africa/Johannesburg');

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'sihlekv5x5u1_my_new_database');      
define('DB_USER', 'sihlekv5x5u1_ncubehk');    
define('DB_PASS', 'Khetha2288'); 
define('DB_CHARSET', 'utf8mb4');

// --- Email / SMTP Configuration ---
define('SMTP_HOST', 'mail.sihlekhumaloattorneysinc.co.za');
define('SMTP_USER', 'noreply@sihlekhumaloattorneysinc.co.za');
define('SMTP_PASS', 'Khumalo#'); 
define('SMTP_PORT', 587);            
define('SMTP_SECURE', 'tls');       

// --- Firm & Identity Details ---
define('FIRM_NAME',  'Sihle Khumalo Attorneys Inc');
define('FIRM_EMAIL', 'info@sihlekhumaloattorneysinc.co.za');
define('FIRM_TEL',   '031 023-3408');
define('FIRM_CELL',  '076 542 7942');
define('SITE_URL',   'https://sihlekhumaloattorneysinc.co.za');

/**
 * Connect to database using PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check config.php credentials.");
        }
    }
    return $pdo;
}

/**
 * Sanitize user input
 */
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get the real visitor IP address
 */
function getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}