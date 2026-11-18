<?php
require __DIR__ . '/../includes/admin_layout.php';
$me = require_admin(); $errors = []; $old = [];
$me = fetch_one('SELECT * FROM admins WHERE id=?', [$me['id']]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $old = $_POST;
    $name = trim(preg_replace('/\s+/', ' ', strip_tags((string)($_POST['name'] ?? '')))); $email = trim((string)($_POST['email'] ?? ''));
    $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? ''); $cur = (string)($_POST['current'] ?? '');
    if ($name === '' || mb_strlen($name) > 100) $errors['name'] = 'Enter your name (max 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errors['email'] = 'Enter a valid email address.';
    if ($new !== '') { if ($e = validate_password($new)) $errors['new'] = $e; elseif ($new !== $new2) $errors['new2'] = 'The two new passwords do not match.'; }
    if ($cur === '') $errors['current'] = 'Enter your current password to save changes.';
    if (!$errors) {
        try { update_own_credentials($name, $email, $cur, $new); flash('ok', 'Your profile was updated.'); go(BASE_URL . '/admin/account.php'); }
        catch (InvalidArgumentException $ex) {
            $m = $ex->getMessage();
            if (stripos($m, 'current') !== false) $errors['current'] = $m; elseif (stripos($m, 'email') !== false) $errors['email'] = $m; else $errors['_form'] = $m;
        }
    }
}
admin_header('My Profile', 'account'); echo ui_errors_summary($errors); ?>
<div class="card profile"><span class="avatar"><?= e(initials($me['name'])) ?></span><dl>
<dt>Name</dt><dd><b><?= e($me['name']) ?></b></dd><dt>Email</dt><dd><?= e($me['email']) ?></dd>
<dt>Role</dt><dd><?= $me['role'] === 'super_admin' ? 'Super admin' : 'Admin' ?></dd><dt>Last login</dt><dd><?= e($me['last_login_at'] ?: '—') ?></dd></dl></div>
<form method="post" class="card formcard wide" novalidate><?= csrf_field() ?><h2 class="section-title">Edit details</h2><div class="form-grid">
<?= ui_field('Full name', ui_input('name', $old['name'] ?? $me['name'], ['maxlength' => 100, 'required' => true]), ['for' => 'name', 'req' => true, 'half' => true, 'error' => $errors['name'] ?? null]) ?>
<?= ui_field('Email', ui_input('email', $old['email'] ?? $me['email'], ['type' => 'email', 'maxlength' => 150, 'required' => true]), ['for' => 'email', 'req' => true, 'half' => true, 'error' => $errors['email'] ?? null]) ?>
</div><h2 class="section-title" style="margin-top:26px">Change password <small>(optional)</small></h2><div class="form-grid">
<?= ui_field('New password', ui_input('new', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'new', 'half' => true, 'help' => 'Leave empty to keep your current password.', 'error' => $errors['new'] ?? null]) ?>
<?= ui_field('Confirm new password', ui_input('new2', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'new2', 'half' => true, 'error' => $errors['new2'] ?? null]) ?>
</div><h2 class="section-title" style="margin-top:26px">Confirm it’s you</h2><div class="form-grid">
<?= ui_field('Current password', ui_input('current', '', ['type' => 'password', 'autocomplete' => 'current-password']), ['for' => 'current', 'req' => true, 'half' => true, 'error' => $errors['current'] ?? null]) ?>
</div><div class="form-actions"><button class="btn">Save changes</button></div></form>
<?php admin_footer();
