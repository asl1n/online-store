<?php
require __DIR__ . '/../includes/admin_layout.php';
require_admin();
$self = BASE_URL . '/admin/messages.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0); $do = $_POST['_do'] ?? '';
    if ($do === 'delete') { db_delete('contact_messages', $id); log_activity('delete', 'Message', $id); flash('ok', 'Message deleted.'); }
    if ($do === 'unread') { q('UPDATE contact_messages SET is_read=0 WHERE id=?', [$id]); flash('ok', 'Marked as unread.'); }
    go($self);
}
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $m = fetch_one('SELECT * FROM contact_messages WHERE id=?', [$id]);
    if (!$m) { flash('err', 'That message no longer exists.'); go($self); }
    q('UPDATE contact_messages SET is_read=1 WHERE id=?', [$id]);
    admin_header('Message', 'messages', '<a class="btn ghost" href="' . e($self) . '">&larr; Back to inbox</a>'); ?>
    <div class="card pad msgview" style="max-width:860px">
      <p><b>From:</b> <?= e($m['name']) ?> &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;</p>
      <p><b>Phone:</b> <?= e($m['phone'] ?: '—') ?></p><p><b>Subject:</b> <?= e($m['subject'] ?: '—') ?></p><p><b>Received:</b> <?= e($m['created_at']) ?></p>
      <hr style="border:0;border-top:1px solid var(--line);margin:16px 0"><p style="white-space:pre-wrap"><?= e($m['message']) ?></p>
      <div class="form-actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="unread" class="btn ghost">Mark as unread</button></form>
      <form method="post" data-confirm-title="Delete message?" data-confirm="This message will be permanently deleted."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="delete" class="btn danger-ghost">Delete</button></form></div></div>
    <?php admin_footer(); exit;
}
$per = 20; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)fetch_val('SELECT COUNT(*) FROM contact_messages'); $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$rows = fetch_all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per));
admin_header('Contact Messages', 'messages'); ?>
<div class="toolbar"><span class="count"><?= $total ?> message<?= $total === 1 ? '' : 's' ?></span></div>
<div class="card tablewrap"><table class="table"><thead><tr><th>Status</th><th>Name</th><th>Email</th><th>Subject / message</th><th>Received</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr class="<?= $r['is_read'] ? '' : 'unread' ?>"><td><span class="pill <?= $r['is_read'] ? '' : 'on' ?>"><?= $r['is_read'] ? 'Read' : 'New' ?></span></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td>
<td><?= e(mb_strimwidth((string)($r['subject'] ?: $r['message']), 0, 60, '…')) ?></td><td><?= e(substr((string)$r['created_at'], 0, 16)) ?></td>
<td class="act"><a class="btn sm ghost" href="?id=<?= (int)$r['id'] ?>">View</a></td></tr><?php endforeach;
if (!$rows) echo '<tr><td colspan="6" class="empty">No messages yet. Messages from the website contact form appear here.</td></tr>'; ?></tbody></table></div><?= pager($page, $pages) ?>
<?php admin_footer();
