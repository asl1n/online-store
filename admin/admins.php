<?php
require __DIR__ . '/../includes/admin_layout.php';
$me = require_super();
$self = BASE_URL . '/admin/admins.php'; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $do = $_POST['_do'] ?? '';
    try {
        if ($do === 'create') {
            $id = create_admin(clean_text_simple($_POST['name'] ?? ''), trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''), (string)($_POST['role'] ?? 'admin'));
            log_activity('create', 'Admin', $id, (string)$_POST['email']); flash('ok', 'Admin created.'); go($self);
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$me['id']) throw new InvalidArgumentException('You cannot change your own status here.');
        if ($do === 'toggle') {
            q('UPDATE admins SET is_active = 1 - is_active WHERE id=?', [$id]);
            log_activity('update', 'Admin', $id, 'Toggled active'); flash('ok', 'Admin status changed.'); go($self);
        }
        if ($do === 'reset') {
            $pw = (string)($_POST['password'] ?? '');
            if (strlen($pw) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
            db_update('admins', $id, ['password_hash' => password_hash($pw, PASSWORD_DEFAULT)]);
            log_activity('update', 'Admin', $id, 'Password reset'); flash('ok', 'Password reset.'); go($self);
        }
    } catch (InvalidArgumentException $ex) { $error = $ex->getMessage(); }
}
function clean_text_simple(string $s): string { return trim(preg_replace('/\s+/', ' ', strip_tags($s))); }
$rows = fetch_all('SELECT a.*, c.name AS creator FROM admins a LEFT JOIN admins c ON c.id=a.created_by ORDER BY a.id');
admin_header('Admins', 'admins');
if ($error) echo '<div class="flash err">' . e($error) . '</div>'; ?>
<div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th>Created by</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td>
<td><span class="pill <?= $r['is_active'] ? 'on' : '' ?>"><?= $r['is_active'] ? 'Active' : 'Disabled' ?></span></td>
<td><?= e($r['last_login_at'] ?: '—') ?></td><td><?= e($r['creator'] ?: '—') ?></td><td class="act">
<?php if ((int)$r['id'] !== (int)$me['id']): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button name="_do" value="toggle" class="link"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button></form>
<form method="post" class="inl"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="password" name="password" placeholder="New password" minlength="8"><button name="_do" value="reset" class="link">Reset</button></form>
<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<h2>Add admin</h2>
<form method="post" class="card form"><?= csrf_field() ?><input type="hidden" name="_do" value="create">
<div class="row"><label>Name</label><input name="name" required maxlength="100"></div>
<div class="row"><label>Email</label><input name="email" type="email" required></div>
<div class="row"><label>Password (8+)</label><input name="password" type="password" minlength="8" required></div>
<div class="row"><label>Role</label><select name="role"><option value="admin">Admin</option><option value="super_admin">Super admin</option></select></div>
<button class="btn">Create admin</button></form>
<p><small>Admins are disabled, not deleted, so the activity log and "added by" history stay intact.</small></p>
<?php admin_footer();
