<?php
require_once __DIR__ . '/db.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . BASE_URL . $path); exit; }

// ---- CSRF ----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid or expired form. Go back and refresh.');
    }
}

// ---- Flash messages ----
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// ---- Slugs ----
function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s ?: 'item';
}
function unique_slug(string $table, string $text, int $ignoreId = 0): string {
    $base = slugify($text); $slug = $base; $i = 2;
    while (fetch_val("SELECT COUNT(*) FROM `$table` WHERE slug=? AND id<>?", [$slug, $ignoreId])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ---- Image upload: returns stored path, null if no file, throws on error ----
function upload_image(string $field, string $subdir): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if ($f['size'] > MAX_UPLOAD_MB * 1024 * 1024) throw new RuntimeException('Image too large (max ' . MAX_UPLOAD_MB . 'MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WEBP, GIF or ICO images are allowed.');
    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) throw new RuntimeException('Could not save image.');
    return $subdir . '/' . $name;
}
function delete_upload(?string $rel): void {
    if ($rel && !str_contains($rel, '..') && is_file(UPLOAD_DIR . $rel)) @unlink(UPLOAD_DIR . $rel);
}
function upload_url(?string $rel): string { return $rel ? UPLOAD_URL . $rel : ''; }

// ---- Activity log (admin_name stored so logs survive admin deletion) ----
function log_activity(string $action, string $entity, ?int $entityId = null, ?string $details = null): void {
    $a = current_admin();
    q('INSERT INTO activity_logs (admin_id, admin_name, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?,?)',
      [$a['id'] ?? null, $a['name'] ?? 'system', $action, $entity, $entityId,
       $details ? mb_substr($details, 0, 500) : null, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/** Audit fields to merge into insert/update data. */
function audit_create(): array { $id = current_admin()['id'] ?? null; return ['created_by' => $id, 'updated_by' => $id]; }
function audit_update(): array { return ['updated_by' => current_admin()['id'] ?? null]; }

// ---- Site settings (key/value table, loaded once per request) ----
function settings_all(): array {
    static $c = null;
    if ($c === null) {
        try { $c = array_column(fetch_all('SELECT `key`,`value` FROM settings'), 'value', 'key'); }
        catch (Throwable $e) { $c = []; }
    }
    return $c;
}
function setting(string $key, string $default = ''): string {
    $v = settings_all()[$key] ?? null;
    return ($v === null || $v === '') ? $default : (string)$v;
}
function favicon_tag(): string { $f = setting('favicon'); return $f ? '<link rel="icon" href="' . e(upload_url($f)) . '">' : ''; }
function site_name(): string { return setting('site_name', 'Aslin Store'); }

// ---- Security headers ----
function send_security_headers(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
}
send_security_headers();
