<?php
require __DIR__ . '/../includes/admin_layout.php';
require_admin();
$self = BASE_URL . '/admin/messages';
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
    admin_header('Message', 'messages', '<a class="btn ghost" href="' . e($self) . '">' . ui_icon_back() . 'Back to inbox</a>'); ?>
    <?php $when = strtotime((string)$m['created_at']); $subj = trim((string)$m['subject']);
      $re = 'mailto:' . $m['email'] . '?subject=' . rawurlencode('Re: ' . ($subj !== '' ? $subj : 'Your message to ' . site_name())); ?>
    <div class="card msg">
      <div class="msg-head">
        <span class="avatar lg"><?= e(initials((string)$m['name'])) ?></span>
        <div class="msg-who"><b><?= e($m['name']) ?></b><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
        <time class="msg-time" datetime="<?= e(date('c', $when)) ?>"><?= e(date('j M Y', $when)) ?><small><?= e(date('g:i A', $when)) ?></small></time>
      </div>
      <h2 class="msg-subject"><?= $subj !== '' ? e($subj) : '<span class="count">(No subject)</span>' ?></h2>
      <div class="msg-body"><?= e($m['message']) ?></div>
      <div class="msg-meta">
        <div><small>Email</small><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
        <div><small>Phone</small><?php if (trim((string)$m['phone']) !== ''): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string)$m['phone'])) ?>"><?= e($m['phone']) ?></a><?php else: ?><span class="count">Not provided</span><?php endif; ?></div>
        <div><small>Received</small><span><?= e(date('j M Y, g:i A', $when)) ?></span></div>
      </div>
      <div class="msg-actions">
        <a class="btn" href="<?= e($re) ?>">Reply by email</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="unread" class="btn ghost">Mark as unread</button></form>
        <form method="post" class="push" data-confirm-title="Delete message?" data-confirm="This message will be permanently deleted."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="_do" value="delete" class="btn danger-ghost">Delete</button></form>
      </div>
    </div>
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
