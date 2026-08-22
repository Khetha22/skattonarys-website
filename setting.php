<?php
// ═══════════════════════════════════════════════════════════════
//  SIHLE KHUMALO ATTORNEYS INC
//  File: admin/settings.php — System Settings
// ═══════════════════════════════════════════════════════════════

session_start();
require_once 'config.php';

// ── LOGIN CHECK ──
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$admin_id = $_SESSION['admin_id'];
$success = '';
$error = '';

// ── FETCH CURRENT ADMIN DATA ──
$stmt = $db->prepare("SELECT username, email FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$current_user = $stmt->fetch();

// ── HANDLE UPDATES ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Profile (Username/Email)
    if ($action === 'update_profile') {
        $new_user = clean($_POST['username']);
        $new_email = clean($_POST['email']);

        $update = $db->prepare("UPDATE admin_users SET username = ?, email = ? WHERE id = ?");
        if ($update->execute([$new_user, $new_email, $admin_id])) {
            $_SESSION['admin_username'] = $new_user;
            $success = "Profile updated successfully.";
        }
    }

    // Update Password
    if ($action === 'update_password') {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        // Verify old password first
        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch();

        if (password_verify($old_pass, $user['password_hash'])) {
            if ($new_pass === $confirm_pass) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $admin_id]);
                $success = "Password changed successfully.";
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — Sihle Khumalo Attorneys Inc</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#1B4332;--gm:#2D6A4F;--gold:#B8860B;--white:#fff;--cream:#F8F6F1}
        body{font-family:'Segoe UI',sans-serif;background:#f4f7f6;display:flex}
        
        /* Reuse Sidebar Style from Index */
        .sidebar{width:260px;height:100vh;background:var(--g);position:fixed;color:white;padding:2rem 1rem}
        .sidebar a{display:block;padding:10px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:14px}
        .sidebar a:hover{color:var(--gold)}

        .main{margin-left:260px;padding:3rem;width:100%}
        .settings-card{background:var(--white);padding:2rem;max-width:600px;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:2rem;border-top:4px solid var(--gold)}
        
        h1{color:var(--g);margin-bottom:2rem}
        h2{font-size:16px;color:var(--gm);margin-bottom:1.5rem;text-transform:uppercase;letter-spacing:1px}
        
        .form-group{margin-bottom:1.2rem}
        label{display:block;font-size:12px;margin-bottom:5px;color:#666}
        input{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px}
        
        .btn{padding:10px 20px;background:var(--gold);color:var(--g);border:none;cursor:pointer;font-weight:600;font-size:12px;text-transform:uppercase}
        .btn:hover{background:#C9A84C}
        
        .alert{padding:1rem;margin-bottom:1rem;border-radius:4px;font-size:14px}
        .success{background:#d4edda;color:#155724}
        .error{background:#f8d7da;color:#721c24}
    </style>
</head>
<body>

<nav class="sidebar">
    <h2 style="color:white; margin-bottom:1rem">Admin Settings</h2>
    <a href="index.php">← Back to Dashboard</a>
    <hr style="opacity:0.1; margin:1rem 0">
    <a href="logout.php" style="color:#ffb3b3">Logout</a>
</nav>

<main class="main">
    <h1>Settings</h1>

    <?php if($success): ?> <div class="alert success"><?= $success ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert error"><?= $error ?></div> <?php endif; ?>

    <div class="settings-card">
        <h2>Admin Profile</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($current_user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($current_user['email']) ?>" required>
            </div>
            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>

    <div class="settings-card">
        <h2>Security / Change Password</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="old_password" required>
            </div>
            <hr style="margin:1.5rem 0; opacity:0.1">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>
    </div>
</main>

</body>
</html>