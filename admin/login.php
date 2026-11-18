<?php
require __DIR__ . '/../includes/admin_layout.php';
if (current_admin()) redirect('/admin/index.php');
$error = null; $errors = []; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string)($_POST['email'] ?? '')); $pw = (string)($_POST['password'] ?? '');
    if ($email === '') $errors['email'] = 'Please enter your email.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'That does not look like a valid email.';
    if ($pw === '') $errors['password'] = 'Please enter your password.';
    if (!$errors) { $error = admin_login($email, $pw); if (!$error) redirect('/admin/index.php'); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login – <?= e(site_name()) ?></title><?= favicon_tag() ?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/admin.css"></head>
<body class="login"><form method="post" class="card loginbox" novalidate><?= csrf_field() ?>
<div class="lb"><b><?= e(mb_strtoupper(site_name())) ?></b><small>ADMIN PANEL</small></div>
<?= $error ? ui_alert('err', $error) : '' ?>
<?= ui_field('Email', ui_input('email', $email, ['type' => 'email', 'autofocus' => true, 'autocomplete' => 'username']), ['for' => 'email', 'error' => $errors['email'] ?? null]) ?>
<?= ui_field('Password', ui_input('password', '', ['type' => 'password', 'autocomplete' => 'current-password']), ['for' => 'password', 'error' => $errors['password'] ?? null]) ?>
<button class="btn">Log in</button></form></body></html>
