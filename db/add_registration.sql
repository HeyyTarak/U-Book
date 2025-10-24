-- Add registration and payment support
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS student_id VARCHAR(50),
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add payment tracking to bookings
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS payment_date TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS qr_code_data TEXT,
ADD COLUMN IF NOT EXISTS ticket_number VARCHAR(100);

-- Create tickets table for digital passes
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_number VARCHAR(100) UNIQUE,
    qr_code_data TEXT,
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    checked_in_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Update existing events with sample prices
UPDATE events SET price = 25.00 WHERE id = 1; -- Tech Fest
UPDATE events SET price = 15.00 WHERE id = 2; -- Music Night
UPDATE events SET price = 0.00 WHERE id = 3;  -- Startup Pitch (Free)
UPDATE events SET price = 10.00 WHERE id = 4; -- Art Workshop

-- Add more sample events with prices
INSERT INTO events (name, description, event_date, venue, total_tickets, available_tickets, price, category_id, image_url) VALUES
('Hackathon 2024', '24-hour coding competition with amazing prizes', '2024-04-15 09:00:00', 'Tech Building', 100, 100, 0.00, 1, 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400'),
('DJ Night', 'Electronic music night with professional DJs', '2024-04-20 20:00:00', 'Student Center', 200, 200, 20.00, 2, 'https://images.unsplash.com/photo-1571266028243-43a4d4dff852?w=400'),
('Basketball Tournament', 'Inter-college basketball championship', '2024-04-25 14:00:00', 'Sports Arena', 300, 300, 5.00, 3, 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=400');