<?php
$serviceSlug = trim((string)($_GET['slug'] ?? ''));
if (!preg_match('/^[a-z0-9-]{2,100}$/', $serviceSlug)) {
    http_response_code(404);
    exit('الخدمة غير موجودة.');
}
require __DIR__ . '/service_template.php';
?>
