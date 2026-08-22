<?php
// ═══════════════════════════════════════════════════════════════
//   SIHLE KHUMALO ATTORNEYS INC
//   File: admin/logout.php — Admin Session Destroyer
//   Upload to: public_html/admin/logout.php
// ═══════════════════════════════════════════════════════════════

// 1. Initialize session to access session array
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. If a session cookie exists, destroy it in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Completely destroy session data on the server
session_destroy();

// 5. Prevent browser caching so clicking the back button won't show cached admin data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 6. Redirect to admin login screen
header("Location: login.php");
exit;