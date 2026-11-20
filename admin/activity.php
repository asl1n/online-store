<?php
require __DIR__ . '/../includes/admin_layout.php';
require_admin();
$per = 30; $page = max(1, (int)($_GET['page'] ?? 1)); $who = (int)($_GET['admin'] ?? 0);
$where = $who ? 'WHERE admin_id=?' : ''; $p = $who ? [$who] : [];
$total = (int)fetch_val("SELECT COUNT(*) FROM activity_logs $where", $p); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$rows = fetch_all("SELECT * FROM activity_logs $where ORDER BY id DESC LIMIT " . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per), $p);
$filter = '<form method="get" data-autosubmit><select name="admin" aria-label="Filter by admin"><option value="">All admins</option>';
foreach (fetch_all('SELECT id,name FROM admins ORDER BY name') as $a) $filter .= '<option value="' . (int)$a['id'] . '"' . ($who === (int)$a['id'] ? ' selected' : '') . '>' . e($a['name']) . '</option>';
$filter .= '</select><noscript><button class="btn ghost">Filter</button></noscript></form>';
admin_header('Activity Log', 'activity', '', $filter); ?>
<div class="card"><div class="tablewrap"><table class="table"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Item</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['admin_name']) ?></td><td><span class="pill"><?= e($l['action']) ?></span></td>
<td><?= e($l['entity'] . ($l['entity_id'] ? ' #' . (int)$l['entity_id'] : '')) ?></td><td><?= e($l['details']) ?></td><td><?= e($l['ip_address']) ?></td></tr><?php endforeach;
if (!$rows) echo '<tr><td colspan="6" class="empty">No activity found.</td></tr>'; ?></tbody></table></div><?= ui_tablefoot($page, $per, $total, $pages, ['entry', 'entries']) ?></div>
<?php admin_footer();
