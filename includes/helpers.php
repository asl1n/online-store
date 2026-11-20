<?php
require_once __DIR__ . '/db.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
/** Clean public URLs: /shop.php -> /shop, /product.php?slug=x -> /product/x, /index.php -> / (see .htaccess). */
function pretty_path(string $p): string {
    if ($p === '/index.php') return '/';
    if ($p === '/admin/index.php') return '/admin/';
    if (preg_match('~^/admin/([a-z-]+)\.php(.*)$~', $p, $m)) return '/admin/' . $m[1] . $m[2];
    if (preg_match('~^/(product|blog)\.php\?slug=([A-Za-z0-9_-]+)$~', $p, $m)) return '/' . $m[1] . '/' . $m[2];
    return preg_replace('~^/(shop|blogs|contact)\.php(?=$|\?)~', '/$1', $p);
}
/** Asset URL with ?v=<file modified time> so browsers never serve a stale CSS/JS file. */
function asset(string $rel): string { $f = __DIR__ . '/..' . $rel; return BASE_URL . $rel . '?v=' . (is_file($f) ? filemtime($f) : 1); }
function redirect(string $path): never { header('Location: ' . BASE_URL . pretty_path($path)); exit; }

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
/** Normalise $_FILES[$field] (single or multiple) into a list of single-file arrays, skipping empty slots. */
function uploaded_files(string $field): array {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field]; $out = [];
    if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    foreach ($f['name'] as $i => $_) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $f['name'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}
/** Validates one uploaded file; returns its extension or throws. */
function check_uploaded_image(array $f): string {
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if ($f['size'] > MAX_UPLOAD_MB * 1024 * 1024) throw new RuntimeException('Image too large (max ' . MAX_UPLOAD_MB . 'MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WEBP, GIF or ICO images are allowed.');
    return $ext;
}
function save_uploaded_image(array $f, string $subdir): string {
    $ext = check_uploaded_image($f);
    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) throw new RuntimeException('Could not save image.');
    return $subdir . '/' . $name;
}
function upload_image(string $field, string $subdir): ?string {
    $list = uploaded_files($field);
    return $list ? save_uploaded_image($list[0], $subdir) : null;
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
/** Public origin for canonical/OG URLs. Optionally define SITE_URL in config.php (recommended in production). */
function site_origin(): string {
    if (defined('SITE_URL') && SITE_URL) return rtrim((string)SITE_URL, '/');
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . $host;
}
/** Plain-text snippet for meta descriptions (cuts at a word, ~155 chars). */
function seo_trim(?string $s, int $len = 155): string {
    $s = preg_replace('~</(p|h[1-6]|li|div|blockquote|tr|figcaption)>~i', ' ', (string)$s);
    $s = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if (mb_strlen($s) <= $len) return $s;
    $cut = mb_substr($s, 0, $len); $sp = mb_strrpos($cut, ' ');
    return rtrim(($sp !== false && $sp > $len * 0.6) ? mb_substr($cut, 0, $sp) : $cut, ' ,.;:-') . '…';
}
function site_name(): string { return setting('site_name', 'Aslin Store'); }

// ---- Rich text: plain->html, allow-list sanitizer, renderer ----
function looks_like_html(string $s): bool {
    return (bool)preg_match('/<\s*(p|h[1-6]|ul|ol|li|blockquote|figure|table|br|img|a|strong|em|b|i|u|s|hr|div|span)\b/i', $s);
}
function plain_to_html(?string $s): string {
    $out = '';
    foreach (preg_split('/\n\s*\n/', trim((string)$s)) as $p) if (trim($p) !== '') $out .= '<p>' . nl2br(e(trim($p))) . '</p>';
    return $out;
}
/** Allow-list HTML sanitizer (no external libs). Anything not explicitly allowed is removed or unwrapped. */
function html_clean(string $html): string {
    $html = trim($html);
    if ($html === '') return '';
    $allow = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [], 'code' => [], 'pre' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'a' => ['href', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height', 'class'], 'figure' => ['class'], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    $rename = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];
    $drop = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option', 'svg', 'math',
             'template', 'noscript', 'link', 'meta', 'base', 'video', 'audio', 'canvas', 'frame', 'frameset', 'applet', 'head', 'title'];

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) return '';

    $walk = function (DOMNode $parent) use (&$walk, $allow, $rename, $drop) {
        foreach (iterator_to_array($parent->childNodes) as $n) {
            if ($n instanceof DOMText) continue;
            if (!($n instanceof DOMElement)) { $parent->removeChild($n); continue; }       // comments, processing instructions
            $tag = strtolower($n->nodeName);
            if (in_array($tag, $drop, true)) { $parent->removeChild($n); continue; }
            if (isset($rename[$tag])) {
                $new = $n->ownerDocument->createElement($rename[$tag]);
                while ($n->firstChild) $new->appendChild($n->firstChild);
                $parent->replaceChild($new, $n); $n = $new; $tag = $rename[$tag];
            }
            if (!isset($allow[$tag])) {                                                      // unknown tag: keep its (cleaned) content only
                $walk($n);
                while ($n->firstChild) $parent->insertBefore($n->firstChild, $n);
                $parent->removeChild($n); continue;
            }
            $names = []; foreach ($n->attributes as $a) $names[] = $a->nodeName;
            foreach ($names as $an) {
                $ln = strtolower($an); $val = $n->getAttribute($an);
                if (!in_array($ln, $allow[$tag], true)) { $n->removeAttribute($an); continue; }
                switch ($ln) {
                    case 'href':
                        $v = preg_replace('/[\x00-\x20\x7F]+/', '', $val);
                        if (preg_match('~^(https?://|mailto:|tel:|#|/(?!/))~i', $v)) $n->setAttribute('href', $v); else $n->removeAttribute('href');
                        break;
                    case 'src':
                        $v = preg_replace('/[\x00-\x20\x7F]+/', '', $val);
                        if (preg_match('~^(https?://|/(?!/))~i', $v)) $n->setAttribute('src', $v); else $n->removeAttribute('src');
                        break;
                    case 'target': if ($val !== '_blank') $n->removeAttribute('target'); break;
                    case 'rel': $n->removeAttribute('rel'); break;
                    case 'alt': $n->setAttribute('alt', mb_substr($val, 0, 200)); break;
                    case 'width': case 'height': case 'colspan': case 'rowspan':
                        if (!preg_match('/^\d{1,4}$/', $val)) $n->removeAttribute($an); break;
                    case 'class':
                        $keep = array_filter(preg_split('/\s+/', trim($val)), fn($t) => preg_match('/^(image|image-inline|image-style-[a-z-]+|image_resized|table)$/', $t));
                        if ($keep) $n->setAttribute('class', implode(' ', $keep)); else $n->removeAttribute('class');
                        break;
                }
            }
            if ($tag === 'a' && $n->getAttribute('target') === '_blank') $n->setAttribute('rel', 'noopener noreferrer');
            if ($tag === 'img') {
                if (!$n->hasAttribute('src')) { $parent->removeChild($n); continue; }
                $n->setAttribute('loading', 'lazy');
            }
            $walk($n);
        }
    };
    $walk($body);
    $out = ''; foreach ($body->childNodes as $c) $out .= $dom->saveHTML($c);
    $out = preg_replace_callback('/&#(\d+);/', fn($m) => (int)$m[1] > 127 ? mb_chr((int)$m[1], 'UTF-8') : $m[0], $out);
    return trim($out);
}
/** Output-safe HTML for a stored content field (new rich text or old plain text). */
function render_content(?string $s): string {
    $s = (string)$s;
    return looks_like_html($s) ? html_clean($s) : plain_to_html($s);
}

// ---- Security headers ----
/**
 * Sends the Content-Security-Policy. Features are cumulative per request, so the captcha and the
 * tracking tags can both be allowed on the same page without one overriding the other.
 * Features: 'captcha' (Google reCAPTCHA, contact page) and 'tags' (Google Tag Manager / Analytics, public pages).
 */
function send_csp(string ...$add): void {
    static $on = [];
    foreach ($add as $f) $on[$f] = true;
    if (headers_sent()) return;
    $src = ['script' => ["'self'"], 'style' => ["'self'", "'unsafe-inline'"], 'img' => ["'self'", 'data:', 'blob:'], 'connect' => ["'self'"], 'frame' => []];
    if (isset($on['captcha'])) {
        array_push($src['script'], 'https://www.google.com', 'https://www.gstatic.com');
        $src['style'][] = 'https://www.gstatic.com'; $src['img'][] = 'https://www.gstatic.com';
        array_push($src['frame'], 'https://www.google.com', 'https://recaptcha.google.com');
    }
    if (isset($on['tags'])) {
        array_push($src['script'], 'https://www.googletagmanager.com', 'https://www.google-analytics.com');
        array_push($src['img'], 'https://www.googletagmanager.com', 'https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://*.googletagmanager.com');
        array_push($src['connect'], 'https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com', 'https://stats.g.doubleclick.net');
        $src['frame'][] = 'https://www.googletagmanager.com';
    }
    $csp = "default-src 'self'; img-src " . implode(' ', array_unique($src['img'])) . '; style-src ' . implode(' ', array_unique($src['style']))
         . '; script-src ' . implode(' ', array_unique($src['script'])) . '; connect-src ' . implode(' ', array_unique($src['connect']))
         . ($src['frame'] ? '; frame-src ' . implode(' ', array_unique($src['frame'])) : '')
         . "; frame-ancestors 'none'; form-action 'self'; base-uri 'self'";
    header('Content-Security-Policy: ' . $csp);
}
function send_security_headers(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    send_csp();
}
send_security_headers();
