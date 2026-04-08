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
    <meta name="description" content="Launch your own meme coin on Solana. Full service — deploy, LP, website, marketing. No coding needed.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🪙</text></svg>">
</head>
<body>

<!-- Particle Canvas -->
<canvas id="particles"></canvas>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <a href="#" class="logo">
        <div class="logo-icon">🪙</div>
        <?= htmlspecialchars($c['brand_name']) ?>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
    <div class="links" id="navLinks">
        <a href="#how">How It Works</a>
        <a href="#pricing">Pricing</a>
        <a href="#features">Features</a>
        <a href="#contact">Order</a>
        <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p">💬 Order Now</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-content">
        <div class="badge">
            <span class="pulse-dot"></span>
            <?= htmlspecialchars($c['hero_badge']) ?>
        </div>
        <h1><?= htmlspecialchars($c['brand_tagline']) ?></h1>
        <p><?= htmlspecialchars($c['brand_description']) ?></p>
        <div class="hero-btns">
            <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p">💬 Order via Telegram</a>
            <a href="#pricing" class="btn btn-s">💰 See Pricing</a>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <div class="num" data-count="50">0+</div>
                <div class="lbl">Tokens Launched</div>
            </div>
            <div class="stat">
                <div class="num">24h</div>
                <div class="lbl">Delivery Time</div>
            </div>
            <div class="stat">
                <div class="num">100%</div>
                <div class="lbl">Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Ticker -->
<div class="ticker">
    <div class="ticker-inner">
        <span>🪙 <b>SOLANA</b> Primary Chain</span>
        <span>🔒 <b>ANTI-RUG</b> LP Locked</span>
        <span>⚡ <b>24H</b> Fast Delivery</span>
        <span>🛡️ <b>RUGCHECK</b> Verified</span>
        <span>🔗 <b>MULTI-CHAIN</b> Support</span>
        <span>🎨 <b>CUSTOM</b> Website</span>
        <span>💬 <b>24/7</b> Telegram Support</span>
        <span>🪙 <b>SOLANA</b> Primary Chain</span>
        <span>🔒 <b>ANTI-RUG</b> LP Locked</span>
        <span>⚡ <b>24H</b> Fast Delivery</span>
        <span>🛡️ <b>RUGCHECK</b> Verified</span>
        <span>🔗 <b>MULTI-CHAIN</b> Support</span>
        <span>🎨 <b>CUSTOM</b> Website</span>
        <span>💬 <b>24/7</b> Telegram Support</span>
    </div>
</div>

<!-- How It Works -->
<section class="sec" id="how">
    <div class="sec-lbl reveal">Process</div>
    <h2 class="reveal">How It <span class="g">Works</span></h2>
    <p class="sub reveal">From idea to live token in 4 simple steps</p>
    <div class="steps-grid">
        <div class="glass-card step-card reveal reveal-delay-1">
            <div class="step-num">1</div>
            <h3>📝 Tell Us Your Idea</h3>
            <p>Message us with your token name, ticker, supply, and any special features you want.</p>
        </div>
        <div class="glass-card step-card reveal reveal-delay-2">
            <div class="step-num">2</div>
            <h3>💸 Pay & Fund LP</h3>
            <p>Choose your package, pay the service fee, and send SOL for liquidity pool setup.</p>
        </div>
        <div class="glass-card step-card reveal reveal-delay-3">
            <div class="step-num">3</div>
            <h3>🚀 We Build Everything</h3>
            <p>Deploy token, create LP on Raydium, build website, setup Telegram — all done for you.</p>
        </div>
        <div class="glass-card step-card reveal reveal-delay-4">
            <div class="step-num">4</div>
            <h3>🎉 Token Goes Live!</h3>
            <p>Your token on Dexscreener, tradeable on Raydium. You focus on marketing, we handle the tech.</p>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="sec" id="pricing">
    <div class="sec-lbl reveal">Pricing</div>
    <h2 class="reveal">Choose Your <span class="g">Package</span></h2>
    <p class="sub reveal">From basic launch to full premium service</p>
    <div class="pricing-grid">
        <?php
        $pkgs = [
            ['tier'=>'🥉 Basic','price'=>$c['pkg_basic_price'],'desc'=>$c['pkg_basic_desc'],'features'=>$c['pkg_basic_features'],'btn'=>'btn-d','pop'=>false],
            ['tier'=>'🥈 Standard','price'=>$c['pkg_standard_price'],'desc'=>$c['pkg_standard_desc'],'features'=>$c['pkg_standard_features'],'btn'=>'btn-p','pop'=>true],
            ['tier'=>'🥇 Premium','price'=>$c['pkg_premium_price'],'desc'=>$c['pkg_premium_desc'],'features'=>$c['pkg_premium_features'],'btn'=>'btn-a','pop'=>false],
        ];
        foreach ($pkgs as $pkg):
        ?>
        <div class="glass-card price-card tilt-card<?= $pkg['pop'] ? ' popular' : '' ?> reveal">
            <?php if ($pkg['pop']): ?><div class="pop-badge">⭐ MOST POPULAR</div><?php endif; ?>
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
    <p style="text-align:center;color:var(--td);font-size:0.85rem;margin-top:2rem" class="reveal">
        💡 <strong style="color:var(--t)">Client provides SOL for LP</strong> — we handle setup. LP Lock optional!
    </p>
</section>

<!-- LP Lock Options -->
<section class="sec" id="lp-options">
    <div class="sec-lbl reveal">Add-ons</div>
    <h2 class="reveal">LP Lock <span class="g">Options</span></h2>
    <p class="sub reveal">Pilih sesuai budget & trust level. Client tentukan sendiri.</p>
    <div class="lp-grid">
        <div class="glass-card lp-card reveal reveal-delay-1">
            <div class="lp-ic">🔓</div>
            <h3>No Lock</h3>
            <div class="lp-price">Free</div>
            <p>LP tetap bisa diwithdraw kapan aja. Cocok buat testing atau project kecil.</p>
        </div>
        <div class="glass-card lp-card lp-pop reveal reveal-delay-2">
            <div class="lp-badge">⭐ RECOMMENDED</div>
            <div class="lp-ic">🔒</div>
            <h3>Lock 6 Months</h3>
            <div class="lp-price">+$100</div>
            <p>Investor percaya. Dexscreener badge locked. RugCheck score naik.</p>
        </div>
        <div class="glass-card lp-card reveal reveal-delay-3">
            <div class="lp-ic">🔒</div>
            <h3>Lock 12 Months</h3>
            <div class="lp-price">+$150</div>
            <p>Max trust. Best untuk project jangka panjang. PinkSale certificate.</p>
        </div>
        <div class="glass-card lp-card reveal reveal-delay-4">
            <div class="lp-ic">🔥</div>
            <h3>Burn LP</h3>
            <div class="lp-price">Free</div>
            <p>Permanent — LP nggak bisa diambil lagi selamanya. Maximum anti-rug signal.</p>
        </div>
    </div>
</section>

<!-- Features -->
<section class="sec" id="features">
    <div class="sec-lbl reveal">Why Us</div>
    <h2 class="reveal">Why Choose <span class="g"><?= htmlspecialchars($c['brand_name']) ?></span></h2>
    <p class="sub reveal">Professional, transparent, and secure</p>
    <div class="feat-grid">
        <div class="glass-card feat-card reveal reveal-delay-1">
            <div class="ic">🔒</div>
            <h3>Secure by Default</h3>
            <p>Mint & freeze revoked. LP locked via trusted platforms. RugCheck verified. Your investors can trust the token.</p>
        </div>
        <div class="glass-card feat-card reveal reveal-delay-2">
            <div class="ic">⚡</div>
            <h3>Fast Delivery</h3>
            <p>Basic: 24h. Standard & Premium: 48h. We don't waste your time.</p>
        </div>
        <div class="glass-card feat-card reveal reveal-delay-3">
            <div class="ic">🎨</div>
            <h3>Professional Website</h3>
            <p>Modern, responsive landing page with tokenomics, how to buy guide, and trust indicators.</p>
        </div>
        <div class="glass-card feat-card reveal reveal-delay-1">
            <div class="ic">🛡️</div>
            <h3>Anti-Rugpull Setup</h3>
            <p>LP lock, RugCheck score, transparent tokenomics — everything to prove legitimacy.</p>
        </div>
        <div class="glass-card feat-card reveal reveal-delay-2">
            <div class="ic">💬</div>
            <h3>Full Support</h3>
            <p>Telegram support throughout. Premium gets 1 week post-launch support.</p>
        </div>
        <div class="glass-card feat-card reveal reveal-delay-3">
            <div class="ic">🔗</div>
            <h3>Multi-Chain</h3>
            <p>Solana, Base, Ethereum, BSC — want to launch on multiple chains? We can do that.</p>
        </div>
    </div>
</section>

<!-- Contact / Order Form -->
<section class="sec" id="contact">
    <div class="sec-lbl reveal">Order</div>
    <h2 class="reveal">Ready to <span class="g">Launch?</span></h2>
    <p class="sub reveal">Fill the form and we'll reach out on Telegram</p>
    <div class="glass-card contact-box reveal">
        <?php if ($form_error): ?><div class="form-alert form-error"><?= htmlspecialchars($form_error) ?></div><?php endif; ?>
        <?php if ($form_success): ?><div class="form-alert form-success"><?= htmlspecialchars($form_success) ?></div><?php endif; ?>
        <form method="POST" action="#contact">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="inquiry">
            <div class="fg">
                <label>Name *</label>
                <input type="text" name="name" required maxlength="100" placeholder="Your name">
            </div>
            <div class="fg">
                <label>Telegram Username *</label>
                <input type="text" name="telegram" placeholder="@username" required maxlength="100">
            </div>
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
            <div class="fg">
                <label>Message</label>
                <textarea name="message" rows="4" placeholder="Tell us about your token idea..." maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn btn-p" style="width:100%;justify-content:center;font-size:1rem;padding:1rem">🚀 Submit Inquiry</button>
        </form>
    </div>
</section>

<!-- CTA -->
<section class="cta-sec reveal">
    <h2>Or Chat Directly 🚀</h2>
    <p>Prefer Telegram? Click below.</p>
    <a href="<?= htmlspecialchars($c['telegram_link']) ?>" target="_blank" class="btn btn-p" style="font-size:1.1rem;padding:1rem 3rem">💬 Chat on Telegram</a>
</section>

<!-- Footer -->
<div class="footer">
    <p>🪙 <?= htmlspecialchars($c['brand_name']) ?> — Meme Coin as a Service</p>
    <p style="margin-top:0.5rem">Professional token deployment on Solana</p>
    <p style="margin-top:0.5rem">© <?= date('Y') ?> | Not financial advice. DYOR.</p>
</div>

<script>
// ===== PARTICLE SYSTEM =====
(function() {
    const canvas = document.getElementById('particles');
    const ctx = canvas.getContext('2d');
    let particles = [];
    let mouse = { x: 0, y: 0 };
    let w, h;

    function resize() {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    document.addEventListener('mousemove', e => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });

    class Particle {
        constructor() {
            this.reset();
        }
        reset() {
            this.x = Math.random() * w;
            this.y = Math.random() * h;
            this.size = Math.random() * 2 + 0.5;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.speedY = (Math.random() - 0.5) * 0.3;
            this.opacity = Math.random() * 0.4 + 0.1;
            const colors = ['0,255,136', '168,85,247', '255,107,53'];
            this.color = colors[Math.floor(Math.random() * colors.length)];
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;

            // Mouse interaction
            const dx = mouse.x - this.x;
            const dy = mouse.y - this.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 150) {
                this.x -= dx * 0.005;
                this.y -= dy * 0.005;
                this.opacity = Math.min(this.opacity + 0.02, 0.6);
            }

            if (this.x < 0 || this.x > w || this.y < 0 || this.y > h) this.reset();
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${this.color},${this.opacity})`;
            ctx.fill();
        }
    }

    // Create particles
    const count = Math.min(80, Math.floor(w * h / 15000));
    for (let i = 0; i < count; i++) particles.push(new Particle());

    function connectParticles() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 120) {
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(0,255,136,${0.06 * (1 - dist / 120)})`;
                    ctx.lineWidth = 0.5;
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
    }

    function animate() {
        ctx.clearRect(0, 0, w, h);
        particles.forEach(p => { p.update(); p.draw(); });
        connectParticles();
        requestAnimationFrame(animate);
    }
    animate();
})();

// ===== NAVBAR SCROLL =====
const navbar = document.getElementById('navbar');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const st = window.scrollY;
    navbar.classList.toggle('scrolled', st > 50);
    lastScroll = st;
});

// ===== MOBILE NAV =====
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');

navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    navToggle.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
});

// Close nav on link click
navLinks.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
        navLinks.classList.remove('active');
        navToggle.textContent = '☰';
    });
});

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            const offset = 80;
            const y = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    });
});

// ===== SCROLL REVEAL =====
const reveals = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

reveals.forEach(el => revealObserver.observe(el));

// ===== TILT EFFECT =====
document.querySelectorAll('.tilt-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        const rotateX = (y - centerY) / centerY * -4;
        const rotateY = (x - centerX) / centerX * 4;
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.01,1.01,1.01)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});

// ===== COUNTER ANIMATION =====
const counterEl = document.querySelector('[data-count]');
if (counterEl) {
    const target = parseInt(counterEl.getAttribute('data-count'));
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                let current = 0;
                const increment = target / 40;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counterEl.textContent = target + '+';
                        clearInterval(timer);
                    } else {
                        counterEl.textContent = Math.floor(current) + '+';
                    }
                }, 40);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    counterObserver.observe(counterEl);
}
</script>
</body>
</html>
