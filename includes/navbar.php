<?php
/**
 * Good Spot Gaming Hub - Universal Navbar
 * 
 * Include this on any page: <?php include __DIR__ . '/includes/navbar.php'; ?>
 * Or from subdirectory: <?php include __DIR__ . '/../includes/navbar.php'; ?>
 * 
 * Uses $base_url to resolve all links correctly from any page depth.
 */

require_once __DIR__ . '/session_helper.php';

// Calculate the base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/GamingSpotHub';
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_url ?>/#home">
            <div class="logo-container">
                <span class="logo-g">G</span><span class="logo-s">s</span><span class="logo-p">p</span><span class="logo-o">o</span><span class="logo-t">t</span>
                <span class="logo-text">GAMING HUB</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#gaming">Gaming Rates</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#units">Units</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/reserve.php">Reserve</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#events">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/#contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>/faqs.php">FAQs</a></li>
            </ul>

            <?php if (isLoggedIn()): ?>
            <!-- Logged-in User Menu -->
            <div class="nav-user ms-lg-3">
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle" id="userDropdownBtn">
                        <div class="user-avatar"><?= getUserInitials() ?></div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                            <span class="user-role-badge"><?= getRoleBadge() ?></span>
                        </div>
                        <i class="fas fa-chevron-down user-chevron"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="dropdown-user-header">
                            <div class="user-avatar user-avatar-lg"><?= getUserInitials() ?></div>
                            <div>
                                <div class="dropdown-user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                                <div class="dropdown-user-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php if (in_array($_SESSION['role'], ['owner', 'shopkeeper'])): ?>
                            <a href="<?= $base_url ?>/admin.php" class="dropdown-item">
                                <i class="fas fa-gauge-high"></i> Admin Dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?= $base_url ?>/dashboard.php" class="dropdown-item">
                                <i class="fas fa-chart-line"></i> My Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="<?= $base_url ?>/auth/logout.php" class="dropdown-item dropdown-item-danger">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Guest Buttons -->
            <div class="nav-cta ms-lg-3">
                <a href="<?= $base_url ?>/auth/login.php" class="btn btn-secondary">Login</a>
                <a href="<?= $base_url ?>/auth/register.php" class="btn btn-primary">Register</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
(function () {
    const nav = document.getElementById('mainNav');
    if (!nav) return;

    // ── Scroll effect ──────────────────────────────────────────────────────────
    // On pages that have no #home hero (reserve, faqs, dashboard, etc.),
    // the navbar should always appear opaque. On index.php it transitions.
    const hasHero = !!document.getElementById('home');

    function applyScroll() {
        if (!hasHero || window.scrollY > 60) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    }

    applyScroll(); // apply immediately on page load (no flash of transparent bar)
    window.addEventListener('scroll', applyScroll, { passive: true });

    // ── Mobile menu: close when clicking outside ───────────────────────────────
    const toggler  = nav.querySelector('.navbar-toggler');
    const collapse = nav.querySelector('.navbar-collapse');
    if (toggler && collapse) {
        document.addEventListener('click', function (e) {
            if (!nav.contains(e.target) && collapse.classList.contains('show')) {
                toggler.click();
            }
        });
    }

    // ── User dropdown toggle ───────────────────────────────────────────────────
    const dropdownBtn = document.getElementById('userDropdownBtn');
    const dropdown    = dropdownBtn?.closest('.user-dropdown');
    if (dropdownBtn && dropdown) {
        dropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    }
})();
</script>
