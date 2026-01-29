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
    FOREIGN KEY (scanned_by) REFERENCES admin_users(admin_id)
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
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
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
