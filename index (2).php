<?php
// ═══════════════════════════════════════════════════════════════
//   SIHLE KHUMALO ATTORNEYS INC
//   File: admin/index.php — Admin Dashboard Panel
//   Upload to: public_html/admin/index.php
// ═══════════════════════════════════════════════════════════════

session_start();

// 1. Enforce Authentication Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Load Config & DB
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'sihlekv5x5u1_my_new_database');
if (!defined('DB_USER')) define('DB_USER', 'sihlekv5x5u1_ncubehk');
if (!defined('DB_PASS')) define('DB_PASS', 'Khetha2288');

function getDashboardDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// Fetch Enquiries from DB
try {
    $db = getDashboardDB();
    $stmt = $db->query("SELECT * FROM enquiries ORDER BY id DESC");
    $enquiries = $stmt->fetchAll();
} catch (\PDOException $e) {
    $enquiries = [];
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Sihle Khumalo Attorneys Inc</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
  body { background: #f4f6f9; color: #333; }
  .header { background: #1B4332; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #B8860B; }
  .header h1 { font-family: Georgia, serif; font-size: 1.2rem; font-weight: normal; }
  .header .user-info { font-size: 13px; color: #B8860B; }
  .btn-logout { background: #B8860B; color: #1B4332; padding: 6px 14px; text-decoration: none; font-size: 12px; font-weight: bold; border-radius: 3px; margin-left: 15px; }
  .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
  .card { background: #fff; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 2rem; }
  .card h2 { font-size: 1.1rem; color: #1B4332; margin-bottom: 1rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem; }
  table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 14px; }
  th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
  th { background: #f8f9fa; color: #1B4332; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
  tr:hover { background: #fdfdfd; }
  .badge { background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
</style>
</head>
<body>

<div class="header">
  <h1>Sihle Khumalo Attorneys Inc — Admin Portal</h1>
  <div class="user-info">
    Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <h2>Submitted Client Enquiries</h2>
    
    <?php if (isset($dbError)): ?>
      <p style="color: red;">Database connection issue: <?= htmlspecialchars($dbError) ?></p>
    <?php elseif (empty($enquiries)): ?>
      <p>No client enquiries received yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Practice Area</th>
            <th>Message</th>
            <th>Date Received</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enquiries as $row): ?>
            <tr>
              <td>#<?= htmlspecialchars($row['id'] ?? '') ?></td>
              <td><strong><?= htmlspecialchars($row['full_name'] ?? ($row['name'] ?? 'N/A')) ?></strong></td>
              <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
              <td><span class="badge"><?= htmlspecialchars($row['matter_type'] ?? ($row['practice_area'] ?? 'General')) ?></span></td>
              <td><?= htmlspecialchars($row['message'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['created_at'] ?? 'Just now') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>