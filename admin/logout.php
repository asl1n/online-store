<?php
require __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); admin_logout(); }
redirect('/admin/login.php');
