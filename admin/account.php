<?php
require __DIR__ . '/../includes/admin_layout.php';
$me = require_admin(); $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        update_own_credentials(trim(strip_tags((string)($_POST['name'] ?? ''))), trim((string)($_POST['email'] ?? '')),
            (string)($_POST['current'] ?? ''), (string)($_POST['new'] ?? ''));
        flash('ok', 'Account updated.'); go(BASE_URL . '/admin/account.php');
    } catch (InvalidArgumentException $ex) { $error = $ex->getMessage(); }
}
admin_header('My Account', 'account');
if ($error) echo '<div class="flash err">' . e($error) . '</div>'; ?>
<form method="post" class="card form"><?= csrf_field() ?>
<div class="row"><label>Name</label><input name="name" value="<?= e($me['name']) ?>" required maxlength="100"></div>
<div class="row"><label>Email</label><input name="email" type="email" value="<?= e($me['email']) ?>" required></div>
<div class="row"><label>New password</label><input name="new" type="password" minlength="8" autocomplete="new-password"><small>Leave empty to keep the current one.</small></div>
<div class="row"><label>Current password *</label><input name="current" type="password" required autocomplete="current-password"></div>
<button class="btn">Save</button></form>
<?php admin_footer();
