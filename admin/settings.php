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
    'twitter_handle' => ['X / Twitter handle', 'text', 16, false, 'Optional, e.g. @aslinstore. Added to Twitter/X link cards.', 'twitter', true],
  ],
  'Branding' => [
    'header_logo' => ['Header logo', 'image', 0, false, 'Shown in the website header. Transparent PNG/WEBP works best. Empty = site name as text.', '', true],
    'footer_logo' => ['Footer logo', 'image', 0, false, 'Shown in the website footer (dark background). A white/light logo looks best.', '', true],
    'favicon' => ['Favicon', 'image', 0, false, 'Small square icon in the browser tab (PNG or ICO, at least 64×64).', '', true],
    'og_image' => ['Social share image', 'image', 0, false, 'Preview picture when a link is shared on WhatsApp, Facebook, Discord, X/Twitter etc. Best: 1200×630 JPG/PNG. Products and blog posts use their own image; this is used for every other page. Empty = header logo.', '', true],
  ],
  'Contact details' => [
    'phones' => ['Phone number(s)', 'text', 100, false, 'e.g. 9802055500, 9802055501', 'phone', true],
    'email' => ['Public email', 'text', 150, false, 'Shown on the contact page and footer.', 'email', true],
    'address' => ['Address', 'text', 200, false, '', '', false],
  ],
  'Google reCAPTCHA (contact form)' => [
    'recaptcha_site_key' => ['Site key', 'text', 100, false, 'Get both keys free at google.com/recaptcha/admin: create a site, choose “reCAPTCHA v2” → “I’m not a robot” checkbox, and add your domain (and localhost for testing). The captcha turns on only when BOTH keys are saved; leave empty to keep it off.', 'recaptcha', true],
    'recaptcha_secret_key' => ['Secret key', 'secret', 100, false, 'Keep this private. It is never shown again after saving. Leave blank to keep the saved key.', 'recaptcha', true],
  ],
  'Google & analytics' => [
    'gtm_id' => ['Google Tag Manager ID', 'text', 20, false, 'Looks like GTM-XXXXXXX (tagmanager.google.com → your container). When set, manage GA4, Ads, pixels etc. inside Tag Manager. Not loaded for logged-in admins.', 'gtm', true],
    'ga4_id' => ['Google Analytics 4 ID', 'text', 20, false, 'Looks like G-XXXXXXXXXX (analytics.google.com → Admin → Data streams). Leave empty if GA4 runs through Tag Manager, so visits are not counted twice.', 'ga4', true],
    'gsc_verification' => ['Search Console verification', 'text', 200, false, 'Google Search Console → Settings → Ownership verification → “HTML tag”. Paste just the code, or the whole <meta> tag. Then submit your sitemap: /sitemap.xml', 'gsc', true],
    'bing_verification' => ['Bing Webmaster verification', 'text', 200, false, 'Optional. Bing Webmaster Tools → “HTML Meta Tag”. Paste the code or the whole <meta> tag.', 'bing', true],
  ],
  'Footer & social' => [
    'footer_about' => ['Footer description', 'textarea', 300, false, 'Short text under the footer logo.', '', false],
    'facebook_url' => ['Facebook page URL', 'text', 255, false, 'Must start with https://', 'url', true],
    'instagram_url' => ['Instagram URL', 'text', 255, false, 'Must start with https://', 'url', true],
    'copyright_text' => ['Copyright text', 'text', 150, false, 'Leave empty for “© year Site name. All rights reserved.”', '', false],
    'powered_by' => ['Powered by (name)', 'text', 80, false, 'Shown on the right side of the footer bar as “Powered by …”. Leave empty to hide.', '', true],
    'powered_by_url' => ['Powered by link', 'text', 255, false, 'Optional. Makes the name a link. Must start with https://', 'url', true],
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
        if ($type === 'secret') {   // never echoed back: blank = keep the saved value, tick the box to erase it
            $v = trim((string)($_POST[$k] ?? ''));
            if ($v === '') { if (!empty($_POST['clear_' . $k])) $new[$k] = ''; continue; }
            if (!preg_match('/^[A-Za-z0-9_-]{20,100}$/', $v)) { $errors[$k] = 'That does not look like a valid key (letters, numbers, - and _ only).'; continue; }
            $new[$k] = $v; continue;
        }
        $v = clean_text((string)($_POST[$k] ?? ''), $type === 'textarea'); $vals[$k] = $v;
        if (in_array($kind, ['gsc', 'bing'], true) && preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $v, $mm)) $v = $vals[$k] = trim($mm[1]);   // they pasted the whole <meta> tag
        if (in_array($kind, ['gtm', 'ga4'], true)) $v = $vals[$k] = strtoupper(str_replace(' ', '', $v));
        if ($req && $v === '') $errors[$k] = "$label is required.";
        elseif (mb_strlen($v) > $max) $errors[$k] = "$label is too long (max $max characters).";
        elseif ($v !== '' && $kind === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$k] = 'Enter a valid email address.';
        elseif ($v !== '' && $kind === 'url' && !preg_match('~^https?://[^\s]+\.[^\s]+$~i', $v)) $errors[$k] = 'Enter a full link starting with http:// or https://';
        elseif ($v !== '' && $kind === 'twitter' && !preg_match('/^@?[A-Za-z0-9_]{1,15}$/', $v)) $errors[$k] = 'Use a handle like @aslinstore (letters, numbers, _ only).';
        elseif ($v !== '' && $kind === 'recaptcha' && !preg_match('/^[A-Za-z0-9_-]{20,100}$/', $v)) $errors[$k] = 'That does not look like a valid key (letters, numbers, - and _ only).';
        elseif ($v !== '' && $kind === 'gtm' && !preg_match('/^GTM-[A-Z0-9]{4,10}$/', $v)) $errors[$k] = 'Enter an ID like GTM-ABC1234.';
        elseif ($v !== '' && $kind === 'ga4' && !preg_match('/^G-[A-Z0-9]{6,14}$/', $v)) $errors[$k] = 'Enter an ID like G-ABCD123456 (GA4 IDs start with G-).';
        elseif ($v !== '' && $kind === 'gsc' && !preg_match('/^[A-Za-z0-9_-]{20,100}$/', $v)) $errors[$k] = 'That does not look like a Search Console code (paste the content value, or the whole meta tag).';
        elseif ($v !== '' && $kind === 'bing' && !preg_match('/^[A-Fa-f0-9]{32}$/', $v)) $errors[$k] = 'Bing codes are 32 letters/numbers (0-9, A-F).';
        elseif ($v !== '' && $kind === 'phone' && !preg_match('/^[0-9+\-\s(),\/]{5,100}$/', $v)) $errors[$k] = 'Use digits, spaces, + - ( ) , / only.';
        else $new[$k] = ($kind === 'twitter' && $v !== '') ? '@' . ltrim($v, '@') : $v;
    }
    if (!$errors) {
        $changed = [];
        foreach ($new as $k => $v) {
            if (($cur[$k] ?? '') === $v) continue;
            q('INSERT INTO settings (`key`,`value`,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), updated_by=VALUES(updated_by)', [$k, $v, $me['id']]);
            if (!empty($cur[$k]) && in_array($k, ['header_logo', 'footer_logo', 'favicon', 'og_image'], true)) delete_upload($cur[$k]);
            $changed[] = $k;
        }
        if ($changed) log_activity('update', 'Settings', null, implode(', ', $changed));
        flash('ok', $changed ? 'Settings saved.' : 'No changes to save.'); go($self);
    }
    // uploads that succeeded before another field failed would be orphaned: clean them
    foreach ($new as $k => $v) if (in_array($k, ['header_logo', 'footer_logo', 'favicon', 'og_image'], true) && $v) delete_upload($v);
}
admin_header('Site Settings', 'settings'); echo ui_errors_summary($errors); ?>
<form method="post" enctype="multipart/form-data" class="card formcard wide" novalidate><?= csrf_field() ?>
<?php foreach ($sections as $title => $items): ?><h2 class="section-title"><?= e($title) ?></h2><div class="form-grid" style="margin-bottom:8px">
<?php foreach ($items as $k => [$label, $type, $max, $req, $help, $kind, $half]):
    $o = ['for' => $k, 'req' => $req, 'help' => $help, 'half' => $half, 'error' => $errors[$k] ?? null];
    if ($type === 'image') {
        $ctl = (!empty($cur[$k]) ? '<img class="thumb" src="' . e(upload_url($cur[$k])) . '" alt="">' : '<small>Nothing uploaded yet.</small>')
             . '<input type="file" id="' . e($k) . '" name="' . e($k) . '" accept="image/png,image/jpeg,image/webp,image/gif,image/x-icon,.ico">'
             . (!empty($cur[$k]) ? '<label class="chk"><input type="checkbox" name="remove_' . e($k) . '"> Remove current</label>' : '');
    } elseif ($type === 'secret') {
        $has = !empty($cur[$k]);
        $ctl = ui_input($k, '', ['type' => 'password', 'maxlength' => $max, 'autocomplete' => 'new-password', 'spellcheck' => 'false', 'placeholder' => $has ? '•••••••• saved (type a new key to replace)' : 'Paste secret key'])
             . ($has ? '<label class="chk"><input type="checkbox" name="clear_' . e($k) . '"> Remove saved key</label>' : '');
    } elseif ($type === 'textarea') $ctl = ui_textarea($k, $vals[$k] ?? '', ['rows' => 3, 'maxlength' => $max]);
    else $ctl = ui_input($k, $vals[$k] ?? '', ['maxlength' => $max] + ($req ? ['required' => true] : []));
    echo ui_field($label, $ctl, $o);
endforeach; ?></div><?php endforeach; ?>
<div class="form-actions"><button class="btn">Save settings</button><a class="btn ghost" href="<?= BASE_URL ?>/admin/index.php">Cancel</a></div></form>
<?php admin_footer();
