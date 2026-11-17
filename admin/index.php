<?php
require __DIR__ . '/../includes/admin_layout.php';
admin_header('Dashboard', 'index');
$stats = ['Products' => 'products', 'Brands' => 'brands', 'Categories' => 'categories', 'Blogs' => 'blogs', 'Banners' => 'banners'];
echo '<div class="stats">';
foreach ($stats as $label => $t) echo '<div class="card stat"><b>' . (int)fetch_val("SELECT COUNT(*) FROM `$t`") . '</b>' . e($label) . '</div>';
echo '<div class="card stat"><b>' . (int)fetch_val('SELECT COUNT(*) FROM contact_messages WHERE is_read=0') . '</b>Unread messages</div></div>';
$logs = fetch_all('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 10');
?>
<h2>Recent activity</h2><div class="card"><table><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['admin_name']) ?></td>
<td><?= e($l['action']) ?> <?= e($l['entity']) ?></td><td><?= e($l['details']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php admin_footer();
