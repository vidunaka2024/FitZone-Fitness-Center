-- Sample data for FitZone Fitness Center
-- Run this after importing the main schema

-- Insert sample trainers
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `phone`, `gender`, `role`, `status`, `created_at`) VALUES
('Sarah', 'Johnson', 'sarah.johnson@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0101', 'female', 'trainer', 'active', NOW()),
('Mike', 'Thompson', 'mike.thompson@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0102', 'male', 'trainer', 'active', NOW()),
('Maria', 'Rodriguez', 'maria.rodriguez@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0103', 'female', 'trainer', 'active', NOW()),
('David', 'Lee', 'david.lee@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0104', 'male', 'trainer', 'active', NOW()),
('Emma', 'Wilson', 'emma.wilson@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0105', 'female', 'trainer', 'active', NOW()),
('John', 'Martinez', 'john.martinez@fitzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0106', 'male', 'trainer', 'active', NOW());

-- Get trainer IDs for references (assuming they start from a certain ID)
-- You may need to adjust these IDs based on your actual data

-- Insert trainer profiles (adjust user_id values based on actual trainer IDs)
INSERT INTO `trainer_profiles` (`user_id`, `bio`, `specializations`, `certifications`, `experience_years`, `hourly_rate`, `is_accepting_clients`, `rating`, `total_reviews`) VALUES
((SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Certified yoga instructor with 8+ years of experience specializing in Hatha and Vinyasa yoga.', '["Hatha Yoga", "Vinyasa", "Pilates", "Meditation"]', '["RYT-500", "PMA-CPT"]', 8, 75.00, 1, 4.9, 127),
((SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'Former collegiate athlete turned strength coach, specializing in functional fitness and sports performance.', '["CrossFit", "Powerlifting", "Sports Performance", "Strength Training"]', '["CSCS", "NASM-CPT", "CrossFit Level 2"]', 12, 85.00, 1, 4.8, 95),
((SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Dance fitness expert bringing energy and fun to every workout session.', '["Zumba", "Latin Dance", "Cardio Dance", "HIIT"]', '["ZIN", "AFAA", "Group Fitness"]', 6, 70.00, 1, 4.7, 89),
((SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cardio specialist focused on weight loss and endurance training programs.', '["HIIT", "Spinning", "Cardio", "Weight Loss"]', '["ACE-CPT", "Spinning Instructor", "TRX"]', 10, 80.00, 1, 4.9, 112),
((SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Physical therapist and fitness expert specializing in injury prevention and rehabilitation.', '["Injury Prevention", "Corrective Exercise", "Senior Fitness", "Pilates"]', '["DPT", "ACSM-CPT", "FMS"]', 15, 90.00, 1, 5.0, 156),
((SELECT id FROM users WHERE email = 'john.martinez@fitzone.com'), 'Functional movement specialist focused on real-world strength and mobility.', '["Functional Movement", "TRX", "Bootcamp", "Mobility"]', '["FMS", "TRX-STC", "NASM-CES"]', 9, 75.00, 1, 4.8, 78);

-- Insert class schedules for the next 2 weeks
-- First, let's create schedules for existing classes

-- Zumba Dance Fitness (Class ID 1) - Mon, Wed, Fri at 7:00 PM with Maria
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 1 DAY, '19:00:00', '19:45:00', 5, 'scheduled'),
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 3 DAY, '19:00:00', '19:45:00', 8, 'scheduled'),
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 5 DAY, '19:00:00', '19:45:00', 3, 'scheduled'),
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 8 DAY, '19:00:00', '19:45:00', 2, 'scheduled'),
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 10 DAY, '19:00:00', '19:45:00', 0, 'scheduled'),
(1, (SELECT id FROM users WHERE email = 'maria.rodriguez@fitzone.com'), 'Studio A', CURDATE() + INTERVAL 12 DAY, '19:00:00', '19:45:00', 1, 'scheduled');

-- Hatha Yoga (Class ID 2) - Daily at 8:00 AM and 6:00 PM with Sarah
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 1 DAY, '08:00:00', '09:00:00', 12, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 1 DAY, '18:00:00', '19:00:00', 15, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 2 DAY, '08:00:00', '09:00:00', 8, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 2 DAY, '18:00:00', '19:00:00', 10, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 3 DAY, '08:00:00', '09:00:00', 6, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 3 DAY, '18:00:00', '19:00:00', 18, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 4 DAY, '08:00:00', '09:00:00', 4, 'scheduled'),
(2, (SELECT id FROM users WHERE email = 'sarah.johnson@fitzone.com'), 'Yoga Studio', CURDATE() + INTERVAL 4 DAY, '18:00:00', '19:00:00', 14, 'scheduled');

-- CrossFit WOD (Class ID 3) - Mon-Fri at 6:00 AM and 7:00 PM with Mike
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 1 DAY, '06:00:00', '06:50:00', 10, 'scheduled'),
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 1 DAY, '19:00:00', '19:50:00', 13, 'scheduled'),
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 2 DAY, '06:00:00', '06:50:00', 8, 'scheduled'),
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 2 DAY, '19:00:00', '19:50:00', 15, 'scheduled'),
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 3 DAY, '06:00:00', '06:50:00', 7, 'scheduled'),
(3, (SELECT id FROM users WHERE email = 'mike.thompson@fitzone.com'), 'CrossFit Box', CURDATE() + INTERVAL 3 DAY, '19:00:00', '19:50:00', 14, 'scheduled');

-- Indoor Cycling (Class ID 4) - Tue, Thu, Sat at 7:30 AM and 5:30 PM with David
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 2 DAY, '07:30:00', '08:15:00', 16, 'scheduled'),
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 2 DAY, '17:30:00', '18:15:00', 12, 'scheduled'),
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 4 DAY, '07:30:00', '08:15:00', 9, 'scheduled'),
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 4 DAY, '17:30:00', '18:15:00', 18, 'scheduled'),
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 6 DAY, '07:30:00', '08:15:00', 11, 'scheduled'),
(4, (SELECT id FROM users WHERE email = 'david.lee@fitzone.com'), 'Cycling Studio', CURDATE() + INTERVAL 6 DAY, '17:30:00', '18:15:00', 20, 'scheduled');

-- Pilates Core (Class ID 5) - Mon, Wed, Fri at 9:00 AM and 6:30 PM with Emma
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 1 DAY, '09:00:00', '09:55:00', 7, 'scheduled'),
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 1 DAY, '18:30:00', '19:25:00', 11, 'scheduled'),
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 3 DAY, '09:00:00', '09:55:00', 5, 'scheduled'),
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 3 DAY, '18:30:00', '19:25:00', 13, 'scheduled'),
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 5 DAY, '09:00:00', '09:55:00', 3, 'scheduled'),
(5, (SELECT id FROM users WHERE email = 'emma.wilson@fitzone.com'), 'Pilates Studio', CURDATE() + INTERVAL 5 DAY, '18:30:00', '19:25:00', 9, 'scheduled');

-- Add more class types if they exist in your database
-- Bootcamp classes with John
INSERT INTO `class_schedules` (`class_id`, `trainer_id`, `room`, `date`, `start_time`, `end_time`, `current_capacity`, `status`) VALUES
(6, (SELECT id FROM users WHERE email = 'john.martinez@fitzone.com'), 'Outdoor Area', CURDATE() + INTERVAL 2 DAY, '06:30:00', '07:20:00', 8, 'scheduled'),
(6, (SELECT id FROM users WHERE email = 'john.martinez@fitzone.com'), 'Main Gym', CURDATE() + INTERVAL 2 DAY, '19:00:00', '19:50:00', 12, 'scheduled'),
(6, (SELECT id FROM users WHERE email = 'john.martinez@fitzone.com'), 'Outdoor Area', CURDATE() + INTERVAL 4 DAY, '06:30:00', '07:20:00', 6, 'scheduled'),
(6, (SELECT id FROM users WHERE email = 'john.martinez@fitzone.com'), 'Main Gym', CURDATE() + INTERVAL 4 DAY, '19:00:00', '19:50:00', 15, 'scheduled');

-- Note: If class ID 6 doesn't exist, you may need to insert a bootcamp class first:
INSERT IGNORE INTO `classes` (`name`, `description`, `type`, `difficulty_level`, `duration_minutes`, `max_capacity`, `calories_burned_estimate`) VALUES
('Military Bootcamp', 'High-intensity military-style workout combining cardio and strength training', 'strength', 'intermediate', 50, 16, 480);

-- Update the bootcamp schedules to use the correct class ID
UPDATE `class_schedules` SET `class_id` = (SELECT id FROM classes WHERE name = 'Military Bootcamp') WHERE `class_id` = 6;