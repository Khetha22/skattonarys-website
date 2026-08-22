<?php
// ═══════════════════════════════════════════════════════════════
//   SIHLE KHUMALO ATTORNEYS INC
//   File: admin/login.php — Admin Login Page
//   Upload to: public_html/admin/login.php
// ═══════════════════════════════════════════════════════════════

session_start();

// 1. Safe Config Loading
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 2. Fallback Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'sihlekv5x5u1_my_new_database');
if (!defined('DB_USER')) define('DB_USER', 'sihlekv5x5u1_ncubehk');
if (!defined('DB_PASS')) define('DB_PASS', 'Khetha2288');

// 3. Fallback Helper Functions (Prevents Fatal Error if config.php lacks them)
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

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $user['id'];
                $_SESSION['admin_username']  = $user['username'];
                $_SESSION['admin_role']      = $user['role'] ?? 'admin';

                // Update last login timestamp
                $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Log activity
                try {
                    $db->prepare("INSERT INTO activity_log (admin_id, action, ip_address) VALUES (?, 'Admin login', ?)")
                       ->execute([$user['id'], getIP()]);
                } catch (\Throwable $e) {
                    error_log("[Login Activity Log Error] " . $e->getMessage());
                }

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';

                // Log failed attempt
                try {
                    $db->prepare("INSERT INTO activity_log (action, details, ip_address) VALUES ('Failed login attempt', ?, ?)")
                       ->execute([$username, getIP()]);
                } catch (\Throwable $e) {
                    error_log("[Login Activity Log Error] " . $e->getMessage());
                }
            }
        } catch (\PDOException $e) {
            error_log("[Login DB Error] " . $e->getMessage());
            $error = 'Database connection failure. Please contact system administrator.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Sihle Khumalo Attorneys Inc</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#1B4332;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;
  background-image:repeating-linear-gradient(90deg,rgba(184,134,11,.04) 0,rgba(184,134,11,.04) 1px,transparent 1px,transparent 60px),
    repeating-linear-gradient(0deg,rgba(184,134,11,.04) 0,rgba(184,134,11,.04) 1px,transparent 1px,transparent 60px);}
.login-box{background:#fff;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.3);border-radius:4px;overflow:hidden}
.login-header{background:#1B4332;padding:2rem;text-align:center;border-bottom:3px solid #B8860B}
.login-header h1{font-family:Georgia,serif;font-size:1.3rem;font-weight:400;color:#fff}
.login-header p{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#B8860B;margin-top:.4rem}
.login-body{padding:2rem}
.error{background:#ffebee;border-left:4px solid #e53935;padding:.8rem 1rem;font-size:13.5px;color:#c62828;margin-bottom:1.2rem}
label{display:block;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#4a5e52;margin-bottom:.4rem;margin-top:1rem}
input[type=text],input[type=password]{width:100%;padding:.8rem 1rem;border:1px solid rgba(27,67,50,.2);background:#F8F6F1;font-size:14px;outline:none;transition:border-color .3s}
input:focus{border-color:#2D6A4F;background:#fff}
.btn-login{width:100%;padding:.9rem;background:#B8860B;color:#1B4332;font-size:13px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;border:none;cursor:pointer;margin-top:1.5rem;transition:background .3s}
.btn-login:hover{background:#C9A84C}
.login-footer{text-align:center;padding:1rem;font-size:11px;color:#888;border-top:1px solid #eee}
</style>
</head>
<body>
<div class="login-box">
  <div class="login-header">
    <h1>Sihle Khumalo Attorneys Inc</h1>
    <p>🔒 Admin Portal</p>
  </div>
  <div class="login-body">
    <?php if ($error): ?>
      <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <label>Username or Email</label>
      <input type="text" name="username" placeholder="admin" autocomplete="username" required>
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      <button type="submit" class="btn-login">Login to Dashboard →</button>
    </form>
  </div>
  <div class="login-footer">
    Sihle Khumalo Attorneys Inc &copy; 2026 &nbsp;|&nbsp; Secure Admin Access
  </div>
</div>
</body>
</html>