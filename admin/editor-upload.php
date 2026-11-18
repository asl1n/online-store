<?php
// Image upload endpoint used by the rich-text editor. Returns JSON.
require __DIR__ . '/../includes/admin_layout.php';
header('Content-Type: application/json; charset=utf-8');
function fail(int $code, string $msg): never { http_response_code($code); echo json_encode(['error' => ['message' => $msg]]); exit; }
if (!current_admin()) fail(401, 'Your session expired. Please log in again.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) fail(403, 'Security check failed. Refresh the page and try again.');
try {
    $p = upload_image('upload', 'content');
    if (!$p) fail(400, 'No image received.');
    log_activity('create', 'Image', null, $p);
    echo json_encode(['url' => upload_url($p)]);
} catch (Throwable $e) { fail(400, $e->getMessage()); }
