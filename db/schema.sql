-- Create database
CREATE DATABASE IF NOT EXISTS u_book;
USE u_book;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    venue VARCHAR(255) NOT NULL,
    total_tickets INT NOT NULL,
    available_tickets INT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_available_tickets (available_tickets)
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    num_tickets INT NOT NULL,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_event_id (event_id),
    INDEX idx_booking_date (booking_date),
    UNIQUE KEY unique_active_booking (user_id, event_id, status)
);

-- Insert demo data
INSERT INTO users (id, email, name, password, role) VALUES
(1, 'admin@college.edu', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'student@college.edu', 'John Student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert sample events
INSERT INTO events (name, description, event_date, venue, total_tickets, available_tickets, created_by) VALUES
('Tech Symposium 2024', 'Annual technology conference featuring latest innovations and expert talks.', '2024-03-15 09:00:00', 'Main Auditorium', 200, 150, 1),
('Cultural Fest', 'Celebration of diverse cultures with performances, food, and activities.', '2024-03-20 14:00:00', 'College Grounds', 500, 320, 1),
('Career Fair', 'Connect with top companies and explore internship opportunities.', '2024-03-25 10:00:00', 'Convocation Hall', 300, 85, 1),
('Music Concert', 'Live performance by college bands and special guests.', '2024-04-05 18:00:00', 'Open Air Theater', 150, 150, 1);

-- Insert sample bookings
INSERT INTO bookings (user_id, event_id, num_tickets, status) VALUES
(2, 1, 2, 'confirmed'),
(2, 3, 1, 'confirmed');