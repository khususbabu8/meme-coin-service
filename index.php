<?php
require_once __DIR__ . '/config.php';
setSecurityHeaders();
$content = getContent();

$form_error = '';
$form_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'inquiry') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $form_error = 'Invalid request.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $telegram = sanitize($_POST['telegram'] ?? '');
        $package = sanitize($_POST['package'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $lpLock = sanitize($_POST['lp_lock'] ?? 'No Lock (Free)');
        if (empty($name) || empty($telegram) || empty($package)) {
            $form_error = 'Please fill all required fields.';
        } elseif (strlen($name) > 100 || strlen($telegram) > 100 || strlen($message) > 2000) {
            $form_error = 'Input too long.';
        } else {
            saveInquiry($name, $telegram, $package, $message, $lpLock);
            $form_success = '✅ Inquiry sent! We\'ll contact you on Telegram soon.';
        }
    }
}
$csrf = generateCSRF();
$c = $content;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($c['brand_name']) ?> — Meme Coin as a Service</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="#" class="logo"><span>🪙</span> <?= htmlspecialchars($c['brand_name']) ?></a>
        <div class="links">
            <a href="#how">How It Works</a>
            <a href="#pricing">Pricing</a>
            <a href="#features">Features</a>
            <a href="#contact">Order</a>
            <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p">💬 Order Now</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="badge"><?= htmlspecialchars($c['hero_badge']) ?></div>
            <h1><?= htmlspecialchars($c['brand_tagline']) ?></h1>
            <p><?= htmlspecialchars($c['brand_description']) ?></p>
            <div class="hero-btns">
                <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p">💬 Order via Telegram</a>
                <a href="#pricing" class="btn btn-s">💰 See Pricing</a>
            </div>
            <div class="hero-stats">
                <div class="stat"><div class="num"><?= htmlspecialchars($c['stats_tokens']) ?></div><div class="lbl">Tokens Launched</div></div>
                <div class="stat"><div class="num"><?= htmlspecialchars($c['stats_delivery']) ?></div><div class="lbl">Delivery Time</div></div>
                <div class="stat"><div class="num"><?= htmlspecialchars($c['stats_satisfaction']) ?></div><div class="lbl">Satisfaction</div></div>
            </div>
        </div>
    </section>

    <section class="sec" id="how">
        <div class="sec-lbl">Process</div>
        <h2>How It <span class="g">Works</span></h2>
        <p class="sub">From idea to live token in 4 simple steps</p>
        <div class="steps-grid">
            <div class="step-card"><div class="num">1</div><h3>📝 Tell Us Your Idea</h3><p>Message us with your token name, ticker, supply, and any special features you want.</p></div>
            <div class="step-card"><div class="num">2</div><h3>💸 Pay & Fund LP</h3><p>Choose your package, pay the service fee, and send SOL for liquidity pool setup.</p></div>
            <div class="step-card"><div class="num">3</div><h3>🚀 We Build Everything</h3><p>We deploy token, create LP on Raydium, build website, setup Telegram group — all done for you.</p></div>
            <div class="step-card"><div class="num">4</div><h3>🎉 Token Goes Live!</h3><p>Your token on Dexscreener, tradeable on Raydium. You focus on marketing, we handle the tech.</p></div>
        </div>
    </section>

    <section class="sec" id="pricing">
        <div class="sec-lbl">Pricing</div>
        <h2>Choose Your <span class="g">Package</span></h2>
        <p class="sub">From basic launch to full premium service</p>
        <div class="pricing-grid">
            <?php
            $pkgs = [
                ['tier'=>'🥉 Basic','price'=>$c['pkg_basic_price'],'desc'=>$c['pkg_basic_desc'],'features'=>$c['pkg_basic_features'],'btn'=>'btn-d','pop'=>false],
                ['tier'=>'🥈 Standard','price'=>$c['pkg_standard_price'],'desc'=>$c['pkg_standard_desc'],'features'=>$c['pkg_standard_features'],'btn'=>'btn-p','pop'=>true],
                ['tier'=>'🥇 Premium','price'=>$c['pkg_premium_price'],'desc'=>$c['pkg_premium_desc'],'features'=>$c['pkg_premium_features'],'btn'=>'btn-a','pop'=>false],
            ];
            foreach ($pkgs as $pkg):
            ?>
            <div class="price-card<?= $pkg['pop'] ? ' popular' : '' ?>">
                <?php if ($pkg['pop']): ?><div class="pop-badge">MOST POPULAR</div><?php endif; ?>
                <div class="tier"><?= $pkg['tier'] ?></div>
                <div class="price">$<?= htmlspecialchars($pkg['price']) ?></div>
                <div class="desc"><?= htmlspecialchars($pkg['desc']) ?></div>
                <ul class="feat-list">
                    <?php foreach (explode("\n", $pkg['features']) as $f): 
                        $f = trim($f);
                        if (empty($f)) continue;
                        $isAddon = (strpos($f, '+ Add-on') === 0) || (strpos($f, '+ Lock') === 0) || (strpos($f, '+ Burn') === 0) || (strpos($f, '🔒') === 0);
                    ?>
                    <li<?= $isAddon ? ' class="addon"' : '' ?>>
                        <span class="ck"><?= $isAddon ? '➕' : '✅' ?></span> 
                        <?php if ($isAddon): ?>
                            <span class="addon-text"><?= htmlspecialchars($f) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($f) ?>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="#contact" class="btn <?= $pkg['btn'] ?>" style="width:100%;justify-content:center">Get Started</a>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="text-align:center;color:var(--td);font-size:0.85rem;margin-top:2rem">💡 <strong>Client provides SOL for LP</strong> — we handle setup. LP Lock optional, pilih sendiri!</p>
    </section>

    <section class="sec" id="lp-options">
        <div class="sec-lbl">Add-ons</div>
        <h2>LP Lock <span class="g">Options</span></h2>
        <p class="sub">Pilih sesuai budget & trust level. Client tentukan sendiri.</p>
        <div class="lp-grid">
            <div class="lp-card">
                <div class="lp-ic">🔓</div>
                <h3>No Lock</h3>
                <div class="lp-price">Free</div>
                <p>LP tetap bisa diwithdraw kapan aja. Cocok buat testing atau project kecil.</p>
            </div>
            <div class="lp-card lp-pop">
                <div class="lp-badge">RECOMMENDED</div>
                <div class="lp-ic">🔒</div>
                <h3>Lock 6 Months</h3>
                <div class="lp-price">+$100</div>
                <p>Investor percaya. Dexscreener badge locked. RugCheck score naik.</p>
            </div>
            <div class="lp-card">
                <div class="lp-ic">🔒</div>
                <h3>Lock 12 Months</h3>
                <div class="lp-price">+$150</div>
                <p>Max trust. Best untuk project jangka panjang. PinkSale certificate.</p>
            </div>
            <div class="lp-card">
                <div class="lp-ic">🔥</div>
                <h3>Burn LP</h3>
                <div class="lp-price">Free</div>
                <p>Permanent — LP nggak bisa diambil lagi selamanya. Maximum anti-rug signal.</p>
            </div>
        </div>
    </section>

    <section class="sec" id="features">
        <div class="sec-lbl">Why Us</div>
        <h2>Why Choose <span class="g"><?= htmlspecialchars($c['brand_name']) ?></span></h2>
        <div class="feat-grid">
            <div class="feat-card"><div class="ic">🔒</div><h3>Secure by Default</h3><p>Mint & freeze revoked. LP locked. RugCheck verified.</p></div>
            <div class="feat-card"><div class="ic">⚡</div><h3>Fast Delivery</h3><p>Basic: 24h. Standard & Premium: 48h.</p></div>
            <div class="feat-card"><div class="ic">🎨</div><h3>Professional Website</h3><p>Modern, responsive landing page with trust indicators.</p></div>
            <div class="feat-card"><div class="ic">🛡️</div><h3>Anti-Rugpull Setup</h3><p>LP lock, RugCheck score, transparent tokenomics.</p></div>
            <div class="feat-card"><div class="ic">💬</div><h3>Full Support</h3><p>Telegram support. Premium gets 1 week post-launch.</p></div>
            <div class="feat-card"><div class="ic">🔗</div><h3>Multi-Chain</h3><p>Solana, Base, Ethereum, BSC available.</p></div>
        </div>
    </section>

    <section class="sec" id="contact">
        <div class="sec-lbl">Order</div>
        <h2>Ready to <span class="g">Launch?</span></h2>
        <p class="sub">Fill the form and we'll reach out on Telegram</p>
        <div class="contact-box">
            <?php if ($form_error): ?><div class="form-alert form-error"><?= htmlspecialchars($form_error) ?></div><?php endif; ?>
            <?php if ($form_success): ?><div class="form-alert form-success"><?= htmlspecialchars($form_success) ?></div><?php endif; ?>
            <form method="POST" action="#contact">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="inquiry">
                <div class="fg"><label>Name *</label><input type="text" name="name" required maxlength="100"></div>
                <div class="fg"><label>Telegram Username *</label><input type="text" name="telegram" placeholder="@username" required maxlength="100"></div>
                <div class="fg">
                    <label>Package *</label>
                    <select name="package" required>
                        <option value="">Select package...</option>
                        <option value="Basic ($<?= htmlspecialchars($c['pkg_basic_price']) ?>)">🥉 Basic — $<?= htmlspecialchars($c['pkg_basic_price']) ?></option>
                        <option value="Standard ($<?= htmlspecialchars($c['pkg_standard_price']) ?>)">🥈 Standard — $<?= htmlspecialchars($c['pkg_standard_price']) ?></option>
                        <option value="Premium ($<?= htmlspecialchars($c['pkg_premium_price']) ?>)">🥇 Premium — $<?= htmlspecialchars($c['pkg_premium_price']) ?></option>
                    </select>
                </div>
                <div class="fg">
                    <label>LP Lock Option</label>
                    <select name="lp_lock">
                        <option value="No Lock (Free)">🔓 No Lock — Free</option>
                        <option value="Lock 3 Months (+$50)">🔒 Lock 3 Months — +$50</option>
                        <option value="Lock 6 Months (+$100)" selected>🔒 Lock 6 Months — +$100</option>
                        <option value="Lock 12 Months (+$150)">🔒 Lock 12 Months — +$150</option>
                        <option value="Burn LP Permanent (Free)">🔥 Burn LP Permanent — Free</option>
                    </select>
                </div>
                <div class="fg"><label>Message</label><textarea name="message" rows="4" placeholder="Tell us about your token idea..." maxlength="2000"></textarea></div>
                <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">🚀 Submit Inquiry</button>
            </form>
        </div>
    </section>

    <section class="cta-sec">
        <h2>Or Chat Directly 🚀</h2>
        <p>Prefer Telegram? Click below.</p>
        <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p" style="font-size:1.1rem;padding:1rem 3rem">💬 Chat on Telegram</a>
    </section>

    <div class="footer">
        <p>🪙 <?= htmlspecialchars($c['brand_name']) ?> — Meme Coin as a Service</p>
        <p style="margin-top:0.5rem">© <?= date('Y') ?> | Not financial advice. DYOR.</p>
    </div>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const el = document.querySelector(a.getAttribute('href'));
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>