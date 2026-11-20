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

// ---- Google reCAPTCHA v2 ("I'm not a robot" checkbox) ----
// Keys live in Admin -> Settings -> Google reCAPTCHA. Captcha is only active when BOTH keys are saved.
function captcha_site_key(): string { return setting('recaptcha_site_key'); }
function captcha_enabled(): bool { return captcha_site_key() !== '' && setting('recaptcha_secret_key') !== ''; }
/** Widget markup (put inside the <form>) + the Google script. Empty string when captcha is not configured. */
function captcha_widget(): string {
    if (!captcha_enabled()) return '';
    return '<div class="captcha"><div class="g-recaptcha" data-sitekey="' . e(captcha_site_key()) . '"></div></div>'
         . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}
/** Asks Google whether the token is valid. @return string|null null = OK, otherwise the error to show. */
function captcha_verify(string $token): ?string {
    if (!captcha_enabled()) return null;
    if ($token === '' || strlen($token) > 4096) return 'Please tick “I’m not a robot”.';
    $body = http_build_query(['secret' => setting('recaptcha_secret_key'), 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']);
    $raw = false;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5]);
        $raw = curl_exec($ch); curl_close($ch);
    } else {
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create(['http' => [
            'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $body, 'timeout' => 8]]));
    }
    $res = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($res)) return 'Could not check the captcha right now. Please try again.';
    if (!empty($res['success'])) return null;
    $codes = (array)($res['error-codes'] ?? []);
    if (array_intersect($codes, ['invalid-input-secret', 'missing-input-secret']))
        return APP_DEBUG ? 'DEBUG: the reCAPTCHA secret key in Settings is wrong.' : 'Captcha is not set up correctly. Please try again later.';
    return 'Captcha check failed. Please tick “I’m not a robot” and send again.';
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

    // Captcha last: only spend a Google check on otherwise-valid messages
    if (($cerr = captcha_verify((string)($in['g-recaptcha-response'] ?? ''))) !== null) return [false, ['captcha' => $cerr]];

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
