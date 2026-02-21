<?php
require_once __DIR__ . '/includes/session_helper.php';
requireRole(['owner', 'shopkeeper']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - G SPOT Gaming Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-gamepad" style="font-size: 24px;"></i>
        <h2>G SPOT Admin</h2>
    </div>
    <div class="nav-item active" onclick="showPage('dashboard')">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
    </div>
    <div class="nav-item" onclick="showPage('stations')">
        <i class="fas fa-desktop"></i>
        <span>Stations</span>
    </div>
    <div class="nav-item" onclick="showPage('bookings')">
        <i class="fas fa-calendar-alt"></i>
        <span>Bookings</span>
    </div>

    <div class="nav-item" onclick="showPage('games')">
        <i class="fas fa-gamepad"></i>
        <span>Game Library</span>
    </div>
    <div class="nav-item" onclick="showPage('financial')">
        <i class="fas fa-dollar-sign"></i>
        <span>Financial</span>
    </div>
    <div class="nav-item" onclick="showPage('reports')">
        <i class="fas fa-chart-bar"></i>
        <span>Reports</span>
    </div>
    <div class="nav-item" onclick="showPage('settings')">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </div>
    <div style="flex:1;"></div>
    <a href="index.php" class="nav-item" style="text-decoration:none; color:inherit; margin-top:auto; border-top:1px solid rgba(255,255,255,0.1); padding-top:15px;">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Site</span>
    </a>
</div>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
        <h3 id="pageTitle">Dashboard</h3>
    </div>
    <div class="topbar-right">
        <div class="notification-btn">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">5</span>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= getUserInitials() ?></div>
            <div>
                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($user['full_name']) ?></div>
                <div style="font-size: 12px; color: #718096;"><?= getRoleBadge() ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Dashboard Page -->
    <div class="page active" id="dashboard">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">₱12,450</div>
                        <div class="stat-label">Today's Revenue</div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                </div>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 12% from yesterday
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">8</div>
                        <div class="stat-label">Active Sessions</div>
                    </div>
                    <div class="stat-icon sessions">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 4 stations occupied
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">24</div>
                        <div class="stat-label">Bookings Today</div>
                    </div>
                    <div class="stat-icon bookings">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 8% from last week
                </div>
            </div>
            
        </div>
        

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Bookings</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Station</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="recentBookings">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Stations Page -->
    <div class="page" id="stations">
        <div class="card-header" style="margin-bottom: 20px;">
            <h3 class="card-title">Station Management</h3>
            <div>
                <button class="btn btn-sm"><i class="fas fa-th"></i> Grid</button>
                <button class="btn btn-sm"><i class="fas fa-list"></i> List</button>
            </div>
        </div>
        <div class="station-grid" id="stationGrid">
            <!-- Populated by JS -->
        </div>
    </div>
    
    <!-- Bookings Page -->
    <div class="page" id="bookings">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Bookings</h3>
                <button class="btn btn-primary" onclick="openModal('newBooking')">
                    <i class="fas fa-plus"></i> New Booking
                </button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Station</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="allBookings">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
    

    <!-- Games Page -->
    <div class="page" id="games">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Game Library</h3>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Game
                </button>
            </div>
            <div class="station-grid" id="gamesGrid">
                <!-- Game cards populated by JS -->
            </div>
        </div>
    </div>
    
    <!-- Financial Page -->
    <div class="page" id="financial">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">₱245,680</div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₱45,200</div>
                <div class="stat-label">Monthly Expenses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₱200,480</div>
                <div class="stat-label">Net Profit</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">81.6%</div>
                <div class="stat-label">Profit Margin</div>
            </div>
        </div>
        

    </div>
    
    <!-- Reports Page -->
    <div class="page" id="reports">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Analytics & Reports</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="card">
                    <h4>Peak Hours Analysis</h4>
                    <canvas id="peakHoursChart"></canvas>
                </div>
                <div class="card">
                    <h4>Popular Games</h4>
                    <canvas id="popularGamesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Page -->
    <div class="page" id="settings">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Settings</h3>
            </div>
            <div class="form-group">
                <label>Business Hours</label>
                <input type="text" class="form-control" value="9:00 AM - 11:00 PM">
            </div>
            <div class="form-group">
                <label>Standard PC Rate (per hour)</label>
                <input type="number" class="form-control" value="50">
            </div>
            <div class="form-group">
                <label>Console Rate (per hour)</label>
                <input type="number" class="form-control" value="60">
            </div>
            <button class="btn btn-primary" onclick="saveSettings()">Save Settings</button>
        </div>
    </div>
    
</div>

<!-- Modals -->
<div class="modal" id="newBookingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">New Booking</h3>
            <span class="modal-close" onclick="closeModal('newBooking')">&times;</span>
        </div>
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" class="form-control" placeholder="Enter customer name">
        </div>
        <div class="form-group">
            <label>Station</label>
            <select class="form-control">
                <option>PC-01</option>
                <option>PC-02</option>
                <option>PS5-01</option>
            </select>
        </div>
        <div class="form-group">
            <label>Date & Time</label>
            <input type="datetime-local" class="form-control">
        </div>
        <div class="form-group">
            <label>Duration (hours)</label>
            <input type="number" class="form-control" value="2">
        </div>
        <button class="btn btn-primary" onclick="createBooking()">Create Booking</button>
    </div>
</div>


<!-- Toast -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle toast-icon"></i>
    <span id="toastMessage">Action completed successfully!</span>
</div>

<script>
// Mock Data
const stations = [
    {id: 'PC-01', type: 'Gaming PC', status: 'available', specs: 'Intel i7-13700K, RTX 4070 Ti, 32GB RAM', icon: 'desktop'},
    {id: 'PC-02', type: 'Gaming PC', status: 'available', specs: 'Intel i7-13700K, RTX 4070 Ti, 32GB RAM', icon: 'desktop'},
    {id: 'PC-03', type: 'Gaming PC', status: 'occupied', specs: 'AMD Ryzen 9 7900X, RTX 4070, 32GB RAM', icon: 'desktop'},
    {id: 'PC-04', type: 'Premium Gaming PC', status: 'available', specs: 'Intel i9-14900K, RTX 4090, 64GB RAM', icon: 'desktop'},
    {id: 'PS5-01', type: 'PlayStation 5', status: 'available', specs: 'PS5 Console, 55" 4K TV, Premium Headset', icon: 'playstation'},
    {id: 'XBOX-01', type: 'Xbox Series X', status: 'occupied', specs: 'Xbox Series X, 55" 4K TV, Premium Headset', icon: 'xbox'},
    {id: 'NSW-01', type: 'Nintendo Switch', status: 'available', specs: 'Nintendo Switch OLED, 43" Full HD TV', icon: 'gamepad'},
    {id: 'VIP-01', type: 'VIP Gaming Room', status: 'available', specs: '4-6 pax, 2x Gaming PCs, PS5 & Xbox', icon: 'crown'},
    {id: 'STR-01', type: 'Streaming Station', status: 'maintenance', specs: 'Intel i9-14900K, 4K Webcam, Dual Monitors', icon: 'video'},
    {id: 'PC-05', type: 'Gaming PC', status: 'available', specs: 'Intel i7-13700K, RTX 4070 Ti, 32GB RAM', icon: 'desktop'},
    {id: 'PC-06', type: 'Gaming PC', status: 'occupied', specs: 'AMD Ryzen 9 7900X, RTX 4070, 32GB RAM', icon: 'desktop'},
    {id: 'PS5-02', type: 'PlayStation 5', status: 'available', specs: 'PS5 Console, 55" 4K TV, Premium Headset', icon: 'playstation'}
];

const bookings = [
    {id: 'BK001', customer: 'John Doe', station: 'PC-01', time: '2:00 PM', duration: '2hrs', status: 'confirmed'},
    {id: 'BK002', customer: 'Jane Smith', station: 'PS5-01', time: '3:30 PM', duration: '3hrs', status: 'confirmed'},
    {id: 'BK003', customer: 'Mike Johnson', station: 'XBOX-01', time: '5:00 PM', duration: '1hr', status: 'pending'},
    {id: 'BK004', customer: 'Sarah Williams', station: 'VIP-01', time: '6:00 PM', duration: '4hrs', status: 'confirmed'},
    {id: 'BK005', customer: 'Tom Brown', station: 'PC-04', time: '7:00 PM', duration: '2hrs', status: 'completed'}
];

const games = [
    {id: 1, title: 'Valorant', platform: 'PC', players: '5v5', status: 'popular', image: 'https://images2.alphacoders.com/106/1065185.jpg'},
    {id: 2, title: 'League of Legends', platform: 'PC', players: '5v5', status: 'active', image: 'https://images4.alphacoders.com/935/935548.jpg'},
    {id: 3, title: 'Dota 2', platform: 'PC', players: '5v5', status: 'active', image: 'https://images.alphacoders.com/131/1317511.jpg'},
    {id: 4, title: 'Tekken 8', platform: 'PS5', players: '1v1', status: 'new', image: 'https://images7.alphacoders.com/134/1346049.jpg'},
    {id: 5, title: 'EA FC 24', platform: 'PS5/Xbox', players: '1v1', status: 'active', image: 'https://images.alphacoders.com/134/1345479.jpg'},
    {id: 6, title: 'Cyberpunk 2077', platform: 'PC', players: 'Single Player', status: 'active', image: 'https://images3.alphacoders.com/105/1057451.jpg'}
];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderStations();
    renderRecentBookings();
    renderAllBookings();
    renderGames();
    initCharts();
});

// Navigation
function showPage(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById(page).classList.add('active');
    event.target.closest('.nav-item').classList.add('active');
    
    const titles = {
        dashboard: 'Dashboard',
        stations: 'Station Management',
        bookings: 'Booking Management',
        games: 'Game Library',
        financial: 'Financial Reports',
        reports: 'Analytics & Reports',
        settings: 'Settings'
    };
    document.getElementById('pageTitle').textContent = titles[page];
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
}

// Render Functions
function renderStations() {
    const grid = document.getElementById('stationGrid');
    grid.innerHTML = stations.map(s => `
        <div class="station-card">
            <div class="station-status ${s.status}">${s.status.charAt(0).toUpperCase() + s.status.slice(1)}</div>
            <div class="station-number">${s.id}</div>
            <div class="station-type">
                <i class="fas fa-${s.icon}"></i>
                <span>${s.type}</span>
            </div>
            <div class="station-specs">${s.specs}</div>
            <div class="station-actions">
                <button class="btn btn-sm btn-primary">View Details</button>
                <button class="btn btn-sm btn-success" onclick="changeStatus('${s.id}')">Change Status</button>
            </div>
        </div>
    `).join('');
}

function renderRecentBookings() {
    const tbody = document.getElementById('recentBookings');
    tbody.innerHTML = bookings.slice(0, 5).map(b => `
        <tr>
            <td>${b.id}</td>
            <td>${b.customer}</td>
            <td>${b.station}</td>
            <td>${b.time}</td>
            <td>${b.duration}</td>
            <td><span class="badge badge-${b.status === 'confirmed' ? 'success' : b.status === 'pending' ? 'warning' : 'info'}">${b.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary">View</button>
                <button class="btn btn-sm btn-danger">Cancel</button>
            </td>
        </tr>
    `).join('');
}

function renderAllBookings() {
    const tbody = document.getElementById('allBookings');
    tbody.innerHTML = bookings.map(b => `
        <tr>
            <td>${b.id}</td>
            <td>${b.customer}</td>
            <td>${b.station}</td>
            <td>Feb 3, 2026 ${b.time}</td>
            <td>${b.duration}</td>
            <td>₱${parseInt(b.duration) * 50}</td>
            <td><span class="badge badge-${b.status === 'confirmed' ? 'success' : b.status === 'pending' ? 'warning' : 'info'}">${b.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary">Edit</button>
                <button class="btn btn-sm btn-danger">Cancel</button>
            </td>
        </tr>
    `).join('');
}


function renderGames() {
    const grid = document.getElementById('gamesGrid');
    grid.innerHTML = games.map(g => `
        <div class="station-card">
            <div class="station-status ${g.status === 'popular' ? 'available' : g.status === 'new' ? 'occupied' : 'maintenance'}">${g.status.toUpperCase()}</div>
            <div style="height: 150px; overflow: hidden; border-radius: 8px; margin-bottom: 15px;">
                <img src="${g.image}" alt="${g.title}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="station-number" style="font-size: 18px;">${g.title}</div>
            <div class="station-type">
                <i class="fas fa-gamepad"></i>
                <span>${g.platform} | ${g.players}</span>
            </div>
            <div class="station-actions">
                <button class="btn btn-sm btn-primary">Edit</button>
                <button class="btn btn-sm btn-danger">Remove</button>
            </div>
        </div>
    `).join('');
}

// Charts
function initCharts() {
    // Peak Hours Chart
    const ctx1 = document.getElementById('peakHoursChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['9AM', '12PM', '3PM', '6PM', '9PM', '11PM'],
            datasets: [{
                label: 'Station Occupancy',
                data: [4, 8, 7, 12, 11, 6],
                borderColor: '#20c8a1',
                backgroundColor: 'rgba(32, 200, 161, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Popular Games Chart
    const ctx2 = document.getElementById('popularGamesChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Valorant', 'Tekken 8', 'EA FC 24', 'Others'],
            datasets: [{
                data: [40, 25, 20, 15],
                backgroundColor: ['#5f85da', '#fb566b', '#20c8a1', '#b37bec'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { color: '#f8f9fa' } } }
        }
    });
}

// Modal Functions
function openModal(type) {
    document.getElementById(type + 'Modal').classList.add('active');
}

function closeModal(type) {
    document.getElementById(type + 'Modal').classList.remove('active');
}

// Actions
function changeStatus(stationId) {
    showToast('Station status updated successfully!', 'success');
}

function createBooking() {
    closeModal('newBooking');
    showToast('Booking created successfully!', 'success');
}

function saveSettings() {
    showToast('Settings saved successfully!', 'success');
}


function showToast(message, type) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMessage').textContent = message;
    toast.className = 'toast ' + type + ' active';
    setTimeout(() => toast.classList.remove('active'), 3000);
}
</script>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>

</body>
</html>
