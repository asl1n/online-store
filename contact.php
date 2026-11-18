<?php
require __DIR__ . '/includes/site.php';
$errors = []; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $errors] = submit_contact($_POST);
    if ($ok) { flash('ok', 'Thank you! We received your message and will get back to you soon.'); redirect('/contact.php'); }
    $old = $_POST;
} else {
    contact_form_open();
    if (!empty($_GET['product']) && ($pr = fetch_one('SELECT name FROM products WHERE slug=? AND is_active=1', [(string)$_GET['product']]))) $old['subject'] = 'Enquiry: ' . $pr['name'];
}
$v = fn(string $k) => e($old[$k] ?? '');
site_header('Contact', 'contact'); render_banners('contact', true); ?>
<div class="wrap section"><h1>Contact us</h1><div class="detail">
<form method="post" class="cform"><?= csrf_field() ?><?= contact_honeypot_field() ?>
<?php if (isset($errors['form'])): ?><div class="flash err"><?= e($errors['form']) ?></div><?php endif; ?>
<?php foreach (['name' => ['Your name', 'text', 100], 'email' => ['Email', 'email', 150], 'phone' => ['Phone (optional)', 'text', 30], 'subject' => ['Subject (optional)', 'text', 200]] as $k => [$label, $type, $max]): ?>
<label><?= $label ?><input type="<?= $type ?>" name="<?= $k ?>" maxlength="<?= $max ?>" value="<?= $v($k) ?>" <?= in_array($k, ['name', 'email'], true) ? 'required' : '' ?>></label>
<?php if (isset($errors[$k])): ?><div class="err"><?= e($errors[$k]) ?></div><?php endif; endforeach; ?>
<label>Message<textarea name="message" rows="6" maxlength="3000" required><?= $v('message') ?></textarea></label>
<?php if (isset($errors['message'])): ?><div class="err"><?= e($errors['message']) ?></div><?php endif; ?>
<button class="btn">Send message</button></form>
<div class="infobox"><h3>Get in touch</h3><p><b>Phone</b><br><?= e(setting('phones')) ?></p><p><b>Address</b><br><?= e(setting('address')) ?></p><p><b>Email</b><br><?= e(setting('email')) ?></p></div></div></div>
<?php site_footer();
