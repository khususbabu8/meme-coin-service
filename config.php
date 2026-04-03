<?php
/**
 * MCaaS - Meme Coin as a Service
 * Configuration & Security
 * KOIN Agent 🪙
 */

// === APP ===
define('APP_NAME', 'KOIN');
define('APP_VERSION', '2.0');

// === SECURITY KEYS ===
define('APP_SECRET', 'mc44s_s3rv1c3_s3cur3_2026!Xk9#');
define('SESSION_NAME', 'koin_mcass_sess');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('CSRF_TOKEN_EXPIRY', 3600);
define('SESSION_TIMEOUT', 1800); // 30 min

// === ADMIN ===
// Hidden admin path
define('ADMIN_PATH', 'x9k2m7p');
define('ADMIN_USER', 'koinadmin');
// Default password: KoinAdmin2026! — CHANGE after first login
define('ADMIN_PASS_HASH', '$5$rounds=535000$RYe9BV78zvw71zwW$colyn0NaG8gMJVJY6yv2bkDCnsfxScicHuAOXbHpIb6');

// === FILES ===
define('CONTENT_FILE', __DIR__ . '/data/content.json');
define('INQUIRIES_FILE', __DIR__ . '/data/inquiries.json');
define('UPLOAD_DIR', __DIR__ . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);

// === SECURITY FUNCTIONS ===
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self';");
}

function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_name(SESSION_NAME);
        session_start();
    }
}

function generateCSRF() {
    if (empty($_SESSION['csrf_token']) || time() > ($_SESSION['csrf_token_expiry'] ?? 0)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + CSRF_TOKEN_EXPIRY;
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize($input) {
    if (is_array($input)) return array_map('sanitize', $input);
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function checkLoginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/koin_mcaas_' . md5($ip);
    $attempts = [];
    if (file_exists($file)) $attempts = json_decode(file_get_contents($file), true) ?: [];
    $cutoff = time() - (LOGIN_LOCKOUT_MINUTES * 60);
    $attempts = array_filter($attempts, fn($t) => $t > $cutoff);
    return count($attempts) < MAX_LOGIN_ATTEMPTS;
}

function recordLoginAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/koin_mcaas_' . md5($ip);
    $attempts = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    $attempts[] = time();
    file_put_contents($file, json_encode($attempts));
}

function clearLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/koin_mcaas_' . md5($ip);
    if (file_exists($file)) unlink($file);
}

// === CONTENT MANAGEMENT ===
function getContent() {
    $defaults = [
        'brand_name' => 'KOIN',
        'brand_tagline' => 'Launch Your Own Meme Coin in 24 Hours',
        'brand_description' => 'Full service — token deploy, liquidity pool, website, marketing. You bring the idea, we build everything. No coding needed.',
        'hero_badge' => '🪙 #1 Solana Meme Coin Service',
        'telegram_link' => 'https://t.me/',
        'twitter_link' => 'https://twitter.com/',
        'stats_tokens' => '50+',
        'stats_delivery' => '24h',
        'stats_satisfaction' => '100%',
        'pkg_basic_price' => '300',
        'pkg_basic_desc' => 'Token deployed & ready to launch',
        'pkg_basic_features' => "Deploy SPL Token on Solana\nCustom name, ticker, supply\nUpload logo + metadata\nRevoke mint & freeze authority\nLanding page website\nDexscreener listing guide",
        'pkg_standard_price' => '700',
        'pkg_standard_desc' => 'Everything you need to go live',
        'pkg_standard_features' => "Everything in Basic\nLP Setup on Raydium\nLP Lock (6-12 months)\nTG Group + bots setup\nTwitter/X account setup\nTokenomics document\nRugCheck verification",
        'pkg_premium_price' => '1500',
        'pkg_premium_desc' => 'Full premium — custom everything',
        'pkg_premium_features' => "Everything in Standard\nCustom smart contract features\nCustom website design (not template)\nAdmin dashboard\nMarketing strategy document\nKOL & shill group contacts\nDexscreener profile optimization\n1 week post-launch support",
        'about_text' => 'Professional meme coin deployment service on Solana.',
        'logo' => '',
        'primary_color' => '#00ff88',
        'accent_color' => '#a855f7',
    ];

    if (file_exists(CONTENT_FILE)) {
        $saved = json_decode(file_get_contents(CONTENT_FILE), true);
        if (is_array($saved)) return array_merge($defaults, $saved);
    }
    return $defaults;
}

function saveContent($data) {
    $dir = dirname(CONTENT_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $allowed = [
        'brand_name','brand_tagline','brand_description','hero_badge',
        'telegram_link','twitter_link',
        'stats_tokens','stats_delivery','stats_satisfaction',
        'pkg_basic_price','pkg_basic_desc','pkg_basic_features',
        'pkg_standard_price','pkg_standard_desc','pkg_standard_features',
        'pkg_premium_price','pkg_premium_desc','pkg_premium_features',
        'about_text','logo','primary_color','accent_color'
    ];
    $clean = [];
    foreach ($allowed as $key) {
        if (isset($data[$key])) $clean[$key] = $data[$key];
    }
    return file_put_contents(CONTENT_FILE, json_encode($clean, JSON_PRETTY_PRINT));
}

function getInquiries() {
    if (file_exists(INQUIRIES_FILE)) {
        $data = json_decode(file_get_contents(INQUIRIES_FILE), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveInquiry($name, $telegram, $package, $message) {
    $dir = dirname(INQUIRIES_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $inquiries = getInquiries();
    $inquiries[] = [
        'id' => uniqid('inq_'),
        'name' => $name,
        'telegram' => $telegram,
        'package' => $package,
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'date' => date('Y-m-d H:i:s'),
        'read' => false
    ];
    return file_put_contents(INQUIRIES_FILE, json_encode($inquiries, JSON_PRETTY_PRINT));
}

// === AUTH ===
function isAdmin() {
    initSecureSession();
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function validateSession() {
    if (!isAdmin()) return;
    // Session hijacking check
    if ($_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '') ||
        $_SESSION['admin_ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_destroy();
        header('Location: index.php?page=login&error=session');
        exit;
    }
    // Timeout check
    if (time() - ($_SESSION['admin_time'] ?? 0) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: index.php?page=login&error=timeout');
        exit;
    }
}
