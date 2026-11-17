<?php
require __DIR__ . '/../includes/admin_layout.php';
require_admin();
$per = 30; $page = max(1, (int)($_GET['page'] ?? 1));
$who = (int)($_GET['admin'] ?? 0);
$where = $who ? 'WHERE admin_id=?' : ''; $p = $who ? [$who] : [];
$total = (int)fetch_val("SELECT COUNT(*) FROM activity_logs $where", $p); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$rows = fetch_all("SELECT * FROM activity_logs $where ORDER BY id DESC LIMIT " . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per), $p);
$admins = fetch_all('SELECT id,name FROM admins ORDER BY name');
admin_header('Activity Log', 'activity'); ?>
<form method="get" class="bar"><select name="admin"><option value="">All admins</option>
<?php foreach ($admins as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $who === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select><button class="btn ghost">Filter</button></form>
<div class="card"><table><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Item</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['admin_name']) ?></td><td><?= e($l['action']) ?></td>
<td><?= e($l['entity']) ?><?= $l['entity_id'] ? ' #' . (int)$l['entity_id'] : '' ?></td><td><?= e($l['details']) ?></td><td><?= e($l['ip_address']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?= pager($page, $pages) ?>
<?php admin_footer();
