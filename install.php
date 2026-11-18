<?php
// Run once in the browser, then DELETE this file.
require_once __DIR__ . '/includes/auth.php';

$done = false; $error = null;
try {
    $count = 0;
    try { $count = (int) fetch_val('SELECT COUNT(*) FROM admins'); } catch (Throwable $e) { /* tables not created yet */ }
    if ($count > 0) exit('Already installed. Delete install.php.');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        db()->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
        $id = create_admin(trim($_POST['name']), trim($_POST['email']), $_POST['password'], 'super_admin');
        $_SESSION['admin_id'] = $id;
        log_activity('create', 'admin', $id, 'Initial super admin created via installer');
        $done = true;
    }
} catch (Throwable $e) { $error = $e->getMessage(); }
?>
<!doctype html><meta charset="utf-8"><title>Install</title>
<body style="font-family:sans-serif;max-width:420px;margin:60px auto">
<h2>Aslin Store – Install</h2>
<?php if ($done): ?>
  <p>Installed. Tables created and super admin set up.</p>
  <p><b>Now delete <code>install.php</code>.</b></p>
  <p><a href="<?= BASE_URL ?>/admin/login.php">Go to admin login &rarr;</a> &nbsp; then open <b>Settings</b> to add your logos, favicon and contact details.</p>
<?php else: ?>
  <?php if ($error) echo '<p style="color:#b00">' . e($error) . '</p>'; ?>
  <form method="post"><?= csrf_field() ?>
    <p>Name<br><input name="name" required style="width:100%"></p>
    <p>Email<br><input name="email" type="email" required style="width:100%"></p>
    <p>Password (8+ chars)<br><input name="password" type="password" minlength="8" required style="width:100%"></p>
    <button>Create tables &amp; super admin</button>
  </form>
<?php endif; ?>
