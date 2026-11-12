<?php
declare(strict_types=1);
session_start();
$configPath = __DIR__ . '/../config.php';
$config = file_exists($configPath) ? require $configPath : [];
$env = static function(string $key, string $default = '') use ($config): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    return (string)($config[$key] ?? $default);
};
$config = [
    'db_host' => $env('DB_HOST', 'localhost'),
    'db_port' => $env('DB_PORT', '5432'),
    'db_name' => $env('DB_NAME', 'postgres'),
    'db_user' => $env('DB_USER', 'postgres'),
    'db_pass' => $env('DB_PASSWORD', ''),
    'db_sslmode' => $env('DB_SSLMODE', 'require'),
    'site_name' => $env('SITE_NAME', $config['site_name'] ?? 'Hani Software'),
    'whatsapp_number' => $env('WHATSAPP_NUMBER', $config['whatsapp_number'] ?? '84562812172'),
];
if ($config['db_pass'] === '') {
    http_response_code(500);
    exit('Database configuration missing. Set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD environment variables. See README_AR.md.');
}
try {
    $pdo = new PDO(
        "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};sslmode={$config['db_sslmode']}",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    error_log('Database connection failed: '.$e->getMessage());
    http_response_code(500);
    exit('تعذر الاتصال بقاعدة البيانات. راجع متغيرات اتصال Supabase في إعدادات الاستضافة.');
}
function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = (string)($_POST['csrf'] ?? '');
        if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
            http_response_code(419); exit('انتهت صلاحية النموذج. ارجع وحاول مرة أخرى.');
        }
    }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    if (!current_user()) { header('Location: /login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if ((current_user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('غير مسموح لك بالدخول لهذه الصفحة.'); }
}
function setting(PDO $pdo, string $key, string $default=''): string {
    $s = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
    $s->execute([$key]); $v = $s->fetchColumn();
    return $v === false ? $default : (string)$v;
}
function price_sdg(array $service, string $usdRate): float {
    return $service['currency'] === 'USD' ? (float)$service['price'] * (float)$usdRate : (float)$service['price'];
}
function money(float $amount): string { return number_format($amount, 2, '.', ','); }
function wallet_balance(PDO $pdo, int $userId): float {
    $st=$pdo->prepare('SELECT wallet_balance FROM users WHERE id=?');
    $st->execute([$userId]); return (float)($st->fetchColumn() ?: 0);
}
function app_header(string $title=''): void {
    global $config;
    $user = current_user();
    $safeTitle = h($title ?: $config['site_name']);
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#08090c"><meta name="description" content="Hani Software — منصة خدمات الهواتف والسوفت وير والتراخيص والمنتجات الرقمية."><title>'.$safeTitle.' | Hani Software</title><link rel="icon" href="/assets/hani-logo.svg" type="image/svg+xml"><link rel="stylesheet" href="/assets/style.css"></head><body><header class="top"><a class="brand" href="/index.php"><img src="/assets/hani-logo.svg" alt="Hani Software" style="width:205px;max-width:42vw;height:auto"></a><nav><a href="/index.php">الرئيسية</a><a href="/services.php">الخدمات</a>';
    if ($user) {
        echo '<a href="/account.php">حسابي</a>';
        if ($user['role']==='admin') echo '<a href="/admin/index.php">لوحة الأدمن</a>';
        echo '<a href="/logout.php">خروج</a>';
    } else echo '<a href="/login.php">دخول</a><a href="/register.php">حساب جديد</a>';
    echo '<a href="/topup.php">المحفظة</a></nav></header><main class="wrap">';
}
function app_footer(): void { echo '</main><footer>© '.date('Y').' Hani Software — خدمات السوفت وير وصيانة الهواتف</footer></body></html>'; }
