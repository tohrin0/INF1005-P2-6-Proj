-- Create database if not exists
CREATE DATABASE IF NOT EXISTS flight_booking;
USE flight_booking;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    reset_token VARCHAR(100) NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Flights table - enhanced for Aviation Stack API
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(20) NOT NULL,
    departure VARCHAR(100) NOT NULL,
    arrival VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    duration INT NOT NULL DEFAULT 0 COMMENT 'Duration in minutes',
    price DECIMAL(10, 2) NOT NULL,
    available_seats INT NOT NULL DEFAULT 100,
    airline VARCHAR(100) NULL,
    departure_gate VARCHAR(10) NULL,
    arrival_gate VARCHAR(10) NULL,
    departure_terminal VARCHAR(10) NULL,
    arrival_terminal VARCHAR(10) NULL,
    status VARCHAR(20) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (flight_number, date)
);

-- Bookings table - 
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_id VARCHAR(255) NOT NULL, -- Changed to VARCHAR as it's from API
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    passengers INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(100) NOT NULL DEFAULT 'Flight Booking Website',
    site_email VARCHAR(100) NOT NULL DEFAULT 'info@example.com',
    site_phone VARCHAR(20) NOT NULL DEFAULT '+1234567890',
    api_cache_time INT NOT NULL DEFAULT 3600 COMMENT 'API cache time in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- FAQ table (added to support the FAQ page)
CREATE TABLE faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table (added to support the contact form)
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'responded') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password is "password")
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default settings
INSERT INTO settings (site_name, site_email, site_phone) VALUES 
('Flight Booking Website', 'info@flightbooking.com', '+1234567890');

-- Insert sample FAQ entries
INSERT INTO faq (question, answer, display_order) VALUES
('What is the flight booking process?', 'The flight booking process involves searching for flights, selecting your preferred flight, entering passenger details, and making a payment.', 1),
('How can I change or cancel my booking?', 'You can change or cancel your booking by logging into your account and navigating to the \'My Bookings\' section. Please note that cancellation policies may apply.', 2),
('What payment methods are accepted?', 'We accept various payment methods including credit cards, debit cards, and PayPal. Please check the payment page for more details.', 3),
('How do I contact customer support?', 'You can contact our customer support team via the contact form on our website or by emailing support@flightbooking.com.', 4),
('Is my personal information safe?', 'Yes, we take your privacy seriously. We use encryption and secure protocols to protect your personal information.', 5);

-- Insert sample flights for testing
INSERT INTO flights (flight_number, departure, arrival, date, time, duration, price, available_seats, airline, status) VALUES
('FL1001', 'New York', 'Los Angeles', CURDATE(), '08:00:00', 360, 299.99, 120, 'American Airlines', 'scheduled'),
('FL1002', 'Chicago', 'Miami', CURDATE(), '10:30:00', 180, 199.99, 85, 'Delta Air Lines', 'scheduled'),
('FL1003', 'San Francisco', 'Seattle', CURDATE(), '12:15:00', 120, 149.99, 65, 'United Airlines', 'scheduled'),
('FL1004', 'Boston', 'Washington DC', CURDATE(), '14:45:00', 90, 129.99, 100, 'JetBlue', 'scheduled'),
('FL1005', 'Las Vegas', 'Phoenix', CURDATE(), '16:30:00', 60, 99.99, 150, 'Southwest', 'scheduled'),
('FL1006', 'Orlando', 'Atlanta', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 120, 179.99, 200, 'Delta Air Lines', 'scheduled'),
('FL1007', 'Denver', 'Dallas', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:30:00', 150, 219.99, 80, 'American Airlines', 'scheduled'),
('FL1008', 'Los Angeles', 'New York', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '07:15:00', 330, 329.99, 110, 'United Airlines', 'scheduled'),
('FL1009', 'Miami', 'Chicago', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:45:00', 190, 209.99, 95, 'JetBlue', 'scheduled'),
('FL1010', 'Seattle', 'San Francisco', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 130, 159.99, 75, 'Southwest', 'scheduled');