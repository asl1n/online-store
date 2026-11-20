<?php
require __DIR__ . '/../includes/admin_layout.php';
$me = require_super();
$self = BASE_URL . '/admin/admins';
$act = $_GET['action'] ?? 'list'; $rid = (int)($_GET['id'] ?? 0); $errors = []; $old = [];
$clean = fn($s) => trim(preg_replace('/\s+/', ' ', strip_tags((string)$s)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $do = $_POST['_do'] ?? ''; $old = $_POST;
    if ($do === 'create') {
        $name = $clean($_POST['name'] ?? ''); $email = trim((string)($_POST['email'] ?? '')); $pw = (string)($_POST['password'] ?? ''); $role = (string)($_POST['role'] ?? 'admin');
        if ($name === '' || mb_strlen($name) > 100) $errors['name'] = 'Enter a name (max 100 characters).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errors['email'] = 'Enter a valid email address.';
        elseif (fetch_val('SELECT COUNT(*) FROM admins WHERE email=?', [$email])) $errors['email'] = 'This email is already used by another admin.';
        if ($e = validate_password($pw)) $errors['password'] = $e; elseif ($pw !== (string)($_POST['password2'] ?? '')) $errors['password2'] = 'The two passwords do not match.';
        if (!in_array($role, ['admin', 'super_admin'], true)) $errors['role'] = 'Choose a valid role.';
        if (!$errors) { $id = create_admin($name, $email, $pw, $role); log_activity('create', 'Admin', $id, $email); flash('ok', 'Admin “' . $name . '” created.'); go($self); }
        $act = 'new';
    } elseif ($do === 'toggle' || $do === 'reset') {
        $rid = (int)($_POST['id'] ?? 0); $t = fetch_one('SELECT * FROM admins WHERE id=?', [$rid]);
        if (!$t) { flash('err', 'Admin not found.'); go($self); }
        if ($rid === (int)$me['id']) { flash('err', 'You cannot change your own status or password here. Use My Profile.'); go($self); }
        if ($do === 'toggle') {
            q('UPDATE admins SET is_active = 1 - is_active WHERE id=?', [$rid]);
            log_activity('update', 'Admin', $rid, $t['is_active'] ? 'Disabled' : 'Enabled'); flash('ok', $t['name'] . ($t['is_active'] ? ' has been disabled.' : ' has been enabled.')); go($self);
        }
        $pw = (string)($_POST['password'] ?? '');
        if ($e = validate_password($pw)) $errors['password'] = $e; elseif ($pw !== (string)($_POST['password2'] ?? '')) $errors['password2'] = 'The two passwords do not match.';
        if (!$errors) { db_update('admins', $rid, ['password_hash' => password_hash($pw, PASSWORD_DEFAULT)]); log_activity('update', 'Admin', $rid, 'Password reset'); flash('ok', 'Password reset for ' . $t['name'] . '.'); go($self); }
        $act = 'reset';
    }
}
$back = ui_back_btn($self);

if ($act === 'new') {
    admin_header('Add Admin', 'admins', $back); echo ui_errors_summary($errors); ?>
    <form method="post" class="card formcard" novalidate><?= csrf_field() ?><input type="hidden" name="_do" value="create"><div class="form-grid">
    <?= ui_field('Full name', ui_input('name', $old['name'] ?? '', ['maxlength' => 100, 'required' => true]), ['for' => 'name', 'req' => true, 'half' => true, 'error' => $errors['name'] ?? null]) ?>
    <?= ui_field('Email', ui_input('email', $old['email'] ?? '', ['type' => 'email', 'maxlength' => 150, 'required' => true]), ['for' => 'email', 'req' => true, 'half' => true, 'error' => $errors['email'] ?? null]) ?>
    <?= ui_field('Password', ui_input('password', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'password', 'req' => true, 'half' => true, 'help' => 'At least 8 characters with a letter and a number.', 'error' => $errors['password'] ?? null]) ?>
    <?= ui_field('Confirm password', ui_input('password2', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'password2', 'req' => true, 'half' => true, 'error' => $errors['password2'] ?? null]) ?>
    <?= ui_field('Role', '<select id="role" name="role"><option value="admin"' . (($old['role'] ?? '') !== 'super_admin' ? ' selected' : '') . '>Admin – manages content</option><option value="super_admin"' . (($old['role'] ?? '') === 'super_admin' ? ' selected' : '') . '>Super admin – also manages admins &amp; settings</option></select>', ['for' => 'role', 'half' => true, 'error' => $errors['role'] ?? null]) ?>
    </div><div class="form-actions"><button class="btn">Create admin</button><a class="btn ghost" href="<?= e($self) ?>">Cancel</a></div></form>
    <?php admin_footer(); exit;
}
if ($act === 'reset') {
    $t = fetch_one('SELECT * FROM admins WHERE id=?', [$rid]);
    if (!$t || $rid === (int)$me['id']) { flash('err', 'Admin not found.'); go($self); }
    admin_header('Reset password', 'admins', $back); echo ui_errors_summary($errors); ?>
    <form method="post" class="card formcard"><?= csrf_field() ?><input type="hidden" name="_do" value="reset"><input type="hidden" name="id" value="<?= $rid ?>">
    <p>Set a new password for <b><?= e($t['name']) ?></b> (<?= e($t['email']) ?>).</p><div class="form-grid">
    <?= ui_field('New password', ui_input('password', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'password', 'req' => true, 'half' => true, 'error' => $errors['password'] ?? null]) ?>
    <?= ui_field('Confirm password', ui_input('password2', '', ['type' => 'password', 'autocomplete' => 'new-password']), ['for' => 'password2', 'req' => true, 'half' => true, 'error' => $errors['password2'] ?? null]) ?>
    </div><div class="form-actions"><button class="btn">Reset password</button><a class="btn ghost" href="<?= e($self) ?>">Cancel</a></div></form>
    <?php admin_footer(); exit;
}
$rows = fetch_all('SELECT a.*, c.name AS creator FROM admins a LEFT JOIN admins c ON c.id=a.created_by ORDER BY a.id');
admin_header('Admins', 'admins', ui_create_btn($self . '?action=new', 'Admin')); ?>
<div class="card tablewrap"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th>Created by</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $self_row = (int)$r['id'] === (int)$me['id']; ?><tr><td><b><?= e($r['name']) ?></b><?= $self_row ? ' <span class="pill">You</span>' : '' ?></td><td><?= e($r['email']) ?></td>
<td><span class="pill"><?= $r['role'] === 'super_admin' ? 'Super admin' : 'Admin' ?></span></td><td><span class="pill <?= $r['is_active'] ? 'on' : 'off' ?>"><?= $r['is_active'] ? 'Active' : 'Disabled' ?></span></td>
<td><?= e($r['last_login_at'] ? substr($r['last_login_at'], 0, 16) : 'Never') ?></td><td><?= e($r['creator'] ?: '—') ?></td><td class="act">
<?php if (!$self_row): ?><a class="btn sm ghost" href="?action=reset&amp;id=<?= (int)$r['id'] ?>">Reset password</a>
<form method="post" data-confirm-title="<?= $r['is_active'] ? 'Disable' : 'Enable' ?> admin?" data-confirm="<?= $r['is_active'] ? e($r['name']) . ' will no longer be able to log in.' : e($r['name']) . ' will be able to log in again.' ?>"><?= csrf_field() ?><input type="hidden" name="_do" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm <?= $r['is_active'] ? 'danger-ghost' : 'ghost' ?>"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<p><small>Admins are disabled instead of deleted, so the activity log and “added by” history always stay intact.</small></p>
<?php admin_footer();
