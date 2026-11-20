<?php
require __DIR__ . '/../includes/admin_layout.php';
$me = require_admin();
admin_header('Dashboard', 'index');
$stats = [['Products', 'products', 'products.php'], ['Brands', 'brands', 'brands.php'], ['Categories', 'categories', 'categories.php'], ['Blogs', 'blogs', 'blogs.php'], ['Banners', 'banners', 'banners.php']];
echo '<div class="stats">';
foreach ($stats as [$l, $t, $href]) echo '<a class="card stat" href="' . BASE_URL . '/admin/' . basename($href, '.php') . '"><b>' . (int)fetch_val("SELECT COUNT(*) FROM `$t`") . '</b><span>' . e($l) . '</span></a>';
echo '<a class="card stat" href="' . BASE_URL . '/admin/messages"><b>' . (int)fetch_val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0') . '</b><span>Unread messages</span></a></div>';
$logs = fetch_all('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 8'); ?>
<h2 class="h2">Recent activity</h2><div class="card tablewrap"><table class="table"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Details</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['admin_name']) ?></td><td><?= e($l['action'] . ' ' . $l['entity']) ?></td><td><?= e($l['details']) ?></td></tr><?php endforeach;
if (!$logs) echo '<tr><td colspan="4" class="empty">No activity yet.</td></tr>'; ?></tbody></table></div>
<?php admin_footer();
