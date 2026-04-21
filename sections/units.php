<?php
// Pull real console data from the database
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/session_helper.php';
$allConsoles  = getConsoles();
$isLoggedIn   = isLoggedIn();
?>
<!-- Units Section -->
<section id="units" class="units-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Available Units</span>
            <h2 class="section-title">Our Gaming Consoles</h2>
            <p class="section-subtitle">Choose from our <?= count($allConsoles) ?> premium console stations</p>
        </div>
        
        <div class="row g-4">
            <?php
            $delay = 100;
            foreach ($allConsoles as $con):
                $type   = $con['console_type'];
                $status = $con['status'];

                // Icon based on console type
                if (str_starts_with($type, 'PS')) {
                    $icon = 'fab fa-playstation';
                } elseif (stripos($type, 'Xbox') !== false) {
                    $icon = 'fab fa-xbox';
                } else {
                    $icon = 'fas fa-gamepad';
                }

                // Status classes
                $statusClass = match($status) {
                    'available'   => 'available',
                    'in_use'      => 'occupied',
                    'maintenance' => 'maintenance',
                    default       => 'available'
                };
                $statusLabel = match($status) {
                    'available'   => 'Available',
                    'in_use'      => 'In Use',
                    'maintenance' => 'Maintenance',
                    default       => ucfirst($status)
                };

                // Featured badge for PS5
                $isFeatured = ($type === 'PS5');
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                <div class="unit-card<?= $isFeatured ? ' featured' : '' ?>">
                    <?php if ($isFeatured): ?>
                    <div class="featured-badge">POPULAR</div>
                    <?php endif; ?>
                    <div class="unit-status <?= $statusClass ?>"><?= $statusLabel ?></div>
                    <div class="unit-number"><?= htmlspecialchars($con['unit_number']) ?></div>
                    <div class="unit-type">
                        <i class="<?= $icon ?>"></i>
                        <span><?= htmlspecialchars($type) ?></span>
                    </div>
                    <div class="unit-price">₱<?= number_format((float)$con['hourly_rate'], 0) ?><span>/hour</span></div>
                    <div class="unit-specs">
                        <h5>Includes</h5>
                        <ul>
                            <li><i class="fas fa-gamepad"></i> <?= htmlspecialchars($type) ?> Console</li>
                            <li><i class="fas fa-tv"></i> HD / 4K Display</li>
                            <li><i class="fas fa-headphones"></i> Gaming Head</li>
                            <li><i class="fas fa-couch"></i> Comfortable Seating</li>
                        </ul>
                    </div>
                    <?php
                        $consoleParam  = urlencode($type);
                        $reserveUrl    = 'reserve.php?console=' . $consoleParam;
                        $bookHref      = $isLoggedIn
                            ? $reserveUrl
                            : 'auth/login.php?redirect=' . urlencode($reserveUrl);
                    ?>
                    <?php if ($status === 'available'): ?>
                    <a href="<?= $bookHref ?>" class="unit-book-btn">
                        <i class="fas fa-calendar-check" style="margin-right:6px;"></i>Book Now
                    </a>
                    <?php else: ?>
                    <a href="#" class="unit-book-btn disabled"><?= $statusLabel ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
                $delay += 50;
            endforeach;
            ?>
        </div>

        <!-- Legend -->
        <div class="units-legend mt-5" data-aos="fade-up">
            <div class="legend-item">
                <span class="legend-indicator available"></span>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <span class="legend-indicator occupied"></span>
                <span>In Use</span>
            </div>
            <div class="legend-item">
                <span class="legend-indicator maintenance"></span>
                <span>Maintenance</span>
            </div>
        </div>
    </div>
</section>
