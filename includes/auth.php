<?php
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}
if (APP_DEBUG) { ini_set('display_errors', '1'); error_reporting(E_ALL); }

function current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    $a = fetch_one('SELECT id,name,email,role,is_active FROM admins WHERE id=?', [$_SESSION['admin_id']]);
    if (!$a || !$a['is_active']) { unset($_SESSION['admin_id']); return null; }
    return $a;
}

function require_admin(): array {
    $a = current_admin();
    if (!$a) redirect('/admin/login');
    return $a;
}
function require_super(): array {
    $a = require_admin();
    if ($a['role'] !== 'super_admin') { http_response_code(403); exit('Only super admins can do this.'); }
    return $a;
}

/** Returns null on success, or an error message. Simple session-based throttle. */
function admin_login(string $email, string $password): ?string {
    $t = $_SESSION['login_fail'] ?? ['n' => 0, 'until' => 0];
    if ($t['until'] > time()) return 'Too many attempts. Try again in ' . ($t['until'] - time()) . 's.';

    $row = fetch_one('SELECT * FROM admins WHERE email=? AND is_active=1', [trim($email)]);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        $t['n']++;
        if ($t['n'] >= 5) $t = ['n' => 0, 'until' => time() + 60];
        $_SESSION['login_fail'] = $t;
        return 'Invalid email or password.';
    }
    unset($_SESSION['login_fail']);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$row['id'];
    q('UPDATE admins SET last_login_at=NOW() WHERE id=?', [$row['id']]);
    log_activity('login', 'admin', (int)$row['id']);
    return null;
}
function admin_logout(): void {
    if (current_admin()) log_activity('logout', 'admin', current_admin()['id']);
    $_SESSION = [];
    session_destroy();
}

// ---- Admin account management ----
function create_admin(string $name, string $email, string $password, string $role = 'admin'): int {
    if ($pe = validate_password($password)) throw new InvalidArgumentException($pe);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email.');
    if (fetch_val('SELECT COUNT(*) FROM admins WHERE email=?', [$email])) throw new InvalidArgumentException('Email already in use.');
    return db_insert('admins', [
        'name' => $name, 'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => in_array($role, ['super_admin', 'admin'], true) ? $role : 'admin',
        'created_by' => current_admin()['id'] ?? null,
    ]);
}

/** Admin changes their own name/email/password. Current password required. */
function update_own_credentials(string $name, string $email, string $currentPw, ?string $newPw): void {
    $me = require_admin();
    $row = fetch_one('SELECT * FROM admins WHERE id=?', [$me['id']]);
    if (!password_verify($currentPw, $row['password_hash'])) throw new InvalidArgumentException('Current password is wrong.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email.');
    if (fetch_val('SELECT COUNT(*) FROM admins WHERE email=? AND id<>?', [$email, $me['id']])) throw new InvalidArgumentException('Email already in use.');
    $data = ['name' => $name, 'email' => $email];
    if ($newPw !== null && $newPw !== '') {
        if ($pe = validate_password($newPw)) throw new InvalidArgumentException($pe);
        $data['password_hash'] = password_hash($newPw, PASSWORD_DEFAULT);
    }
    db_update('admins', (int)$me['id'], $data);
    log_activity('update', 'admin', (int)$me['id'], 'Updated own credentials');
}

function validate_password(string $p): ?string {
    if (strlen($p) < 8) return 'Password must be at least 8 characters.';
    if (strlen($p) > 72) return 'Password must be 72 characters or fewer.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'Password must include at least one letter and one number.';
    return null;
}
