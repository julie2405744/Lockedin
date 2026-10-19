<?php
/**
 * public/landing.php
 * Public landing page — shown to unauthenticated visitors.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

Auth::startSession();

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    $role = Auth::user()['role'] ?? 'student';
    header('Location: ' . BASE_PATH . '/' . ($role === 'admin' ? 'admin' : 'student') . '/dashboard.php');
    exit;
}

$base = BASE_PATH;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InFocus — Find and reserve university study rooms instantly. Book quiet spaces, manage reservations, and study smarter.">
    <title>InFocus — Your University Study Space, Organised</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/landing.css">
</head>
<body>

<!-- ═══════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════ -->
<nav class="lp-nav" id="lp-navbar">
    <a href="<?= $base ?>/landing.php" class="lp-nav__logo">In<span>Focus</span></a>

    <ul class="lp-nav__links">
        <li><a href="#how-it-works">How it works</a></li>
        <li><a href="#rooms">Study Rooms</a></li>
        <li><a href="#features">Features</a></li>
    </ul>

    <div class="lp-nav__actions">
        <a href="<?= $base ?>/login.php"    class="btn-ghost"       id="nav-login">Login</a>
        <a href="<?= $base ?>/register.php" class="btn-primary-nav" id="nav-signup">Sign up</a>
    </div>
</nav>

<!-- ═══════════════════════════════════════════
     HERO
════════════════════════════════════════════ -->
<section class="lp-hero" id="home">
    <div class="lp-hero__overlay"></div>
    <div class="lp-hero__content">
        <span class="lp-hero__eyebrow">University Study Room Booking</span>
        <h1 class="lp-hero__title">
            Find your perfect<br>
            <em>study space.</em>
        </h1>
        <p class="lp-hero__subtitle">
            Browse available rooms, book a quiet spot, and manage your reservations — all in one place.
        </p>

        <!-- Search bar -->
        <div class="lp-search" role="search">
            <div class="lp-search__field">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="hero-search" placeholder="Search by room name or building…" aria-label="Search rooms">
            </div>
            <a href="<?= $base ?>/login.php" class="lp-search__btn" id="hero-find-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Find a room
            </a>
        </div>

        <!-- Stats -->
        <div class="lp-stats">
            <div class="lp-stats__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span><strong>20+</strong> Study rooms</span>
            </div>
            <span class="lp-stats__sep">·</span>
            <div class="lp-stats__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>Book up to <strong>7 days</strong> ahead</span>
            </div>
            <span class="lp-stats__sep">·</span>
            <div class="lp-stats__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span><strong>Instant</strong> confirmation</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     FEATURES STRIP
════════════════════════════════════════════ -->
<section class="lp-features" id="features">
    <div class="lp-feature">
        <div class="lp-feature__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg></div>
        <span class="lp-feature__label">24 Hr Access</span>
    </div>
    <div class="lp-feature">
        <div class="lp-feature__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg></div>
        <span class="lp-feature__label">Fast Wi-Fi</span>
    </div>
    <div class="lp-feature">
        <div class="lp-feature__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 2.5-5 6-5s6 2 6 5M16 5a3 3 0 0 1 0 6M18 15c2 0 3 1.5 3 3.5"/></svg></div>
        <span class="lp-feature__label">Group & Solo Rooms</span>
    </div>
    <div class="lp-feature">
        <div class="lp-feature__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 21V4h12v17M16 9h4v12M8 8h.01M12 8h.01M8 12h.01M12 12h.01M8 16h.01M12 16h.01"/></svg></div>
        <span class="lp-feature__label">Modern Facilities</span>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════ -->
<section class="lp-how" id="how-it-works">
    <p class="lp-section-tag">Simple &amp; Fast</p>
    <h2 class="lp-section-title">How it works</h2>
    <p class="lp-section-sub">Reserve your ideal study space in three simple steps. No calls, no queues — just instant booking.</p>

    <div class="lp-steps">
        <div class="lp-step">
            <div class="lp-step__num">1</div>
            <h3>Create your account</h3>
            <p>Sign up with your university email in under a minute. Your account is ready instantly.</p>
        </div>
        <div class="lp-step">
            <div class="lp-step__num">2</div>
            <h3>Find an available room</h3>
            <p>Browse rooms by date, capacity, and facilities. See real-time availability at a glance.</p>
        </div>
        <div class="lp-step">
            <div class="lp-step__num">3</div>
            <h3>Book &amp; show up</h3>
            <p>Confirm your reservation instantly. Track, modify or cancel it anytime from your dashboard.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     ROOMS
════════════════════════════════════════════ -->
<section class="lp-rooms-section" id="rooms">
    <div class="lp-rooms-inner">
        <p class="lp-section-tag">What's available</p>
        <h2 class="lp-section-title">Explore our study rooms</h2>
        <p class="lp-section-sub">From quiet solo booths to large group rooms with whiteboards — we have the right space for every need.</p>

        <div class="lp-rooms">
            <div class="lp-room-card">
                <div class="lp-room-card__img lp-room-card__img--blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H20v17H7.5A3.5 3.5 0 0 0 4 22zM4 5.5V22M8 6h8M8 10h8"/></svg></div>
                <div class="lp-room-card__body">
                    <h3>Silent Study Room</h3>
                    <p>Perfect for focused individual study. Equipped with ergonomic seating and power outlets.</p>
                    <div class="lp-room-card__meta">
                        <span class="lp-badge">1–2 people</span>
                        <span class="lp-badge">Silent zone</span>
                    </div>
                </div>
            </div>
            <div class="lp-room-card">
                <div class="lp-room-card__img lp-room-card__img--indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 21h8M12 16v5"/></svg></div>
                <div class="lp-room-card__body">
                    <h3>Group Collaboration Room</h3>
                    <p>Ideal for team projects. Includes a large display screen and whiteboard walls.</p>
                    <div class="lp-room-card__meta">
                        <span class="lp-badge">4–8 people</span>
                        <span class="lp-badge">Screen</span>
                    </div>
                </div>
            </div>
            <div class="lp-room-card">
                <div class="lp-room-card__img lp-room-card__img--sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5zM6 11.5V16c3 2 9 2 12 0v-4.5M22 9v6"/></svg></div>
                <div class="lp-room-card__body">
                    <h3>Seminar Room</h3>
                    <p>A flexible space for presentations, study groups, and academic discussions.</p>
                    <div class="lp-room-card__meta">
                        <span class="lp-badge">10–20 people</span>
                        <span class="lp-badge">Projector</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lp-rooms-cta">
            <a href="<?= $base ?>/login.php" class="btn-outline-blue" id="browse-rooms-btn">Browse all rooms →</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CTA BAND
════════════════════════════════════════════ -->
<section class="lp-cta">
    <h2>Ready to find your focus?</h2>
    <p>Join hundreds of students already booking study rooms through InFocus.</p>
    <div class="lp-cta__btns">
        <a href="<?= $base ?>/register.php" class="btn-cta-white"   id="cta-signup">Get started free</a>
        <a href="<?= $base ?>/login.php"    class="btn-cta-outline" id="cta-login">Sign in</a>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     FOOTER
════════════════════════════════════════════ -->
<footer class="lp-footer">
    <a href="<?= $base ?>/landing.php" class="lp-footer__logo">In<span>Focus</span></a>
    <p class="lp-footer__copy">© <?= date('Y') ?> InFocus. All rights reserved.</p>
    <ul class="lp-footer__links">
        <li><a href="<?= $base ?>/login.php">Sign in</a></li>
        <li><a href="<?= $base ?>/register.php">Register</a></li>
    </ul>
</footer>

<script>
// Keep the transparent hero navigation readable after scrolling.
const nav = document.getElementById('lp-navbar');
window.addEventListener('scroll', () => {
    nav.classList.toggle('is-scrolled', window.scrollY > 10);
});

// Hero search → redirect to login with query
document.getElementById('hero-find-btn').addEventListener('click', function(e) {
    const q = document.getElementById('hero-search').value.trim();
    if (q) {
        e.preventDefault();
        window.location = '<?= $base ?>/login.php?room=' + encodeURIComponent(q);
    }
});
</script>

</body>
</html>
