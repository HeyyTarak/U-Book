-- Enhanced schema with categories, images, and better event management
USE u_book;

-- Add categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6366F1',
    icon VARCHAR(50) DEFAULT '🎯',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Enhanced events table
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS category_id INT,
ADD COLUMN IF NOT EXISTS image_url VARCHAR(500),
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS max_tickets_per_user INT DEFAULT 5,
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS registration_deadline DATETIME,
ADD FOREIGN KEY (category_id) REFERENCES categories(id);

-- Insert categories
INSERT IGNORE INTO categories (id, name, color, icon) VALUES
(1, 'Technology', '#6366F1', '💻'),
(2, 'Music', '#10B981', '🎵'),
(3, 'Sports', '#EF4444', '⚽'),
(4, 'Arts', '#8B5CF6', '🎨'),
(5, 'Business', '#F59E0B', '💼'),
(6, 'Workshop', '#06B6D4', '🔧');

-- Update existing events with categories
UPDATE events SET category_id = 1 WHERE name LIKE '%Tech%';
UPDATE events SET category_id = 2 WHERE name LIKE '%Music%';
UPDATE events SET category_id = 4 WHERE name LIKE '%Art%';
UPDATE events SET category_id = 5 WHERE name LIKE '%Startup%';

-- Add sample images to events
UPDATE events SET image_url = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400' WHERE id = 1;
UPDATE events SET image_url = 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400' WHERE id = 2;
UPDATE events SET image_url = 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=400' WHERE id = 3;
UPDATE events SET image_url = 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400' WHERE id = 4;