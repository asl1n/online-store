<?php
require_once __DIR__ . '/auth.php';

const CONTACT_MAX_PER_10MIN = 3;   // per IP
const CONTACT_MAX_PER_DAY   = 10;  // per IP
const CONTACT_MIN_SECONDS   = 3;   // humans take longer than this to fill a form

/** Call when rendering the form. Starts the "time trap". */
function contact_form_open(): void { $_SESSION['contact_t'] = time(); }

/** Hidden honeypot field: real users never see/fill it. Put inside the <form>. */
function contact_honeypot_field(): string {
    return '<div style="position:absolute;left:-9999px" aria-hidden="true">'
         . '<label>Leave empty<input type="text" name="hp_extra_field" tabindex="-1" autocomplete="off"></label></div>';
}

function clean_text(string $s, bool $multiline = false): string {
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';   // control chars
    if (!$multiline) $s = preg_replace('/\s+/u', ' ', $s) ?? '';
    return trim($s);
}

/** @return array{0:bool,1:array<string,string>} [success, errors] */
function submit_contact(array $in): array {
    // Bots: silently pretend success so they learn nothing
    // While APP_DEBUG is true we say WHY a message was blocked (easier testing)
    $blocked = fn(string $why) => APP_DEBUG ? [false, ['form' => 'DEBUG blocked: ' . $why]] : [true, []];
    if (!empty($in['hp_extra_field'])) return $blocked('honeypot field was filled');
    $opened = $_SESSION['contact_t'] ?? 0;
    if ($opened && time() - $opened < CONTACT_MIN_SECONDS) return $blocked('form submitted too fast (under ' . CONTACT_MIN_SECONDS . 's)');

    csrf_check();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ((int)fetch_val("SELECT COUNT(*) FROM contact_messages WHERE ip_address=? AND created_at > NOW() - INTERVAL 10 MINUTE", [$ip]) >= CONTACT_MAX_PER_10MIN
     || (int)fetch_val("SELECT COUNT(*) FROM contact_messages WHERE ip_address=? AND created_at > NOW() - INTERVAL 1 DAY", [$ip]) >= CONTACT_MAX_PER_DAY) {
        return [false, ['form' => 'Too many messages. Please try again later.']];
    }

    $name    = clean_text((string)($in['name'] ?? ''));
    $email   = clean_text((string)($in['email'] ?? ''));
    $phone   = clean_text((string)($in['phone'] ?? ''));
    $subject = clean_text((string)($in['subject'] ?? ''));
    $message = clean_text((string)($in['message'] ?? ''), true);

    $err = [];
    if ($name === '' || mb_strlen($name) > 100) $err['name'] = 'Enter your name (max 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $err['email'] = 'Enter a valid email.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) $err['phone'] = 'Enter a valid phone number.';
    if (mb_strlen($subject) > 200) $err['subject'] = 'Subject too long (max 200).';
    if (mb_strlen($message) < 5 || mb_strlen($message) > 3000) $err['message'] = 'Message must be 5 to 3000 characters.';
    // Link-spam guard
    if (preg_match_all('~https?://|www\.~i', $message) > 2) $err['message'] = 'Too many links in message.';
    if ($err) return [false, $err];

    // Exact duplicate in the last hour -> ignore quietly
    if (fetch_val("SELECT COUNT(*) FROM contact_messages WHERE email=? AND message=? AND created_at > NOW() - INTERVAL 1 HOUR", [$email, $message])) {
        return $blocked('same message already sent in the last hour');
    }

    db_insert('contact_messages', [
        'name' => $name, 'email' => $email, 'phone' => $phone ?: null,
        'subject' => $subject ?: null, 'message' => $message, 'ip_address' => $ip,
    ]);
    unset($_SESSION['contact_t']);
    return [true, []];
}
