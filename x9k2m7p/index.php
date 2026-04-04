<?php
require_once __DIR__ . '/../config.php';
setSecurityHeaders();

$page = $_GET['page'] ?? 'login';
$error = '';
$success = '';

initSecureSession();

// Logout
if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login&msg=loggedout');
    exit;
}

// Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } elseif (!checkLoginRateLimit()) {
        $error = 'Too many attempts. Try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $input_hash = crypt($password, ADMIN_PASS_HASH);
        if ($username === ADMIN_USER && hash_equals(ADMIN_PASS_HASH, $input_hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['admin_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['admin_time'] = time();
            clearLoginAttempts();
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            recordLoginAttempt();
            $error = 'Invalid credentials.';
            sleep(2);
        }
    }
}

// Save content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'save') {
    requireAdmin();
    validateSession();
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        header('Location: index.php?page=dashboard&error=csrf');
        exit;
    }
    $data = [];
    foreach ($_POST as $key => $val) {
        if ($key === 'csrf_token') continue;
        $data[$key] = sanitize($val);
    }
    // Logo upload
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['image/png','image/jpeg','image/gif','image/webp','image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed) && $_FILES['logo']['size'] <= MAX_UPLOAD_SIZE) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $filename)) {
                $data['logo'] = 'assets/uploads/' . $filename;
            }
        }
    }
    saveContent($data);
    header('Location: index.php?page=dashboard&msg=saved');
    exit;
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'changepass') {
    requireAdmin();
    validateSession();
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
        $page = 'dashboard';
    } else {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 8) {
            $error = 'Password min 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $new_hash = crypt($new, '$5$rounds=535000$' . bin2hex(random_bytes(8)) . '$');
            $config_file = __DIR__ . '/../config.php';
            $config = file_get_contents($config_file);
            $config = preg_replace(
                "/define\('ADMIN_PASS_HASH', '.*?'\);/",
                "define('ADMIN_PASS_HASH', '" . $new_hash . "');",
                $config
            );
            file_put_contents($config_file, $config);
            $success = 'Password changed!';
        }
        $page = 'dashboard';
    }
}

// Delete inquiry
if ($page === 'deleteinq' && isset($_GET['id'])) {
    requireAdmin();
    validateSession();
    $inquiries = getInquiries();
    $inquiries = array_filter($inquiries, fn($i) => $i['id'] !== $_GET['id']);
    file_put_contents(INQUIRIES_FILE, json_encode(array_values($inquiries)));
    header('Location: index.php?page=inquiries&msg=deleted');
    exit;
}

// Mark inquiry read
if ($page === 'readinq' && isset($_GET['id'])) {
    requireAdmin();
    validateSession();
    $inquiries = getInquiries();
    foreach ($inquiries as &$i) {
        if ($i['id'] === $_GET['id']) $i['read'] = true;
    }
    file_put_contents(INQUIRIES_FILE, json_encode(array_values($inquiries)));
    header('Location: index.php?page=inquiries');
    exit;
}

$msg = $_GET['msg'] ?? '';
$csrf = generateCSRF();
$content = getContent();

if (isAdmin()) validateSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOIN Admin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; min-height: 100vh; }

        .login-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem; }
        .login-box { background: #141414; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 400px; }
        .login-box h1 { text-align: center; margin-bottom: 0.5rem; font-size: 1.5rem; }
        .login-box .sub { text-align: center; color: #888; margin-bottom: 2rem; font-size: 0.9rem; }

        .fg { margin-bottom: 1rem; }
        .fg label { display: block; margin-bottom: 0.3rem; font-size: 0.85rem; color: #aaa; }
        .fg input, .fg textarea, .fg select { width: 100%; padding: 0.7rem 1rem; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 0.9rem; outline: none; transition: border-color 0.3s; }
        .fg input:focus, .fg textarea:focus { border-color: #00ff88; }
        .fg textarea { resize: vertical; min-height: 80px; font-family: 'Inter', sans-serif; }

        .btn { padding: 0.7rem 1.5rem; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; font-size: 0.9rem; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-p { background: #00ff88; color: #000; }
        .btn-p:hover { box-shadow: 0 5px 20px rgba(0,255,136,0.3); }
        .btn-d { background: #ff4444; color: #fff; }
        .btn-s { background: #333; color: #fff; }
        .btn-s:hover { background: #444; }
        .btn-w { background: transparent; color: #00ff88; border: 1px solid #00ff88; }

        .alert { padding: 0.8rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; }
        .a-e { background: rgba(255,68,68,0.1); border: 1px solid #ff4444; color: #ff4444; }
        .a-s { background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88; }

        /* Dashboard layout */
        .dash { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: #111; border-right: 1px solid #222; padding: 1.5rem 0; flex-shrink: 0; }
        .sidebar .brand { padding: 0 1.5rem; font-size: 1.2rem; font-weight: 900; color: #00ff88; margin-bottom: 2rem; }
        .sidebar .nav a { display: flex; align-items: center; gap: 0.7rem; padding: 0.8rem 1.5rem; color: #888; text-decoration: none; font-size: 0.9rem; transition: all 0.2s; }
        .sidebar .nav a:hover, .sidebar .nav a.active { color: #00ff88; background: rgba(0,255,136,0.05); }
        .sidebar .nav .unread-badge { background: #ff4444; color: #fff; padding: 0.1rem 0.5rem; border-radius: 50px; font-size: 0.7rem; margin-left: auto; }

        .main { flex: 1; padding: 2rem; overflow-y: auto; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .main-header h2 { font-size: 1.5rem; }
        .main-header .actions { display: flex; gap: 0.5rem; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid .full { grid-column: 1 / -1; }
        .form-actions { margin-top: 1.5rem; display: flex; gap: 1rem; }

        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background: #141414; border: 1px solid #222; border-radius: 12px; padding: 1.5rem; }
        .card .val { font-size: 2rem; font-weight: 900; color: #00ff88; }
        .card .lbl { font-size: 0.85rem; color: #888; margin-top: 0.3rem; }

        /* Inquiry list */
        .inq-list { display: flex; flex-direction: column; gap: 0.8rem; }
        .inq-item { background: #141414; border: 1px solid #222; border-radius: 12px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; }
        .inq-item.unread { border-color: #00ff88; }
        .inq-item .info h3 { font-size: 1rem; margin-bottom: 0.3rem; }
        .inq-item .info p { font-size: 0.85rem; color: #888; }
        .inq-item .meta { text-align: right; font-size: 0.8rem; color: #666; }
        .inq-item .actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }

        /* Security table */
        .sec-table { width: 100%; border-collapse: collapse; }
        .sec-table td { padding: 0.6rem 1rem; border-bottom: 1px solid #222; font-size: 0.9rem; }
        .sec-table td:first-child { color: #888; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .form-grid { grid-template-columns: 1fr; }
            .dash { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php if (!isAdmin()): ?>
<!-- LOGIN -->
<div class="login-wrapper">
    <div class="login-box">
        <h1>🪙 KOIN Admin</h1>
        <p class="sub">MCaaS Dashboard</p>
        <?php if ($error): ?><div class="alert a-e"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($msg === 'loggedout'): ?><div class="alert a-s">Logged out.</div><?php endif; ?>
        <form method="POST" action="index.php?page=login" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="fg"><label>Username</label><input type="text" name="username" required autocomplete="off"></div>
            <div class="fg"><label>Password</label><input type="password" name="password" required autocomplete="off"></div>
            <button type="submit" class="btn btn-p" style="width:100%">Login</button>
        </form>
    </div>
</div>

<?php else: requireAdmin(); ?>
<!-- DASHBOARD -->
<div class="dash">
    <div class="sidebar">
        <div class="brand">🪙 KOIN</div>
        <div class="nav">
            <a href="index.php?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>">📊 Dashboard</a>
            <a href="index.php?page=brand" class="<?= $page==='brand'?'active':'' ?>">🎨 Brand</a>
            <a href="index.php?page=packages" class="<?= $page==='packages'?'active':'' ?>">💰 Packages</a>
            <a href="index.php?page=links" class="<?= $page==='links'?'active':'' ?>">🔗 Links</a>
            <a href="index.php?page=inquiries" class="<?= $page==='inquiries'?'active':'' ?>">
                📩 Inquiries
                <?php $unread = count(array_filter(getInquiries(), fn($i) => !$i['read'])); ?>
                <?php if ($unread > 0): ?><span class="unread-badge"><?= $unread ?></span><?php endif; ?>
            </a>
            <a href="index.php?page=security" class="<?= $page==='security'?'active':'' ?>">🔒 Security</a>
            <a href="index.php?page=logout" style="margin-top:2rem;color:#ff4444">🚪 Logout</a>
        </div>
    </div>

    <div class="main">
        <?php if ($error): ?><div class="alert a-e"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($msg === 'saved'): ?><div class="alert a-s">✅ Saved!</div><?php endif; ?>
        <?php if ($msg === 'deleted'): ?><div class="alert a-s">✅ Deleted.</div><?php endif; ?>
        <?php if ($success): ?><div class="alert a-s"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php
        $inquiries = getInquiries();
        $unread_count = count(array_filter($inquiries, fn($i) => !$i['read']));
        $total_inq = count($inquiries);

        switch ($page):
        case 'dashboard': ?>
        <div class="main-header"><h2>📊 Dashboard</h2><a href="../" target="_blank" class="btn btn-s">👁 View Site</a></div>
        <div class="cards">
            <div class="card"><div class="val"><?= $total_inq ?></div><div class="lbl">Total Inquiries</div></div>
            <div class="card"><div class="val"><?= $unread_count ?></div><div class="lbl">Unread</div></div>
            <div class="card"><div class="val">$<?= htmlspecialchars($content['pkg_basic_price']) ?></div><div class="lbl">Basic Price</div></div>
            <div class="card"><div class="val">$<?= htmlspecialchars($content['pkg_premium_price']) ?></div><div class="lbl">Premium Price</div></div>
        </div>
        <?php if ($unread_count > 0): ?>
        <h3 style="margin-bottom:1rem">📩 Recent Unread</h3>
        <div class="inq-list">
            <?php foreach (array_filter($inquiries, fn($i) => !$i['read']) as $inq): ?>
            <div class="inq-item unread">
                <div class="info">
                    <h3><?= htmlspecialchars($inq['name']) ?> — <?= htmlspecialchars($inq['package']) ?></h3>
                    <p>@<?= htmlspecialchars($inq['telegram']) ?>: <?= htmlspecialchars(substr($inq['message'], 0, 100)) ?><?= strlen($inq['message']) > 100 ? '...' : '' ?></p>
                </div>
                <div class="meta">
                    <div><?= $inq['date'] ?></div>
                    <div class="actions">
                        <a href="index.php?page=readinq&id=<?= $inq['id'] ?>" class="btn btn-s" style="padding:0.3rem 0.8rem">✓ Read</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php break; ?>

        <?php case 'brand': ?>
        <form method="POST" action="index.php?page=save" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="main-header"><h2>🎨 Brand Settings</h2><button type="submit" class="btn btn-p">💾 Save</button></div>
            <div class="form-grid">
                <div class="fg"><label>Brand Name</label><input type="text" name="brand_name" value="<?= htmlspecialchars($content['brand_name']) ?>"></div>
                <div class="fg"><label>Hero Badge</label><input type="text" name="hero_badge" value="<?= htmlspecialchars($content['hero_badge']) ?>"></div>
                <div class="fg full"><label>Tagline</label><input type="text" name="brand_tagline" value="<?= htmlspecialchars($content['brand_tagline']) ?>"></div>
                <div class="fg full"><label>Description</label><textarea name="brand_description"><?= htmlspecialchars($content['brand_description']) ?></textarea></div>
                <div class="fg"><label>Stats: Tokens Launched</label><input type="text" name="stats_tokens" value="<?= htmlspecialchars($content['stats_tokens']) ?>"></div>
                <div class="fg"><label>Stats: Delivery Time</label><input type="text" name="stats_delivery" value="<?= htmlspecialchars($content['stats_delivery']) ?>"></div>
                <div class="fg"><label>Primary Color</label><input type="color" name="primary_color" value="<?= htmlspecialchars($content['primary_color']) ?>"></div>
                <div class="fg"><label>Accent Color</label><input type="color" name="accent_color" value="<?= htmlspecialchars($content['accent_color']) ?>"></div>
                <div class="fg"><label>Logo Upload</label><input type="file" name="logo" accept="image/*"><?php if ($content['logo']): ?><img src="../<?= $content['logo'] ?>" style="max-height:50px;margin-top:0.5rem;border-radius:8px"><?php endif; ?></div>
            </div>
        </form>
        <?php break; ?>

        <?php case 'packages': ?>
        <form method="POST" action="index.php?page=save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="main-header"><h2>💰 Package Pricing</h2><button type="submit" class="btn btn-p">💾 Save</button></div>
            <h3 style="margin-bottom:1rem;color:#cd7f32">🥉 Basic</h3>
            <div class="form-grid" style="margin-bottom:2rem">
                <div class="fg"><label>Price ($)</label><input type="number" name="pkg_basic_price" value="<?= htmlspecialchars($content['pkg_basic_price']) ?>"></div>
                <div class="fg"><label>Description</label><input type="text" name="pkg_basic_desc" value="<?= htmlspecialchars($content['pkg_basic_desc']) ?>"></div>
                <div class="fg full"><label>Features (one per line)</label><textarea name="pkg_basic_features" rows="6"><?= htmlspecialchars($content['pkg_basic_features']) ?></textarea></div>
            </div>
            <h3 style="margin-bottom:1rem;color:#c0c0c0">🥈 Standard</h3>
            <div class="form-grid" style="margin-bottom:2rem">
                <div class="fg"><label>Price ($)</label><input type="number" name="pkg_standard_price" value="<?= htmlspecialchars($content['pkg_standard_price']) ?>"></div>
                <div class="fg"><label>Description</label><input type="text" name="pkg_standard_desc" value="<?= htmlspecialchars($content['pkg_standard_desc']) ?>"></div>
                <div class="fg full"><label>Features (one per line)</label><textarea name="pkg_standard_features" rows="8"><?= htmlspecialchars($content['pkg_standard_features']) ?></textarea></div>
            </div>
            <h3 style="margin-bottom:1rem;color:#ffd700">🥇 Premium</h3>
            <div class="form-grid">
                <div class="fg"><label>Price ($)</label><input type="number" name="pkg_premium_price" value="<?= htmlspecialchars($content['pkg_premium_price']) ?>"></div>
                <div class="fg"><label>Description</label><input type="text" name="pkg_premium_desc" value="<?= htmlspecialchars($content['pkg_premium_desc']) ?>"></div>
                <div class="fg full"><label>Features (one per line)</label><textarea name="pkg_premium_features" rows="8"><?= htmlspecialchars($content['pkg_premium_features']) ?></textarea></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-p">💾 Save All Packages</button></div>
        </form>
        <?php break; ?>

        <?php case 'links': ?>
        <form method="POST" action="index.php?page=save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="main-header"><h2>🔗 Links</h2><button type="submit" class="btn btn-p">💾 Save</button></div>
            <div class="form-grid">
                <div class="fg full"><label>Telegram Link</label><input type="url" name="telegram_link" value="<?= htmlspecialchars($content['telegram_link']) ?>"></div>
                <div class="fg full"><label>Twitter / X Link</label><input type="url" name="twitter_link" value="<?= htmlspecialchars($content['twitter_link']) ?>"></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-p">💾 Save</button></div>
        </form>
        <?php break; ?>

        <?php case 'inquiries': ?>
        <div class="main-header"><h2>📩 Inquiries (<?= $total_inq ?>)</h2></div>
        <?php if (empty($inquiries)): ?>
            <p style="color:#888;text-align:center;padding:3rem">No inquiries yet.</p>
        <?php else: ?>
        <div class="inq-list">
            <?php foreach (array_reverse($inquiries) as $inq): ?>
            <div class="inq-item <?= !$inq['read'] ? 'unread' : '' ?>">
                <div class="info">
                    <h3><?= htmlspecialchars($inq['name']) ?> — <span style="color:#00ff88"><?= htmlspecialchars($inq['package']) ?></span> <?= !$inq['read'] ? '🆕' : '' ?></h3>
                    <p><strong>Telegram:</strong> @<?= htmlspecialchars($inq['telegram']) ?><?php if (!empty($inq['lp_lock'])): ?> &nbsp;|&nbsp; <strong>LP Lock:</strong> <span style="color:#00ff88"><?= htmlspecialchars($inq['lp_lock']) ?></span><?php endif; ?></p>
                    <p style="margin-top:0.5rem"><?= nl2br(htmlspecialchars($inq['message'])) ?></p>
                </div>
                <div class="meta">
                    <div><?= $inq['date'] ?></div>
                    <div class="actions">
                        <?php if (!$inq['read']): ?>
                        <a href="index.php?page=readinq&id=<?= $inq['id'] ?>" class="btn btn-s" style="padding:0.3rem 0.8rem">✓ Read</a>
                        <?php endif; ?>
                        <a href="index.php?page=deleteinq&id=<?= $inq['id'] ?>" class="btn btn-d" style="padding:0.3rem 0.8rem" onclick="return confirm('Delete?')">✕</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php break; ?>

        <?php case 'security': ?>
        <div class="main-header"><h2>🔒 Security</h2></div>
        <div style="max-width:500px">
            <h3 style="margin-bottom:1rem;color:#00ff88">🔑 Change Password</h3>
            <form method="POST" action="index.php?page=changepass">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="fg"><label>New Password (min 8)</label><input type="password" name="new_password" required minlength="8"></div>
                <div class="fg"><label>Confirm</label><input type="password" name="confirm_password" required></div>
                <button type="submit" class="btn btn-p">Update Password</button>
            </form>
            <h3 style="margin:2rem 0 1rem;color:#00ff88">🛡️ Security Status</h3>
            <table class="sec-table">
                <tr><td>CSRF Protection</td><td>✅</td></tr>
                <tr><td>Rate Limiting</td><td>✅ <?= MAX_LOGIN_ATTEMPTS ?> attempts/<?= LOGIN_LOCKOUT_MINUTES ?>min</td></tr>
                <tr><td>Session Timeout</td><td>✅ 30 min</td></tr>
                <tr><td>Session Hijack Check</td><td>✅ IP + UA</td></tr>
                <tr><td>Password Hash</td><td>✅ SHA-256 crypt</td></tr>
                <tr><td>Brute Force Delay</td><td>✅ 2 sec</td></tr>
                <tr><td>Security Headers</td><td>✅ CSP, X-Frame, XSS</td></tr>
                <tr><td>Content-Security-Policy</td><td>✅ Active</td></tr>
                <tr><td>Input Sanitization</td><td>✅ XSS safe</td></tr>
                <tr><td>File Upload MIME Check</td><td>✅</td></tr>
                <tr><td>Admin Path</td><td>🔒 Hidden</td></tr>
                <tr><td>HTTPS Cookies</td><td>✅</td></tr>
            </table>
        </div>
        <?php break; ?>

        <?php endswitch; ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>