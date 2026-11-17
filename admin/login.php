<?php
require __DIR__ . '/../includes/auth.php';
if (current_admin()) redirect('/admin/index.php');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $error = admin_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
    if (!$error) redirect('/admin/index.php');
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/admin.css"></head>
<body class="login"><form method="post" class="card form">
<h1>ALLURE <small>Admin</small></h1>
<?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
<?= csrf_field() ?>
<div class="row"><label>Email</label><input name="email" type="email" required autofocus></div>
<div class="row"><label>Password</label><input name="password" type="password" required></div>
<button class="btn">Log in</button></form></body></html>
