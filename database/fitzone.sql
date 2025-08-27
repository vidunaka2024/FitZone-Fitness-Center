-- FitZone Fitness Center Database Schema
-- Created: 2024
-- Description: Complete database structure for FitZone gym management system

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database creation
-- CREATE DATABASE IF NOT EXISTS `fitzone_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `fitzone_db`;

-- ========================================
-- USERS AND AUTHENTICATION TABLES
-- ========================================

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer-not-to-say') DEFAULT NULL,
  `role` enum('member','trainer','admin','staff') DEFAULT 'member',
  `membership_plan` enum('basic','premium','elite') DEFAULT 'basic',
  `status` enum('active','inactive','suspended','pending') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `newsletter_subscribed` tinyint(1) DEFAULT 0,
  `terms_accepted` tinyint(1) DEFAULT 0,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `fitness_goals` text,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`),
  KEY `idx_membership_plan` (`membership_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User profiles table
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `bio` text,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `medical_conditions` text,
  `fitness_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `preferred_workout_time` enum('morning','afternoon','evening','night') DEFAULT NULL,
  `privacy_level` enum('public','friends','private') DEFAULT 'private',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_profiles_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remember tokens table
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `remember_tokens_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SECURITY AND LOGGING TABLES
-- ========================================

-- Failed login attempts table
CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `attempted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blocked IPs table
CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `blocked_until` timestamp NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting table
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `key_name` (`key_name`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activity_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('login','logout') NOT NULL,
  `session_duration` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `user_sessions_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- MEMBERSHIP AND BILLING TABLES
-- ========================================

-- Memberships table
CREATE TABLE `memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('basic','premium','elite') NOT NULL,
  `status` enum('active','cancelled','suspended','expired','pending') DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `monthly_fee` decimal(8,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `auto_renew` tinyint(1) DEFAULT 1,
  `cancellation_date` date DEFAULT NULL,
  `cancellation_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `next_billing_date` (`next_billing_date`),
  CONSTRAINT `memberships_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Billing transactions table
CREATE TABLE `billing_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `transaction_type` enum('charge','refund','fee','discount') NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` enum('credit_card','debit_card','bank_transfer','cash','check','other') DEFAULT 'credit_card',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `membership_id` (`membership_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `billing_transactions_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_transactions_membership_id_fk` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CLASSES AND TRAINING TABLES
-- ========================================

-- Classes table
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` enum('cardio','strength','flexibility','dance','martial_arts','water','mind_body','other') DEFAULT 'other',
  `difficulty_level` enum('beginner','intermediate','advanced','all_levels') DEFAULT 'all_levels',
  `duration_minutes` int(11) NOT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 20,
  `equipment_needed` text,
  `prerequisites` text,
  `calories_burned_estimate` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','cancelled') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `difficulty_level` (`difficulty_level`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class schedules table
CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `room` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `current_capacity` int(11) DEFAULT 0,
  `wait_list_count` int(11) DEFAULT 0,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `date` (`date`),
  KEY `status` (`status`),
  CONSTRAINT `class_schedules_class_id_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_schedules_trainer_id_fk` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class bookings table
CREATE TABLE `class_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `booking_status` enum('confirmed','cancelled','no_show','wait_listed') DEFAULT 'confirmed',
  `booking_type` enum('regular','drop_in','guest_pass') DEFAULT 'regular',
  `payment_required` tinyint(1) DEFAULT 0,
  `payment_amount` decimal(8,2) DEFAULT 0.00,
  `booking_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `cancellation_date` timestamp NULL DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_schedule` (`user_id`, `schedule_id`),
  KEY `schedule_id` (`schedule_id`),
  KEY `booking_status` (`booking_status`),
  KEY `booking_date` (`booking_date`),
  CONSTRAINT `class_bookings_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_bookings_schedule_id_fk` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TRAINERS AND APPOINTMENTS TABLES
-- ========================================

-- Trainer profiles table
CREATE TABLE `trainer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specializations` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `bio` text,
  `availability` json DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `is_accepting_clients` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `is_accepting_clients` (`is_accepting_clients`),
  KEY `rating` (`rating`),
  CONSTRAINT `trainer_profiles_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personal training appointments table
CREATE TABLE `pt_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `session_type` enum('assessment','training','consultation','follow_up') DEFAULT 'training',
  `status` enum('scheduled','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
  `location` varchar(100) DEFAULT NULL,
  `session_goals` text,
  `trainer_notes` text,
  `client_feedback` text,
  `payment_amount` decimal(8,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `status` (`status`),
  CONSTRAINT `pt_appointments_client_id_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pt_appointments_trainer_id_fk` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- WORKOUTS AND PROGRESS TRACKING TABLES
-- ========================================

-- Workouts table
CREATE TABLE `workouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('cardio','strength','flexibility','sports','mixed','other') DEFAULT 'other',
  `duration_minutes` int(11) DEFAULT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `workout_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `difficulty_rating` int(11) DEFAULT NULL COMMENT '1-10 scale',
  `energy_level_before` int(11) DEFAULT NULL COMMENT '1-10 scale',
  `energy_level_after` int(11) DEFAULT NULL COMMENT '1-10 scale',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `workout_date` (`workout_date`),
  KEY `type` (`type`),
  CONSTRAINT `workouts_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout exercises table
CREATE TABLE `workout_exercises` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workout_id` int(11) NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `sets` int(11) DEFAULT NULL,
  `reps` varchar(50) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `distance` decimal(8,2) DEFAULT NULL,
  `rest_seconds` int(11) DEFAULT NULL,
  `notes` text,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `workout_id` (`workout_id`),
  KEY `order_index` (`order_index`),
  CONSTRAINT `workout_exercises_workout_id_fk` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Body measurements table
CREATE TABLE `body_measurements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `measurement_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `body_fat_percentage` decimal(4,2) DEFAULT NULL,
  `muscle_mass` decimal(5,2) DEFAULT NULL,
  `chest` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hips` decimal(5,2) DEFAULT NULL,
  `arm` decimal(5,2) DEFAULT NULL,
  `thigh` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `measurement_date` (`measurement_date`),
  CONSTRAINT `body_measurements_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- COMMUNICATION AND CONTENT TABLES
-- ========================================

-- Newsletter subscribers table
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL UNIQUE,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
  `subscribed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact messages table
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','in_progress','resolved','spam') DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `admin_notes` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `contact_messages_assigned_to_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog posts table
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL UNIQUE,
  `content` longtext NOT NULL,
  `excerpt` text,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `view_count` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `status` (`status`),
  KEY `published_at` (`published_at`),
  CONSTRAINT `blog_posts_author_id_fk` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SYSTEM AND CONFIGURATION TABLES
-- ========================================

-- System settings table
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json','text') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `setting_key` (`setting_key`),
  KEY `is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', 'FitZone Fitness Center', 'string', 'Site name', 1),
('site_description', 'Your ultimate fitness destination', 'string', 'Site description', 1),
('contact_email', 'info@fitzonecenter.com', 'string', 'Contact email', 1),
('contact_phone', '(555) 123-FITZ', 'string', 'Contact phone', 1),
('address', '123 Fitness Street, Downtown District, Cityville, ST 12345', 'string', 'Gym address', 1),
('operating_hours', '{"mon-fri": "5:00 AM - 11:00 PM", "sat": "6:00 AM - 10:00 PM", "sun": "7:00 AM - 9:00 PM"}', 'json', 'Operating hours', 1),
('membership_prices', '{"basic": 29.00, "premium": 59.00, "elite": 99.00}', 'json', 'Membership pricing', 1),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', 0),
('session_timeout', '3600', 'number', 'Session timeout in seconds', 0),
('email_verification_required', '0', 'boolean', 'Require email verification for new accounts', 0);

-- Insert sample admin user (password: admin123)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`, `email_verified`, `terms_accepted`, `terms_accepted_at`) VALUES
('Admin', 'User', 'admin@fitzonecenter.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1, 1, NOW());

-- Insert sample classes
INSERT INTO `classes` (`name`, `description`, `type`, `difficulty_level`, `duration_minutes`, `max_capacity`, `calories_burned_estimate`) VALUES
('Zumba Dance Fitness', 'High-energy dance fitness combining Latin rhythms with easy-to-follow moves', 'dance', 'intermediate', 45, 25, 400),
('Hatha Yoga', 'Gentle yoga practice focusing on basic postures and breathing techniques', 'mind_body', 'beginner', 60, 20, 200),
('CrossFit WOD', 'High-intensity functional fitness workouts for serious athletes', 'strength', 'advanced', 50, 15, 500),
('Indoor Cycling', 'Energetic indoor cycling class with varied terrain simulation', 'cardio', 'intermediate', 45, 20, 450),
('Pilates Core', 'Core-focused exercises improving strength, flexibility, and posture', 'flexibility', 'beginner', 55, 18, 250),
('Bootcamp', 'Military-style workout combining cardio and strength training', 'mixed', 'intermediate', 50, 20, 475);

-- Create indexes for better performance
CREATE INDEX idx_users_email_status ON users(email, status);
CREATE INDEX idx_class_schedules_date_status ON class_schedules(date, status);
CREATE INDEX idx_workouts_user_date ON workouts(user_id, workout_date);
CREATE INDEX idx_activity_logs_user_created ON activity_logs(user_id, created_at);
CREATE INDEX idx_memberships_user_status ON memberships(user_id, status);

COMMIT;

-- ========================================
-- VIEWS FOR COMMON QUERIES
-- ========================================

-- Active members view
CREATE VIEW `v_active_members` AS
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.membership_plan,
    m.start_date,
    m.next_billing_date,
    u.last_login,
    u.created_at
FROM users u
LEFT JOIN memberships m ON u.id = m.user_id AND m.status = 'active'
WHERE u.status = 'active' AND u.role = 'member';

-- Trainer profiles view
CREATE VIEW `v_trainer_profiles` AS
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    tp.specializations,
    tp.certifications,
    tp.experience_years,
    tp.hourly_rate,
    tp.bio,
    tp.rating,
    tp.total_reviews,
    tp.is_accepting_clients
FROM users u
INNER JOIN trainer_profiles tp ON u.id = tp.user_id
WHERE u.status = 'active' AND u.role = 'trainer';

-- Class schedules with details view
CREATE VIEW `v_class_schedules` AS
SELECT 
    cs.id,
    c.name as class_name,
    c.type,
    c.difficulty_level,
    c.duration_minutes,
    c.max_capacity,
    cs.room,
    cs.date,
    cs.start_time,
    cs.end_time,
    cs.current_capacity,
    cs.status,
    CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
    t.id as trainer_id
FROM class_schedules cs
INNER JOIN classes c ON cs.class_id = c.id
INNER JOIN users t ON cs.trainer_id = t.id
WHERE c.status = 'active';

-- ========================================
-- STORED PROCEDURES
-- ========================================

DELIMITER //

-- Procedure to get user dashboard stats
CREATE PROCEDURE GetUserDashboardStats(IN userId INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM workouts WHERE user_id = userId AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as workouts_this_month,
        (SELECT COUNT(*) FROM class_bookings cb 
         JOIN class_schedules cs ON cb.schedule_id = cs.id 
         WHERE cb.user_id = userId AND cs.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND cb.booking_status = 'confirmed') as classes_this_month,
        (SELECT SUM(duration_minutes) FROM workouts WHERE user_id = userId AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_workout_time,
        (SELECT SUM(calories_burned) FROM workouts WHERE user_id = userId AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as calories_burned_this_month;
END //

-- Procedure to book a class
CREATE PROCEDURE BookClass(IN userId INT, IN scheduleId INT, OUT result VARCHAR(255))
BEGIN
    DECLARE current_cap INT DEFAULT 0;
    DECLARE max_cap INT DEFAULT 0;
    DECLARE existing_booking INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET result = 'Error occurred while booking class';
    END;
    
    START TRANSACTION;
    
    -- Check if user already booked this class
    SELECT COUNT(*) INTO existing_booking 
    FROM class_bookings 
    WHERE user_id = userId AND schedule_id = scheduleId AND booking_status IN ('confirmed', 'wait_listed');
    
    IF existing_booking > 0 THEN
        SET result = 'Already booked for this class';
        ROLLBACK;
    ELSE
        -- Get current capacity and max capacity
        SELECT cs.current_capacity, c.max_capacity INTO current_cap, max_cap
        FROM class_schedules cs
        JOIN classes c ON cs.class_id = c.id
        WHERE cs.id = scheduleId;
        
        IF current_cap < max_cap THEN
            -- Book the class
            INSERT INTO class_bookings (user_id, schedule_id, booking_status, booking_date)
            VALUES (userId, scheduleId, 'confirmed', NOW());
            
            -- Update current capacity
            UPDATE class_schedules 
            SET current_capacity = current_capacity + 1 
            WHERE id = scheduleId;
            
            SET result = 'Class booked successfully';
        ELSE
            -- Add to wait list
            INSERT INTO class_bookings (user_id, schedule_id, booking_status, booking_date)
            VALUES (userId, scheduleId, 'wait_listed', NOW());
            
            -- Update wait list count
            UPDATE class_schedules 
            SET wait_list_count = wait_list_count + 1 
            WHERE id = scheduleId;
            
            SET result = 'Added to wait list';
        END IF;
    END IF;
    
    COMMIT;
END //

DELIMITER ;

-- ========================================
-- TRIGGERS
-- ========================================

DELIMITER //

-- Trigger to update user profile when user is updated
CREATE TRIGGER after_user_update 
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.first_name != OLD.first_name OR NEW.last_name != OLD.last_name THEN
        UPDATE user_profiles 
        SET display_name = CONCAT(NEW.first_name, ' ', NEW.last_name),
            updated_at = NOW()
        WHERE user_id = NEW.id;
    END IF;
END //

-- Trigger to log membership changes
CREATE TRIGGER after_membership_update
AFTER UPDATE ON memberships
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO activity_logs (user_id, action, description, created_at)
        VALUES (NEW.user_id, 'membership_status_change', 
                CONCAT('Membership status changed from ', OLD.status, ' to ', NEW.status), 
                NOW());
    END IF;
END //

DELIMITER ;

-- Grant permissions (uncomment if needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON fitzone_db.* TO 'fitzone_user'@'localhost';
-- FLUSH PRIVILEGES;

-- End of schema