-- eMuse Museum Management System — Full Database Schema
-- Single-file setup: creates database, all tables, and seed data from scratch.
-- Passwords: admin → admin123 | visitors → user123 | all staff → Staff@123

CREATE DATABASE IF NOT EXISTS eMuse_db;
USE eMuse_db;

-- ============================================================
-- CORE USER TABLES
-- ============================================================

-- Admin / Staff Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id   INT PRIMARY KEY AUTO_INCREMENT,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    role       ENUM('super_admin','admin','ticketing_staff','tour_guide',
                    'maintenance_staff','shop_staff','manager')
               DEFAULT 'ticketing_staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Regular / Visitor Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id    INT PRIMARY KEY AUTO_INCREMENT,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    phone      VARCHAR(20),
    address    TEXT,
    status     ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- ============================================================
-- EXHIBITIONS & ARTWORKS
-- ============================================================

-- Exhibit Classifications Table
CREATE TABLE IF NOT EXISTS exhibit_classifications (
    classification_id INT PRIMARY KEY AUTO_INCREMENT,
    name              VARCHAR(100) NOT NULL,
    description       TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exhibits Table
CREATE TABLE IF NOT EXISTS exhibits (
    exhibit_id        INT PRIMARY KEY AUTO_INCREMENT,
    title             VARCHAR(200) NOT NULL,
    description       TEXT,
    classification_id INT,
    start_date        DATE,
    end_date          DATE,
    status            ENUM('upcoming', 'active', 'closed') DEFAULT 'upcoming',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (classification_id) REFERENCES exhibit_classifications(classification_id)
);

-- Locations Table
CREATE TABLE IF NOT EXISTS locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    floor       VARCHAR(20),
    capacity    INT,
    description TEXT
);

-- Artworks and Artifacts Table
CREATE TABLE IF NOT EXISTS artworks (
    artwork_id       INT PRIMARY KEY AUTO_INCREMENT,
    title            VARCHAR(200) NOT NULL,
    artist           VARCHAR(100),
    type             ENUM('painting', 'sculpture', 'artifact', 'photograph', 'other') NOT NULL,
    description      TEXT,
    year_created     VARCHAR(20),
    exhibit_id       INT,
    location_id      INT,
    acquisition_date DATE,
    condition_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    image_path       VARCHAR(255),
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exhibit_id)  REFERENCES exhibits(exhibit_id)  ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL
);

-- ============================================================
-- TICKETING
-- ============================================================

-- Ticket Types / Pricing Table
CREATE TABLE IF NOT EXISTS ticket_types (
    ticket_type VARCHAR(20) PRIMARY KEY,
    price       DECIMAL(10,2) NOT NULL
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id      INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code    VARCHAR(50) UNIQUE NOT NULL,
    visitor_name   VARCHAR(100) NOT NULL,
    visitor_email  VARCHAR(100),
    visitor_phone  VARCHAR(20),
    ticket_type    VARCHAR(20) NOT NULL,
    price          DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    promo_code     VARCHAR(50) DEFAULT NULL,
    amount_paid    DECIMAL(10,2) DEFAULT NULL,
    payment_method ENUM('cash','card','online','free') DEFAULT 'online',
    visit_date     DATE NOT NULL,
    qr_code        VARCHAR(255),
    purchase_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status         ENUM('pending', 'confirmed', 'used', 'cancelled') DEFAULT 'confirmed',
    scanned_at     TIMESTAMP NULL,
    scanned_by     INT,
    user_id        INT NULL,
    FOREIGN KEY (scanned_by)  REFERENCES admin_users(admin_id),
    FOREIGN KEY (ticket_type) REFERENCES ticket_types(ticket_type),
    FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- TOURS
-- ============================================================

-- Tour Guides Table
CREATE TABLE IF NOT EXISTS tour_guides (
    guide_id       INT PRIMARY KEY AUTO_INCREMENT,
    admin_id       INT NULL,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(100),
    phone          VARCHAR(20),
    specialization VARCHAR(100),
    bio            TEXT NULL,
    status         ENUM('active', 'inactive') DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL
);

-- Tours Table
CREATE TABLE IF NOT EXISTS tours (
    tour_id      INT PRIMARY KEY AUTO_INCREMENT,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    guide_id     INT,
    tour_date    DATE NOT NULL,
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    max_capacity INT NOT NULL,
    price        DECIMAL(10,2),
    status       ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES tour_guides(guide_id)
);

-- Tour Bookings Table
CREATE TABLE IF NOT EXISTS tour_bookings (
    booking_id       INT PRIMARY KEY AUTO_INCREMENT,
    tour_id          INT NOT NULL,
    ticket_id        INT,
    visitor_name     VARCHAR(100) NOT NULL,
    visitor_email    VARCHAR(100),
    number_of_people INT DEFAULT 1,
    booking_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status           ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    amount_paid      DECIMAL(10,2) DEFAULT NULL,
    discount_amount  DECIMAL(10,2) DEFAULT 0,
    promo_code       VARCHAR(50) DEFAULT NULL,
    user_id          INT NULL,
    FOREIGN KEY (tour_id)   REFERENCES tours(tour_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id)   REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- VISITOR STATS
-- ============================================================

CREATE TABLE IF NOT EXISTS visitor_stats (
    stat_id        INT PRIMARY KEY AUTO_INCREMENT,
    visit_date     DATE NOT NULL,
    total_visitors INT DEFAULT 0,
    online_tickets INT DEFAULT 0,
    walk_in_tickets INT DEFAULT 0,
    revenue        DECIMAL(10,2) DEFAULT 0,
    UNIQUE KEY (visit_date)
);

-- ============================================================
-- MAINTENANCE
-- ============================================================

-- Equipment Table
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id    INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(200) NOT NULL,
    equipment_type  VARCHAR(100) NOT NULL,
    location_id     INT,
    purchase_date   DATE,
    warranty_expiry DATE,
    status          ENUM('operational', 'maintenance', 'repair', 'retired') DEFAULT 'operational',
    serial_number   VARCHAR(100),
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- Maintenance Records Table
CREATE TABLE IF NOT EXISTS maintenance_records (
    maintenance_id   INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id     INT NOT NULL,
    maintenance_type ENUM('routine', 'preventive', 'repair', 'inspection') NOT NULL,
    scheduled_date   DATE NOT NULL,
    completed_date   DATE,
    performed_by     VARCHAR(100),
    cost             DECIMAL(10,2),
    description      TEXT,
    notes            TEXT,
    status           ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
);

-- Maintenance Alerts Table
CREATE TABLE IF NOT EXISTS maintenance_alerts (
    alert_id        INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id    INT NOT NULL,
    alert_type      ENUM('due', 'overdue', 'urgent', 'warranty_expiring') NOT NULL,
    message         TEXT NOT NULL,
    due_date        DATE,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id)    REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES admin_users(admin_id)
);

-- ============================================================
-- SOUVENIR SHOP
-- ============================================================

-- Product Categories Table
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    product_id     INT PRIMARY KEY AUTO_INCREMENT,
    name           VARCHAR(200) NOT NULL,
    category_id    INT,
    sku            VARCHAR(100) UNIQUE NOT NULL,
    description    TEXT,
    price          DECIMAL(10,2) NOT NULL,
    cost_price     DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    reorder_level  INT DEFAULT 10,
    supplier       VARCHAR(200),
    image_path     VARCHAR(255),
    status         ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id)
);

-- Product Sales Table
CREATE TABLE IF NOT EXISTS product_sales (
    sale_id         INT PRIMARY KEY AUTO_INCREMENT,
    sale_date       DATE NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL,
    payment_method  ENUM('cash', 'card', 'online') NOT NULL,
    customer_name   VARCHAR(100),
    customer_email  VARCHAR(100),
    discount_amount DECIMAL(10,2) DEFAULT 0,
    promo_code      VARCHAR(50) DEFAULT NULL,
    served_by       INT,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (served_by) REFERENCES admin_users(admin_id)
);

-- Sale Items Table
CREATE TABLE IF NOT EXISTS sale_items (
    item_id    INT PRIMARY KEY AUTO_INCREMENT,
    sale_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id)    REFERENCES product_sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Stock Alerts Table
CREATE TABLE IF NOT EXISTS stock_alerts (
    alert_id   INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    alert_type ENUM('low_stock', 'out_of_stock', 'reorder') NOT NULL,
    message    TEXT NOT NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- VISITOR FEEDBACK
-- ============================================================

-- Feedback Categories Table
CREATE TABLE IF NOT EXISTS feedback_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitor Feedback Table
CREATE TABLE IF NOT EXISTS visitor_feedback (
    feedback_id       INT PRIMARY KEY AUTO_INCREMENT,
    visitor_name      VARCHAR(100),
    visitor_email     VARCHAR(100),
    category_id       INT,
    rating            INT CHECK (rating >= 1 AND rating <= 5),
    feedback_text     TEXT NOT NULL,
    visit_date        DATE,
    exhibition_rating INT CHECK (exhibition_rating >= 1 AND exhibition_rating <= 5),
    staff_rating      INT CHECK (staff_rating >= 1 AND staff_rating <= 5),
    facilities_rating INT CHECK (facilities_rating >= 1 AND facilities_rating <= 5),
    recommend         ENUM('yes', 'no', 'maybe'),
    status            ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    admin_response    TEXT,
    responded_by      INT,
    responded_at      TIMESTAMP NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)  REFERENCES feedback_categories(category_id),
    FOREIGN KEY (responded_by) REFERENCES admin_users(admin_id)
);

-- Feedback Statistics Table
CREATE TABLE IF NOT EXISTS feedback_statistics (
    stat_id        INT PRIMARY KEY AUTO_INCREMENT,
    period_date    DATE NOT NULL,
    total_feedback INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    positive_count INT DEFAULT 0,
    neutral_count  INT DEFAULT 0,
    negative_count INT DEFAULT 0,
    response_rate  DECIMAL(5,2),
    UNIQUE KEY (period_date)
);

-- ============================================================
-- REPORTS & ANALYTICS
-- ============================================================

-- Daily Reports Table
CREATE TABLE IF NOT EXISTS daily_reports (
    report_id      INT PRIMARY KEY AUTO_INCREMENT,
    report_date    DATE NOT NULL UNIQUE,
    total_visitors INT DEFAULT 0,
    adult_tickets  INT DEFAULT 0,
    child_tickets  INT DEFAULT 0,
    senior_tickets INT DEFAULT 0,
    student_tickets INT DEFAULT 0,
    group_tickets  INT DEFAULT 0,
    ticket_revenue DECIMAL(10,2) DEFAULT 0,
    tour_revenue   DECIMAL(10,2) DEFAULT 0,
    shop_revenue   DECIMAL(10,2) DEFAULT 0,
    total_revenue  DECIMAL(10,2) DEFAULT 0,
    tours_conducted INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Revenue Tracking Table
CREATE TABLE IF NOT EXISTS revenue_tracking (
    revenue_id       INT PRIMARY KEY AUTO_INCREMENT,
    transaction_date DATE NOT NULL,
    revenue_type     ENUM('ticket', 'tour', 'shop', 'event', 'other') NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    description      TEXT,
    reference_id     INT,
    payment_method   VARCHAR(50),
    recorded_by      INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES admin_users(admin_id)
);

-- Performance Metrics Table
CREATE TABLE IF NOT EXISTS performance_metrics (
    metric_id    INT PRIMARY KEY AUTO_INCREMENT,
    metric_date  DATE NOT NULL,
    metric_type  VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    target_value DECIMAL(10,2),
    percentage   DECIMAL(5,2),
    status       ENUM('excellent', 'good', 'needs_improvement', 'critical'),
    notes        TEXT,
    UNIQUE KEY (metric_date, metric_type)
);

-- ============================================================
-- TICKETING STAFF — ENTRY LOG
-- ============================================================

CREATE TABLE IF NOT EXISTS entry_log (
    log_id     INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id  INT NOT NULL,
    scanned_by INT NOT NULL,
    scan_time  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    entry_type ENUM('entry','exit') DEFAULT 'entry',
    notes      TEXT,
    FOREIGN KEY (ticket_id)  REFERENCES tickets(ticket_id),
    FOREIGN KEY (scanned_by) REFERENCES admin_users(admin_id)
);

-- ============================================================
-- TOUR GUIDE — ISSUE REPORTING
-- ============================================================

CREATE TABLE IF NOT EXISTS tour_issues (
    issue_id    INT PRIMARY KEY AUTO_INCREMENT,
    tour_id     INT NOT NULL,
    reported_by INT NOT NULL,
    issue_type  VARCHAR(100),
    description TEXT NOT NULL,
    severity    ENUM('low','medium','high') DEFAULT 'medium',
    status      ENUM('open','in_progress','resolved') DEFAULT 'open',
    resolution  TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (tour_id)     REFERENCES tours(tour_id),
    FOREIGN KEY (reported_by) REFERENCES admin_users(admin_id)
);

-- ============================================================
-- PROMO CODES
-- ============================================================

CREATE TABLE IF NOT EXISTS promo_codes (
    promo_id       INT PRIMARY KEY AUTO_INCREMENT,
    code           VARCHAR(50) UNIQUE NOT NULL,
    discount_type  ENUM('percentage','fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase   DECIMAL(10,2) DEFAULT 0,
    max_uses       INT DEFAULT NULL,
    uses_count     INT DEFAULT 0,
    valid_from     DATE,
    valid_until    DATE,
    applicable_to  ENUM('tickets','products','tours','all') DEFAULT 'all',
    status         ENUM('active','inactive','expired') DEFAULT 'active',
    created_by     INT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL
);

-- ============================================================
-- VISITOR SHOPPING CART
-- ============================================================

CREATE TABLE IF NOT EXISTS cart (
    cart_id    INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_items (
    item_id    INT PRIMARY KEY AUTO_INCREMENT,
    cart_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id)    REFERENCES cart(cart_id)     ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default super admin (password: admin123)
INSERT INTO admin_users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$vk5lmCRFMdkvEA1.r7u6oe37a5XJs94A8z0XCfzhPSwBViw9Ke/Ou', 'System Administrator', 'admin@museum.com', 'super_admin');

-- Staff accounts (password: Staff@123)
INSERT IGNORE INTO admin_users (username, password, full_name, email, role) VALUES
('manager',      '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Maria Santos',      'manager@museum.com',      'manager'),
('ticketing1',   '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Ana Cruz',          'ticketing1@museum.com',   'ticketing_staff'),
('ticketing2',   '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Ben Reyes',         'ticketing2@museum.com',   'ticketing_staff'),
('guide1',       '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Carlos Dela Cruz',  'guide1@museum.com',       'tour_guide'),
('guide2',       '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Diana Villanueva',  'guide2@museum.com',       'tour_guide'),
('maintenance1', '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Eduardo Lim',       'maintenance1@museum.com', 'maintenance_staff'),
('maintenance2', '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Fiona Tan',         'maintenance2@museum.com', 'maintenance_staff'),
('shopstaff1',   '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'George Garcia',     'shopstaff1@museum.com',   'shop_staff'),
('shopstaff2',   '$2y$10$CqJ6FSkcPKITd/FqBylND.Z1LS9ZLUj5ikdATDdYOH1ng92qhhmfe', 'Helen Aquino',      'shopstaff2@museum.com',   'shop_staff');

-- Sample regular visitor (password: user123)
INSERT INTO users (username, password, full_name, email, phone, status) VALUES
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Visitor', 'john@email.com', '555-0100', 'active');

-- Exhibit classifications
INSERT INTO exhibit_classifications (name, description) VALUES
('Ancient History',       'Artifacts and exhibits from ancient civilizations'),
('Modern Art',            'Contemporary artworks from the 20th and 21st centuries'),
('Natural History',       'Natural specimens and geological exhibits'),
('Cultural Heritage',     'Cultural artifacts and traditional items'),
('Science & Technology',  'Scientific instruments and technological innovations');

-- Ticket type pricing
INSERT INTO ticket_types (ticket_type, price) VALUES
('adult',   500.00),
('child',   400.00),
('senior',  350.00),
('student', 350.00),
('group',   400.00);

-- Locations
INSERT INTO locations (name, floor, capacity, description) VALUES
('Main Gallery',        '1st Floor',   100, 'Primary exhibition space'),
('East Wing',           '1st Floor',    50, 'Secondary exhibition area'),
('West Wing',           '2nd Floor',    75, 'Rotating exhibitions'),
('Special Collections', '2nd Floor',    30, 'Premium exhibits'),
('Sculpture Garden',    'Ground Floor', 150, 'Outdoor sculpture display');

-- Artworks
INSERT INTO artworks (title, artist, type, description, year_created, location_id, condition_status, image_path) VALUES
('Spoliarium',                  'Juan Luna',        'painting', 'A monumental painting depicting scenes of ancient Rome, showcasing the artist\'s exceptional skill in capturing human emotion and historical narrative. It won the gold medal at the 1884 Exposición Nacional de Bellas Artes in Madrid.',                                                                   '1884', 1, 'excellent', 'img/Spoliarium.jpg'),
('The Parisian Life',           'Juan Luna',        'painting', 'A vibrant portrayal of Parisian social life, reflecting the cosmopolitan world that Juan Luna inhabited during his years in Europe.',                                                                                                                                                                       '1892', 1, 'excellent', 'img/artwork_1772097226_69a00ecaecb12.jpg'),
('The Blood Compact',           'Juan Luna',        'painting', 'Depicts the blood compact ritual between Sikatuna and Spanish explorer Miguel López de Legazpi, symbolizing the bond between the Filipino and Spanish peoples.',                                                                                                                                             '1886', 1, 'good',      'img/artwork_1772097237_69a00ed5c3384.jpg'),
('Tampuhan',                    'Juan Luna',        'painting', 'A tender domestic scene capturing a quarrel between lovers, set in a lush tropical environment. It is one of Luna\'s most beloved genre paintings.',                                                                                                                                                        '1895', 1, 'excellent', 'img/artwork_1772097249_69a00ee167b04.jpg'),
('Planting Rice',               'Fernando Amorsolo','painting', 'A masterpiece of Philippine rural life showing farmers planting rice under golden sunlight. Amorsolo\'s signature use of backlighting gives the scene a luminous, idyllic quality.',                                                                                                                       '1951', 1, 'excellent', 'img/artwork_1772097259_69a00eeba3fdd.jpg'),
('Afternoon Meal of the Workers','Fernando Amorsolo','painting','Depicts Filipino farm workers sharing a simple midday meal, bathed in warm afternoon light. A celebration of everyday life and communal spirit.',                                                                                                                                                            '1938', 2, 'good',      'img/artwork_1772097280_69a00f00e0e22.jpg'),
('Dalagang Bukid',              'Fernando Amorsolo','painting', 'A portrait of a young Filipino woman in traditional Barong dress, surrounded by tropical foliage. One of Amorsolo\'s most iconic representations of Filipino femininity.',                                                                                                                                  '1942', 2, 'excellent', 'img/artwork_1772097293_69a00f0d97c83.jpg'),
('The Fruit Gatherers',         'Fernando Amorsolo','painting', 'Young women picking tropical fruit in a sun-drenched orchard. The painting highlights Amorsolo\'s mastery of light and his enduring affection for rural Philippine scenery.',                                                                                                                               '1955', 2, 'good',      'img/artwork_1772097305_69a00f192bad0.png'),
('Under the Mango Tree',        'Fernando Amorsolo','painting', 'A serene pastoral scene showing a family resting beneath the shade of a large mango tree. Evokes the gentle pace of Filipino provincial life.',                                                                                                                                                             '1949', 2, 'excellent', 'img/artwork_1772097318_69a00f2669295.jpg'),
('The Builders',                'Fernando Amorsolo','painting', 'Depicts laborers at work constructing a building, capturing the dignity and strength of the Filipino working class in post-war Manila.',                                                                                                                                                                    '1948', 3, 'good',      'img/artwork_1772097335_69a00f3783264.jpg'),
('España y Filipinas',          'Juan Luna',        'painting', 'An allegorical work portraying the relationship between Spain and the Philippines as two women, symbolizing the colonial bond in a neoclassical style.',                                                                                                                                                    '1884', 1, 'fair',      'img/artwork_1772097347_69a00f43e0973.png'),
('La Bulaqueña',                'Fernando Amorsolo','painting', 'A celebrated portrait of a woman from Bulacan province wearing a traditional pañuelo and saya. Considered one of the finest examples of Amorsolo\'s figure painting.',                                                                                                                                     '1943', 3, 'excellent', 'img/artwork_1772097357_69a00f4d63577.jpg'),
('The Mestiza',                 'Fernando Amorsolo','painting', 'Portrays a mestiza woman in traditional finery, celebrating the blend of indigenous and Spanish heritage in Philippine culture and identity.',                                                                                                                                                               '1944', 3, 'good',      'img/artwork_1772097370_69a00f5a52a78.jpg'),
('Hymen O Hymenae!',            'Juan Luna',        'painting', 'A mythological scene depicting a wedding procession in ancient Greece or Rome, showcasing Luna\'s academic training and command of large-scale figurative composition.',                                                                                                                                    '1885', 4, 'good',      'img/artwork_1772097389_69a00f6d82c6e.jpg'),
('Los Indios Bravos',           'Juan Luna',        'painting', 'A dynamic painting depicting indigenous warriors, reflecting Luna\'s growing nationalist sentiment during his time in Europe.',                                                                                                                                                                             '1898', 4, 'fair',      'img/artwork_1772097401_69a00f79a1e0e.jpg'),
('Woman with a Fan',            'Fernando Amorsolo','painting', 'A graceful portrait of a Filipino woman elegantly holding a hand fan, painted with Amorsolo\'s characteristic warm palette and attention to fabric and texture.',                                                                                                                                           '1952', 4, 'excellent', 'img/artwork_1772097412_69a00f8462ec5.jpg'),
('El Ciego',                    'Juan Luna',        'painting', 'A poignant portrayal of a blind beggar, demonstrating Luna\'s empathy for the marginalized and his skill in rendering human suffering with dignity.',                                                                                                                                                       '1887', 4, 'good',      'img/artwork_1772097426_69a00f9228f71.jpg'),
('Fisherman at Sunset',         'Fernando Amorsolo','painting', 'A fisherman silhouetted against a blazing Philippine sunset, casting his net into the sea. Showcases Amorsolo\'s extraordinary ability to paint light and reflection on water.',                                                                                                                            '1950', 5, 'good',      'img/artwork_1772097481_69a00fc918a00.jpg'),
('Filipino Family',             'Fernando Amorsolo','painting', 'A warm and tender depiction of a Filipino family gathered together in a rural home, symbolizing unity, love, and the central importance of family in Philippine culture.',                                                                                                                                  '1946', 5, 'excellent', 'img/artwork_1772097512_69a00fe839921.jpg');

-- Equipment
INSERT INTO equipment (name, equipment_type, location_id, purchase_date, status, description) VALUES
('HVAC System - Main Gallery',      'Climate Control',  1, '2023-01-15', 'operational', 'Central air conditioning for main gallery'),
('Security Camera Set - East Wing', 'Security',         2, '2023-03-20', 'operational', '8-camera surveillance system'),
('LED Lighting System',             'Lighting',         1, '2023-05-10', 'operational', 'Energy-efficient gallery lighting'),
('Fire Suppression System',         'Safety',           1, '2022-11-05', 'operational', 'Automated fire detection and suppression'),
('Audio Guide System',              'Visitor Service',  1, '2023-07-12', 'operational', '50 wireless audio guide devices');

-- Product categories
INSERT INTO product_categories (name, description) VALUES
('Books & Publications',    'Museum catalogs, art books, and educational materials'),
('Replicas & Collectibles', 'Miniature replicas and collectible items'),
('Apparel & Accessories',   'T-shirts, bags, and accessories'),
('Postcards & Stationery',  'Postcards, notepads, and stationery items'),
('Educational Toys',        'Learning toys and activity kits for children');

-- Products
INSERT INTO products (name, category_id, sku, price, cost_price, stock_quantity, reorder_level, status) VALUES
('Museum Catalog 2026',          1, 'BK-CAT-2026',    450.00, 200.00,  50, 10, 'active'),
('Ancient Vase Replica',         2, 'REP-VASE-001',   850.00, 400.00,  15,  5, 'active'),
('Museum Logo T-Shirt',          3, 'APP-TSHIRT-001', 350.00, 150.00, 100, 20, 'active'),
('Postcard Set - Famous Artworks',4,'PC-SET-001',     120.00,  50.00, 200, 30, 'active'),
('Archaeological Dig Kit',       5, 'TOY-DIG-001',    650.00, 300.00,  30, 10, 'active');

-- Feedback categories
INSERT INTO feedback_categories (name, description) VALUES
('General Experience', 'Overall museum visit experience'),
('Exhibitions',        'Feedback about specific exhibitions'),
('Staff Service',      'Comments about staff and service quality'),
('Facilities',         'Feedback about facilities and amenities'),
('Suggestions',        'Improvement suggestions and ideas');

-- Promo codes
INSERT IGNORE INTO promo_codes (code, discount_type, discount_value, valid_from, valid_until, applicable_to, status) VALUES
('WELCOME10', 'percentage', 10.00, '2026-01-01', '2026-12-31', 'all',      'active'),
('MUSEUM50',  'fixed',      50.00, '2026-01-01', '2026-12-31', 'tickets',  'active'),
('TOUR15',    'percentage', 15.00, '2026-03-01', '2026-09-30', 'tours',    'active'),
('SHOP20',    'percentage', 20.00, '2026-03-01', '2026-06-30', 'products', 'active');

-- Tour guide records (linked to admin accounts)
INSERT IGNORE INTO tour_guides (full_name, email, phone, specialization, status)
  SELECT full_name, email, NULL, 'General Tours', 'active'
  FROM admin_users
  WHERE role = 'tour_guide'
    AND email NOT IN (SELECT email FROM tour_guides WHERE email IS NOT NULL);

-- Link guide records to their admin_users rows
UPDATE tour_guides SET admin_id = (SELECT admin_id FROM admin_users WHERE username='guide1' LIMIT 1) WHERE full_name='Carlos Dela Cruz';
UPDATE tour_guides SET admin_id = (SELECT admin_id FROM admin_users WHERE username='guide2' LIMIT 1) WHERE full_name='Diana Villanueva';

-- Sample tours (inserted after guides exist)
INSERT IGNORE INTO tours (title, description, guide_id, tour_date, start_time, end_time, max_capacity, price, status)
  SELECT 'Morning Heritage Walk', 'Explore the ancient history section with our expert guide.',
         g.guide_id, CURDATE(), '09:00:00', '10:30:00', 20, 150.00, 'scheduled'
  FROM tour_guides g WHERE g.full_name='Carlos Dela Cruz' LIMIT 1;

INSERT IGNORE INTO tours (title, description, guide_id, tour_date, start_time, end_time, max_capacity, price, status)
  SELECT 'Afternoon Modern Art Tour', 'Guided walkthrough of modern and contemporary art exhibits.',
         g.guide_id, CURDATE(), '14:00:00', '15:30:00', 15, 150.00, 'scheduled'
  FROM tour_guides g WHERE g.full_name='Diana Villanueva' LIMIT 1;

SELECT 'eMuse_db setup completed successfully!' AS status;

