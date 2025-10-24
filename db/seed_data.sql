USE u_book;

-- Additional sample users
INSERT INTO users (email, name, password, role) VALUES
('alice@college.edu', 'Alice Johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('bob@college.edu', 'Bob Smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('faculty@college.edu', 'Dr. Sarah Wilson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Additional sample events
INSERT INTO events (name, description, event_date, venue, total_tickets, available_tickets, created_by) VALUES
('Sports Tournament', 'Inter-college basketball and cricket tournaments with exciting prizes.', '2024-04-10 08:00:00', 'Sports Complex', 100, 75, 3),
('Startup Workshop', 'Learn how to launch your own startup from successful entrepreneurs.', '2024-04-12 13:00:00', 'Seminar Hall A', 50, 25, 1),
('Art Exhibition', 'Showcase of student artwork and photography.', '2024-04-18 11:00:00', 'Art Gallery', 80, 80, 3);

-- Additional sample bookings
INSERT INTO bookings (user_id, event_id, num_tickets, status) VALUES
(3, 2, 4, 'confirmed'),
(4, 1, 2, 'confirmed'),
(4, 3, 1, 'cancelled'),
(3, 5, 3, 'confirmed');