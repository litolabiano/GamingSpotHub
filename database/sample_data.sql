-- ============================================================================
-- Good Spot Gaming Hub - Sample Data
-- For testing and demonstration purposes
-- ============================================================================

USE gamingspothub;

-- ============================================================================
-- USERS (1 Owner, 1 Shopkeeper, 3 Customers)
-- Default password for all: "password123" (bcrypt hashed)
-- ============================================================================

INSERT INTO users (email, password_hash, full_name, phone, role, status) VALUES
('owner@goodspot.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', '09171234567', 'owner', 'active'),
('shopkeeper@goodspot.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos', '09181234567', 'shopkeeper', 'active'),
('carlos@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Reyes', '09191234567', 'customer', 'active'),
('anna@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Garcia', '09201234567', 'customer', 'active'),
('mark@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mark Bautista', '09211234567', 'customer', 'active');


-- ============================================================================
-- CONSOLES (5 PS5 units, 5 Xbox Series X units)
-- ============================================================================

INSERT INTO consoles (console_name, console_type, unit_number, status, hourly_rate) VALUES
('PS5 Unit 1', 'PS5', 'PS5-01', 'available', 60.00),
('PS5 Unit 2', 'PS5', 'PS5-02', 'available', 60.00),
('PS5 Unit 3', 'PS5', 'PS5-03', 'available', 60.00),
('PS5 Unit 4', 'PS5', 'PS5-04', 'maintenance', 60.00),
('PS5 Unit 5', 'PS5', 'PS5-05', 'available', 60.00),
('Xbox Unit 1', 'Xbox Series X', 'XBX-01', 'available', 60.00),
('Xbox Unit 2', 'Xbox Series X', 'XBX-02', 'available', 60.00),
('Xbox Unit 3', 'Xbox Series X', 'XBX-03', 'available', 60.00),
('Xbox Unit 4', 'Xbox Series X', 'XBX-04', 'available', 60.00),
('Xbox Unit 5', 'Xbox Series X', 'XBX-05', 'available', 60.00);


-- ============================================================================
-- GAMES (Popular titles across both platforms)
-- ============================================================================

INSERT INTO games (game_name, console_type, genre, is_available, is_new_release, description) VALUES
('NBA 2K25', 'Both', 'Sports', 1, 1, 'The latest basketball simulation game'),
('FIFA 25', 'Both', 'Sports', 1, 1, 'The latest football/soccer simulation game'),
('Call of Duty: Black Ops 6', 'Both', 'FPS', 1, 1, 'First-person shooter action game'),
('GTA V', 'Both', 'Action-Adventure', 1, 0, 'Open-world action-adventure game'),
('Tekken 8', 'Both', 'Fighting', 1, 1, 'Fighting game with stunning graphics'),
('Marvel''s Spider-Man 2', 'PS5', 'Action-Adventure', 1, 0, 'Swing through NYC as Spider-Man'),
('God of War Ragnarök', 'PS5', 'Action-Adventure', 1, 0, 'Epic Norse mythology action game'),
('Astro Bot', 'PS5', 'Platformer', 1, 1, 'Charming 3D platformer exclusive'),
('Forza Motorsport', 'Xbox Series X', 'Racing', 1, 0, 'Next-gen racing simulation'),
('Halo Infinite', 'Xbox Series X', 'FPS', 1, 0, 'Iconic sci-fi first-person shooter'),
('Starfield', 'Xbox Series X', 'RPG', 1, 0, 'Open-world space RPG by Bethesda'),
('Mortal Kombat 1', 'Both', 'Fighting', 1, 0, 'Brutal fighting game reboot'),
('EA Sports FC 25', 'Both', 'Sports', 1, 1, 'Next evolution of football gaming'),
('Resident Evil 4 Remake', 'Both', 'Horror', 1, 0, 'Survival horror classic remade'),
('Dragon Ball: Sparking Zero', 'Both', 'Fighting', 1, 1, 'Anime arena fighting game');


-- ============================================================================
-- GAMING SESSIONS (Sample completed and active sessions)
-- ============================================================================

INSERT INTO gaming_sessions (user_id, console_id, rental_mode, start_time, end_time, duration_minutes, hourly_rate, total_cost, status, created_by) VALUES
-- Completed sessions
(3, 1, 'hourly', '2026-02-20 10:00:00', '2026-02-20 12:00:00', 120, 60.00, 120.00, 'completed', 2),
(4, 6, 'open_time', '2026-02-20 13:00:00', '2026-02-20 15:30:00', 150, 60.00, 150.00, 'completed', 2),
(5, 3, 'unlimited', '2026-02-20 09:00:00', '2026-02-20 21:00:00', 720, 60.00, 300.00, 'completed', 2),
(3, 7, 'hourly', '2026-02-21 14:00:00', '2026-02-21 16:00:00', 120, 60.00, 120.00, 'completed', 2),
-- Active sessions
(4, 2, 'hourly', '2026-02-21 17:00:00', NULL, NULL, 60.00, NULL, 'active', 2),
(5, 8, 'open_time', '2026-02-21 16:30:00', NULL, NULL, 60.00, NULL, 'active', 2);

-- Update console status for active sessions
UPDATE consoles SET status = 'in_use' WHERE console_id IN (2, 8);


-- ============================================================================
-- ADDITIONAL REQUESTS
-- ============================================================================

INSERT INTO additional_requests (session_id, request_type, description, extra_cost, status) VALUES
(1, 'controller_rental', 'Extra DualSense controller', 20.00, 'approved'),
(3, 'extra_hours', 'Extended unlimited session by 2 hours', 0.00, 'approved'),
(5, 'controller_rental', 'Second controller for co-op play', 20.00, 'pending');


-- ============================================================================
-- TRANSACTIONS
-- ============================================================================

INSERT INTO transactions (session_id, user_id, amount, payment_method, payment_status, transaction_date, processed_by) VALUES
(1, 3, 140.00, 'cash', 'completed', '2026-02-20 12:00:00', 2),
(2, 4, 150.00, 'gcash', 'completed', '2026-02-20 15:30:00', 2),
(3, 5, 300.00, 'cash', 'completed', '2026-02-20 21:00:00', 2),
(4, 3, 120.00, 'gcash', 'completed', '2026-02-21 16:00:00', 2);


-- ============================================================================
-- GAME REQUESTS (Customer game installation requests)
-- ============================================================================

INSERT INTO game_requests (user_id, game_name, console_type, message, status) VALUES
(3, 'Elden Ring', 'PS5', 'Would love to play this game here!', 'approved'),
(4, 'Forza Horizon 5', 'Xbox Series X', 'Great racing game, please install', 'installed'),
(5, 'Final Fantasy XVI', 'PS5', 'New release, want to try it', 'pending');


-- ============================================================================
-- TOURNAMENTS
-- ============================================================================

INSERT INTO tournaments (tournament_name, game_id, console_type, start_date, end_date, entry_fee, prize_pool, max_participants, status, announcement) VALUES
('NBA 2K25 Monthly Showdown', 1, 'PS5', '2026-03-01 13:00:00', '2026-03-01 20:00:00', 100.00, 3000.00, 16, 'upcoming',
    'Join our monthly NBA 2K25 tournament! ₱100 entry fee with ₱3,000 prize pool. Register now at the shop or through the website.'),
('Tekken 8 Fight Night', 5, 'PS5', '2026-02-15 14:00:00', '2026-02-15 21:00:00', 50.00, 1500.00, 32, 'completed',
    'Tekken 8 tournament completed! Congratulations to all winners!'),
('COD Warzone Battle', 3, 'Xbox Series X', '2026-03-15 10:00:00', '2026-03-15 18:00:00', 75.00, 2000.00, 20, 'upcoming',
    'Call of Duty tournament coming soon! Team up and compete for ₱2,000!');


-- ============================================================================
-- TOURNAMENT PARTICIPANTS
-- ============================================================================

INSERT INTO tournament_participants (tournament_id, user_id, registration_date, payment_status, placement, prize_amount) VALUES
-- Tekken 8 tournament (completed)
(2, 3, '2026-02-13 10:00:00', 'paid', 1, 800.00),
(2, 4, '2026-02-13 11:00:00', 'paid', 2, 450.00),
(2, 5, '2026-02-14 09:00:00', 'paid', 3, 250.00),
-- NBA 2K25 tournament (upcoming, registrations open)
(1, 3, '2026-02-20 15:00:00', 'paid', NULL, NULL),
(1, 4, '2026-02-21 10:00:00', 'pending', NULL, NULL);


-- ============================================================================
-- REPORTS
-- ============================================================================

INSERT INTO reports (report_type, generated_by, date_from, date_to) VALUES
('daily_sales', 1, '2026-02-20', '2026-02-20'),
('console_usage', 1, '2026-02-01', '2026-02-20'),
('rental_records', 1, '2026-02-01', '2026-02-21');


-- ============================================================================
-- SYSTEM SETTINGS
-- ============================================================================

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('ps5_hourly_rate', '60.00', 'Default hourly rate for PS5 units in ₱'),
('xbox_hourly_rate', '60.00', 'Default hourly rate for Xbox Series X units in ₱'),
('unlimited_rate', '300.00', 'Rate for unlimited play (whole day) in ₱'),
('controller_rental_fee', '20.00', 'Additional controller rental fee in ₱'),
('business_hours_open', '09:00', 'Shop opening time'),
('business_hours_close', '23:00', 'Shop closing time'),
('shop_name', 'Good Spot Gaming Hub', 'Shop name'),
('shop_address', 'Don Placido Avenue, Dasmariñas, Cavite', 'Shop address'),
('shop_phone', '09171234567', 'Shop contact number'),
('tournament_default_fee', '100.00', 'Default tournament entry fee in ₱');
