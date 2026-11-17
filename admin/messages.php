<?php
require __DIR__ . '/../includes/admin_layout.php';
require_admin();
$self = BASE_URL . '/admin/messages.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0);
    if (($_POST['_do'] ?? '') === 'delete') { db_delete('contact_messages', $id); log_activity('delete', 'Message', $id); flash('ok', 'Message deleted.'); }
    if (($_POST['_do'] ?? '') === 'unread') { q('UPDATE contact_messages SET is_read=0 WHERE id=?', [$id]); }
    go($self);
}
$id = (int)($_GET['id'] ?? 0);
if ($id && ($m = fetch_one('SELECT * FROM contact_messages WHERE id=?', [$id]))) {
    q('UPDATE contact_messages SET is_read=1 WHERE id=?', [$id]);
    admin_header('Message', 'messages'); ?>
    <div class="card form"><p><b>From:</b> <?= e($m['name']) ?> &lt;<?= e($m['email']) ?>&gt;</p>
    <p><b>Phone:</b> <?= e($m['phone'] ?: '—') ?></p><p><b>Subject:</b> <?= e($m['subject'] ?: '—') ?></p>
    <p><b>Received:</b> <?= e($m['created_at']) ?></p><hr><p style="white-space:pre-wrap"><?= e($m['message']) ?></p>
    <a class="btn ghost" href="<?= e($self) ?>">Back</a>
    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="unread" class="btn ghost">Mark unread</button></form>
    <form method="post" style="display:inline" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="delete" class="btn danger">Delete</button></form></div>
    <?php admin_footer(); exit;
}
$per = 20; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)fetch_val('SELECT COUNT(*) FROM contact_messages'); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$rows = fetch_all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per));
admin_header('Contact Messages', 'messages'); ?>
<div class="card"><table><thead><tr><th></th><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr class="<?= $r['is_read'] ? '' : 'unread' ?>"><td><?= $r['is_read'] ? '' : '●' ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td>
<td><?= e(mb_strimwidth((string)($r['subject'] ?: $r['message']), 0, 60, '…')) ?></td><td><?= e($r['created_at']) ?></td>
<td class="act"><a href="?id=<?= (int)$r['id'] ?>">View</a></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="6">No messages yet.</td></tr>'; ?>
</tbody></table></div><?= pager($page, $pages) ?>
<?php admin_footer();
