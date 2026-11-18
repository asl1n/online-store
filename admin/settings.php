<?php
require __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/contact.php';
$me = require_super();
$self = BASE_URL . '/admin/settings.php';

// key => [label, type, max, required, help, kind, half]
$sections = [
  'General' => [
    'site_name' => ['Site name', 'text', 80, true, 'Shown in the browser tab, admin panel and as the text logo when no logo is uploaded.', '', false],
    'meta_description' => ['Meta description', 'textarea', 255, false, 'Short summary shown in Google results (max 255 characters).', '', false],
  ],
  'Branding' => [
    'header_logo' => ['Header logo', 'image', 0, false, 'Shown in the website header. Transparent PNG/WEBP works best. Empty = site name as text.', '', true],
    'footer_logo' => ['Footer logo', 'image', 0, false, 'Shown in the website footer (dark background). A white/light logo looks best.', '', true],
    'favicon' => ['Favicon', 'image', 0, false, 'Small square icon in the browser tab (PNG or ICO, at least 64×64).', '', true],
  ],
  'Contact details' => [
    'phones' => ['Phone number(s)', 'text', 100, false, 'e.g. 9802055500, 9802055501', 'phone', true],
    'email' => ['Public email', 'text', 150, false, 'Shown on the contact page and footer.', 'email', true],
    'address' => ['Address', 'text', 200, false, '', '', false],
  ],
  'Footer & social' => [
    'footer_about' => ['Footer description', 'textarea', 300, false, 'Short text under the footer logo.', '', false],
    'facebook_url' => ['Facebook page URL', 'text', 255, false, 'Must start with https://', 'url', true],
    'instagram_url' => ['Instagram URL', 'text', 255, false, 'Must start with https://', 'url', true],
    'copyright_text' => ['Copyright text', 'text', 150, false, 'Leave empty for “© year Site name. All rights reserved.”', '', false],
  ],
];
$cur = settings_all(); $errors = []; $vals = $cur;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $new = []; $remove = [];
    foreach ($sections as $items) foreach ($items as $k => [$label, $type, $max, $req, $help, $kind]) {
        if ($type === 'image') {
            try {
                $p = upload_image($k, 'settings');
                if ($p) $new[$k] = $p; elseif (!empty($_POST['remove_' . $k])) $new[$k] = '';
            } catch (Throwable $ex) { $errors[$k] = $ex->getMessage(); }
            continue;
        }
        $v = clean_text((string)($_POST[$k] ?? ''), $type === 'textarea'); $vals[$k] = $v;
        if ($req && $v === '') $errors[$k] = "$label is required.";
        elseif (mb_strlen($v) > $max) $errors[$k] = "$label is too long (max $max characters).";
        elseif ($v !== '' && $kind === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$k] = 'Enter a valid email address.';
        elseif ($v !== '' && $kind === 'url' && !preg_match('~^https?://[^\s]+\.[^\s]+$~i', $v)) $errors[$k] = 'Enter a full link starting with http:// or https://';
        elseif ($v !== '' && $kind === 'phone' && !preg_match('/^[0-9+\-\s(),\/]{5,100}$/', $v)) $errors[$k] = 'Use digits, spaces, + - ( ) , / only.';
        else $new[$k] = $v;
    }
    if (!$errors) {
        $changed = [];
        foreach ($new as $k => $v) {
            if (($cur[$k] ?? '') === $v) continue;
            q('INSERT INTO settings (`key`,`value`,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), updated_by=VALUES(updated_by)', [$k, $v, $me['id']]);
            if (!empty($cur[$k]) && in_array($k, ['header_logo', 'footer_logo', 'favicon'], true)) delete_upload($cur[$k]);
            $changed[] = $k;
        }
        if ($changed) log_activity('update', 'Settings', null, implode(', ', $changed));
        flash('ok', $changed ? 'Settings saved.' : 'No changes to save.'); go($self);
    }
    // uploads that succeeded before another field failed would be orphaned: clean them
    foreach ($new as $k => $v) if (in_array($k, ['header_logo', 'footer_logo', 'favicon'], true) && $v) delete_upload($v);
}
admin_header('Site Settings', 'settings'); echo ui_errors_summary($errors); ?>
<form method="post" enctype="multipart/form-data" class="card formcard" novalidate><?= csrf_field() ?>
<?php foreach ($sections as $title => $items): ?><h2 class="section-title"><?= e($title) ?></h2><div class="form-grid" style="margin-bottom:8px">
<?php foreach ($items as $k => [$label, $type, $max, $req, $help, $kind, $half]):
    $o = ['for' => $k, 'req' => $req, 'help' => $help, 'half' => $half, 'error' => $errors[$k] ?? null];
    if ($type === 'image') {
        $ctl = (!empty($cur[$k]) ? '<img class="thumb" src="' . e(upload_url($cur[$k])) . '" alt="">' : '<small>Nothing uploaded yet.</small>')
             . '<input type="file" id="' . e($k) . '" name="' . e($k) . '" accept="image/png,image/jpeg,image/webp,image/gif,image/x-icon,.ico">'
             . (!empty($cur[$k]) ? '<label class="chk"><input type="checkbox" name="remove_' . e($k) . '"> Remove current</label>' : '');
    } elseif ($type === 'textarea') $ctl = ui_textarea($k, $vals[$k] ?? '', ['rows' => 3, 'maxlength' => $max]);
    else $ctl = ui_input($k, $vals[$k] ?? '', ['maxlength' => $max] + ($req ? ['required' => true] : []));
    echo ui_field($label, $ctl, $o);
endforeach; ?></div><?php endforeach; ?>
<div class="form-actions"><button class="btn">Save settings</button><a class="btn ghost" href="<?= BASE_URL ?>/admin/index.php">Cancel</a></div></form>
<?php admin_footer();
