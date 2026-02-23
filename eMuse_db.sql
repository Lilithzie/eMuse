-- Museum Management System Database
CREATE DATABASE IF NOT EXISTS eMuse_db;
USE eMuse_db;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Regular Users Table (Visitors/Customers)
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Exhibit Classifications Table
CREATE TABLE IF NOT EXISTS exhibit_classifications (
    classification_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exhibits Table
CREATE TABLE IF NOT EXISTS exhibits (
    exhibit_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    classification_id INT,
    start_date DATE,
    end_date DATE,
    status ENUM('upcoming', 'active', 'closed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (classification_id) REFERENCES exhibit_classifications(classification_id)
);

-- Locations Table
CREATE TABLE IF NOT EXISTS locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    floor VARCHAR(20),
    capacity INT,
    description TEXT
);

-- Artworks and Artifacts Table
CREATE TABLE IF NOT EXISTS artworks (
    artwork_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    artist VARCHAR(100),
    type ENUM('painting', 'sculpture', 'artifact', 'photograph', 'other') NOT NULL,
    description TEXT,
    year_created VARCHAR(20),
    exhibit_id INT,
    location_id INT,
    acquisition_date DATE,
    condition_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exhibit_id) REFERENCES exhibits(exhibit_id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    visitor_name VARCHAR(100) NOT NULL,
    visitor_email VARCHAR(100),
    visitor_phone VARCHAR(20),
    ticket_type ENUM('adult', 'child', 'senior', 'student', 'group') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    visit_date DATE NOT NULL,
    qr_code VARCHAR(255),
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'used', 'cancelled') DEFAULT 'confirmed',
    scanned_at TIMESTAMP NULL,
    scanned_by INT,
    user_id INT NULL,
    FOREIGN KEY (scanned_by) REFERENCES admin_users(admin_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Tour Guides Table
CREATE TABLE IF NOT EXISTS tour_guides (
    guide_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    specialization VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tours Table
CREATE TABLE IF NOT EXISTS tours (
    tour_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    guide_id INT,
    tour_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT NOT NULL,
    current_bookings INT DEFAULT 0,
    price DECIMAL(10,2),
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES tour_guides(guide_id)
);

-- Tour Bookings Table
CREATE TABLE IF NOT EXISTS tour_bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    tour_id INT NOT NULL,
    ticket_id INT,
    visitor_name VARCHAR(100) NOT NULL,
    visitor_email VARCHAR(100),
    number_of_people INT DEFAULT 1,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    user_id INT NULL,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Visitor Statistics Table
CREATE TABLE IF NOT EXISTS visitor_stats (
    stat_id INT PRIMARY KEY AUTO_INCREMENT,
    visit_date DATE NOT NULL,
    total_visitors INT DEFAULT 0,
    online_tickets INT DEFAULT 0,
    walk_in_tickets INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0,
    UNIQUE KEY (visit_date)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, full_name, email, role) 
VALUES ('admin', '$2y$10$vk5lmCRFMdkvEA1.r7u6oe37a5XJs94A8z0XCfzhPSwBViw9Ke/Ou', 'System Administrator', 'admin@museum.com', 'super_admin');

-- Insert sample regular user (password: user123)
INSERT INTO users (username, password, full_name, email, phone, status) VALUES
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Visitor', 'john@email.com', '555-0100', 'active');

-- Insert sample exhibit classifications
INSERT INTO exhibit_classifications (name, description) VALUES
('Ancient History', 'Artifacts and exhibits from ancient civilizations'),
('Modern Art', 'Contemporary artworks from the 20th and 21st centuries'),
('Natural History', 'Natural specimens and geological exhibits'),
('Cultural Heritage', 'Cultural artifacts and traditional items'),
('Science & Technology', 'Scientific instruments and technological innovations');

-- Insert sample locations
INSERT INTO locations (name, floor, capacity, description) VALUES
('Main Gallery', '1st Floor', 100, 'Primary exhibition space'),
('East Wing', '1st Floor', 50, 'Secondary exhibition area'),
('West Wing', '2nd Floor', 75, 'Rotating exhibitions'),
('Special Collections', '2nd Floor', 30, 'Premium exhibits'),
('Sculpture Garden', 'Ground Floor', 150, 'Outdoor sculpture display');

-- ========================================
-- ADDITIONAL FEATURES (Added Feb 2026)
-- ========================================

-- ========================================
-- MAINTENANCE TRACKING TABLES
-- ========================================

-- Equipment Table
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    equipment_type VARCHAR(100) NOT NULL,
    location_id INT,
    purchase_date DATE,
    warranty_expiry DATE,
    status ENUM('operational', 'maintenance', 'repair', 'retired') DEFAULT 'operational',
    serial_number VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- Maintenance Records Table
CREATE TABLE IF NOT EXISTS maintenance_records (
    maintenance_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    maintenance_type ENUM('routine', 'preventive', 'repair', 'inspection') NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE,
    performed_by VARCHAR(100),
    cost DECIMAL(10,2),
    description TEXT,
    notes TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
);

-- Maintenance Alerts Table
CREATE TABLE IF NOT EXISTS maintenance_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    alert_type ENUM('due', 'overdue', 'urgent', 'warranty_expiring') NOT NULL,
    message TEXT NOT NULL,
    due_date DATE,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES admin_users(admin_id)
);

-- ========================================
-- SOUVENIR SHOP MANAGEMENT TABLES
-- ========================================

-- Product Categories Table
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    sku VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    supplier VARCHAR(200),
    image_path VARCHAR(255),
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id)
);

-- Sales Table
CREATE TABLE IF NOT EXISTS product_sales (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'online') NOT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    served_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (served_by) REFERENCES admin_users(admin_id)
);

-- Sale Items Table
CREATE TABLE IF NOT EXISTS sale_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES product_sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Stock Alerts Table
CREATE TABLE IF NOT EXISTS stock_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    alert_type ENUM('low_stock', 'out_of_stock', 'reorder') NOT NULL,
    message TEXT NOT NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ========================================
-- VISITOR FEEDBACK TABLES
-- ========================================

-- Feedback Categories Table
CREATE TABLE IF NOT EXISTS feedback_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitor Feedback Table
CREATE TABLE IF NOT EXISTS visitor_feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_name VARCHAR(100),
    visitor_email VARCHAR(100),
    category_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    feedback_text TEXT NOT NULL,
    visit_date DATE,
    exhibition_rating INT CHECK (exhibition_rating >= 1 AND exhibition_rating <= 5),
    staff_rating INT CHECK (staff_rating >= 1 AND staff_rating <= 5),
    facilities_rating INT CHECK (facilities_rating >= 1 AND facilities_rating <= 5),
    recommend ENUM('yes', 'no', 'maybe'),
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    admin_response TEXT,
    responded_by INT,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES feedback_categories(category_id),
    FOREIGN KEY (responded_by) REFERENCES admin_users(admin_id)
);

-- Feedback Statistics Table
CREATE TABLE IF NOT EXISTS feedback_statistics (
    stat_id INT PRIMARY KEY AUTO_INCREMENT,
    period_date DATE NOT NULL,
    total_feedback INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    positive_count INT DEFAULT 0,
    neutral_count INT DEFAULT 0,
    negative_count INT DEFAULT 0,
    response_rate DECIMAL(5,2),
    UNIQUE KEY (period_date)
);

-- ========================================
-- REPORTS & ANALYTICS TABLES
-- ========================================

-- Daily Reports Table
CREATE TABLE IF NOT EXISTS daily_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL UNIQUE,
    total_visitors INT DEFAULT 0,
    adult_tickets INT DEFAULT 0,
    child_tickets INT DEFAULT 0,
    senior_tickets INT DEFAULT 0,
    student_tickets INT DEFAULT 0,
    group_tickets INT DEFAULT 0,
    ticket_revenue DECIMAL(10,2) DEFAULT 0,
    tour_revenue DECIMAL(10,2) DEFAULT 0,
    shop_revenue DECIMAL(10,2) DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    tours_conducted INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Revenue Tracking Table
CREATE TABLE IF NOT EXISTS revenue_tracking (
    revenue_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_date DATE NOT NULL,
    revenue_type ENUM('ticket', 'tour', 'shop', 'event', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_id INT,
    payment_method VARCHAR(50),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES admin_users(admin_id)
);

-- Performance Metrics Table
CREATE TABLE IF NOT EXISTS performance_metrics (
    metric_id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    target_value DECIMAL(10,2),
    percentage DECIMAL(5,2),
    status ENUM('excellent', 'good', 'needs_improvement', 'critical'),
    notes TEXT,
    UNIQUE KEY (metric_date, metric_type)
);

-- ========================================
-- SAMPLE DATA FOR NEW FEATURES
-- ========================================

-- Sample Equipment
INSERT INTO equipment (name, equipment_type, location_id, purchase_date, status, description) VALUES
('HVAC System - Main Gallery', 'Climate Control', 1, '2023-01-15', 'operational', 'Central air conditioning for main gallery'),
('Security Camera Set - East Wing', 'Security', 2, '2023-03-20', 'operational', '8-camera surveillance system'),
('LED Lighting System', 'Lighting', 1, '2023-05-10', 'operational', 'Energy-efficient gallery lighting'),
('Fire Suppression System', 'Safety', 1, '2022-11-05', 'operational', 'Automated fire detection and suppression'),
('Audio Guide System', 'Visitor Service', 1, '2023-07-12', 'operational', '50 wireless audio guide devices');

-- Sample Product Categories
INSERT INTO product_categories (name, description) VALUES
('Books & Publications', 'Museum catalogs, art books, and educational materials'),
('Replicas & Collectibles', 'Miniature replicas and collectible items'),
('Apparel & Accessories', 'T-shirts, bags, and accessories'),
('Postcards & Stationery', 'Postcards, notepads, and stationery items'),
('Educational Toys', 'Learning toys and activity kits for children');

-- Sample Products
INSERT INTO products (name, category_id, sku, price, cost_price, stock_quantity, reorder_level, status) VALUES
('Museum Catalog 2026', 1, 'BK-CAT-2026', 450.00, 200.00, 50, 10, 'active'),
('Ancient Vase Replica', 2, 'REP-VASE-001', 850.00, 400.00, 15, 5, 'active'),
('Museum Logo T-Shirt', 3, 'APP-TSHIRT-001', 350.00, 150.00, 100, 20, 'active'),
('Postcard Set - Famous Artworks', 4, 'PC-SET-001', 120.00, 50.00, 200, 30, 'active'),
('Archaeological Dig Kit', 5, 'TOY-DIG-001', 650.00, 300.00, 30, 10, 'active');

-- Sample Feedback Categories
INSERT INTO feedback_categories (name, description) VALUES
('General Experience', 'Overall museum visit experience'),
('Exhibitions', 'Feedback about specific exhibitions'),
('Staff Service', 'Comments about staff and service quality'),
('Facilities', 'Feedback about facilities and amenities'),
('Suggestions', 'Improvement suggestions and ideas');

