-- ===============================
-- Database Schema: LSDB Chatbot
-- ===============================

-- Create database
CREATE DATABASE IF NOT EXISTS lsdb_chatbot 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE lsdb_chatbot;

-- ===============================
-- Table: users
-- Stores user information from chatbot interactions
-- ===============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_interactions INT DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    INDEX idx_last_active (last_active)
);

-- ===============================
-- Table: chatbot_options
-- Stores predefined chatbot options and responses
-- ===============================
CREATE TABLE chatbot_options (
    id VARCHAR(50) PRIMARY KEY,
    category ENUM('main', 'courses', 'internships', 'contact') NOT NULL,
    option_text VARCHAR(150) NOT NULL,
    response_text TEXT,
    link_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order)
);

-- ===============================
-- Table: chat_interactions
-- Logs all user interactions with the chatbot
-- ===============================
CREATE TABLE chat_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(100) NOT NULL,
    interaction_type ENUM('start_chat', 'option_select', 'course_inquiry', 'internship_inquiry', 'contact_request', 'restart') NOT NULL,
    option_selected VARCHAR(50),
    user_message TEXT,
    bot_response TEXT,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_session_id (session_id),
    FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE CASCADE
);

-- ===============================
-- Table: app_settings
-- Stores application configuration settings
-- ===============================
CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_active (is_active)
);

-- ===============================
-- Table: analytics
-- Stores chatbot usage analytics
-- ===============================
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_recorded DATE NOT NULL,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_interactions INT DEFAULT 0,
    course_inquiries INT DEFAULT 0,
    internship_inquiries INT DEFAULT 0,
    contact_requests INT DEFAULT 0,
    average_session_duration DECIMAL(10,2),
    most_popular_option VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date_recorded),
    INDEX idx_date_recorded (date_recorded)
);

-- ===============================
-- Insert Initial Data
-- ===============================

-- Main navigation options
INSERT INTO chatbot_options (id, category, option_text, response_text, sort_order) VALUES
('courses', 'main', 'Courses', 'Great! Here are our available courses:', 1),
('internships', 'main', 'Internships', 'Excellent! Here are our internship opportunities:', 2),
('contact', 'main', 'Contact Information', 'Here is our contact information:', 3);

-- Course options
INSERT INTO chatbot_options (id, category, option_text, response_text, link_url, sort_order) VALUES
('digital-marketing', 'courses', 'Digital Marketing', 'Learn comprehensive digital marketing strategies and tools.', 'https://lsdb.edu/courses/digital-marketing', 1),
('business-admin', 'courses', 'Business Administration', 'Master essential business management and leadership skills.', 'https://lsdb.edu/courses/business-administration', 2),
('finance', 'courses', 'Finance', 'Understand financial principles and investment strategies.', 'https://lsdb.edu/courses/finance', 3),
('operations', 'courses', 'Operations', 'Optimize business operations and supply chain management.', 'https://lsdb.edu/courses/operations', 4),
('arts', 'courses', 'Arts', 'Explore creative arts and digital design fundamentals.', 'https://lsdb.edu/courses/arts', 5),
('commerce', 'courses', 'Commerce', 'Learn e-commerce and digital business strategies.', 'https://lsdb.edu/courses/commerce', 6),
('hr', 'courses', 'HR', 'Master human resources and organizational development.', 'https://lsdb.edu/courses/hr', 7);

-- Internship options
INSERT INTO chatbot_options (id, category, option_text, response_text, link_url, sort_order) VALUES
('software-dev', 'internships', 'Software Development', 'Gain hands-on experience in web and mobile development.', 'https://lsdb.edu/internships/software-development', 1),
('marketing-intern', 'internships', 'Marketing', 'Apply marketing strategies in real business scenarios.', 'https://lsdb.edu/internships/marketing', 2),
('finance-intern', 'internships', 'Finance', 'Work with financial analysis and investment planning.', 'https://lsdb.edu/internships/finance', 3);

-- Application settings
INSERT INTO app_settings (setting_key, setting_value, description) VALUES
('contact_phone', '+44 20 7123 4567', 'Main contact phone number'),
('contact_email', 'info@lsdb.edu', 'Main contact email address'),
('chatbot_welcome_message', 'Hey there! Please select an option to get started.', 'Default chatbot welcome message'),
('max_session_duration', '3600', 'Maximum session duration in seconds'),
('enable_analytics', '1', 'Enable chatbot analytics tracking'),
('maintenance_mode', '0', 'Enable maintenance mode');

-- ===============================
-- Stored Procedures
-- ===============================

-- Update user interaction count
DELIMITER //
CREATE PROCEDURE UpdateUserInteractionCount(IN user_email VARCHAR(100))
BEGIN
    UPDATE users 
    SET total_interactions = total_interactions + 1, 
        last_active = NOW() 
    WHERE email = user_email;
END //
DELIMITER ;

-- Get daily analytics
DELIMITER //
CREATE PROCEDURE GetDailyAnalytics(IN analysis_date DATE)
BEGIN
    SELECT 
        da.date_recorded,
        da.total_users,
        da.new_users,
        da.total_interactions,
        da.course_inquiries,
        da.internship_inquiries,
        da.contact_requests,
        da.average_session_duration,
        da.most_popular_option
    FROM analytics da
    WHERE da.date_recorded = analysis_date;
END //
DELIMITER ;

-- Generate analytics for a specific date
DELIMITER //
CREATE PROCEDURE GenerateAnalytics(IN analysis_date DATE)
BEGIN
    DECLARE total_users_count INT DEFAULT 0;
    DECLARE new_users_count INT DEFAULT 0;
    DECLARE total_interactions_count INT DEFAULT 0;
    DECLARE course_inquiries_count INT DEFAULT 0;
    DECLARE internship_inquiries_count INT DEFAULT 0;
    DECLARE contact_requests_count INT DEFAULT 0;
    DECLARE most_popular VARCHAR(100) DEFAULT '';
    
    -- Count total users who interacted on this date
    SELECT COUNT(DISTINCT user_email) INTO total_users_count
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date;
    
    -- Count new users (first interaction ever on this date)
    SELECT COUNT(DISTINCT ci.user_email) INTO new_users_count
    FROM chat_interactions ci
    INNER JOIN users u ON ci.user_email = u.email
    WHERE DATE(ci.created_at) = analysis_date
    AND DATE(u.created_at) = analysis_date;
    
    -- Count total interactions
    SELECT COUNT(*) INTO total_interactions_count
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date;
    
    -- Count course inquiries
    SELECT COUNT(*) INTO course_inquiries_count
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date
    AND interaction_type = 'course_inquiry';
    
    -- Count internship inquiries
    SELECT COUNT(*) INTO internship_inquiries_count
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date
    AND interaction_type = 'internship_inquiry';
    
    -- Count contact requests
    SELECT COUNT(*) INTO contact_requests_count
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date
    AND interaction_type = 'contact_request';
    
    -- Find most popular option
    SELECT option_selected INTO most_popular
    FROM chat_interactions 
    WHERE DATE(created_at) = analysis_date
    AND option_selected IS NOT NULL
    GROUP BY option_selected
    ORDER BY COUNT(*) DESC
    LIMIT 1;
    
    -- Insert or update analytics
    INSERT INTO analytics (
        date_recorded, total_users, new_users, total_interactions,
        course_inquiries, internship_inquiries, contact_requests, most_popular_option
    ) VALUES (
        analysis_date, total_users_count, new_users_count, total_interactions_count,
        course_inquiries_count, internship_inquiries_count, contact_requests_count, most_popular
    ) ON DUPLICATE KEY UPDATE
        total_users = total_users_count,
        new_users = new_users_count,
        total_interactions = total_interactions_count,
        course_inquiries = course_inquiries_count,
        internship_inquiries = internship_inquiries_count,
        contact_requests = contact_requests_count,
        most_popular_option = most_popular;
END //
DELIMITER ;

-- ===============================
-- Views
-- ===============================

-- User activity summary view
CREATE VIEW user_activity_summary AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.created_at,
    u.last_active,
    u.total_interactions,
    COUNT(ci.id) AS actual_interactions,
    MAX(ci.created_at) AS last_interaction
FROM users u
LEFT JOIN chat_interactions ci ON u.email = ci.user_email
GROUP BY u.id, u.name, u.email, u.created_at, u.last_active, u.total_interactions;

-- Popular options view
CREATE VIEW popular_options AS
SELECT 
    co.category,
    co.option_text,
    co.id as option_id,
    COUNT(ci.option_selected) as selection_count,
    ROUND((COUNT(ci.option_selected) * 100.0 / (SELECT COUNT(*) FROM chat_interactions WHERE option_selected IS NOT NULL)), 2) as percentage
FROM chatbot_options co
LEFT JOIN chat_interactions ci ON co.id = ci.option_selected
WHERE co.is_active = 1
GROUP BY co.category, co.option_text, co.id
ORDER BY selection_count DESC;

-- Daily interaction summary view
CREATE VIEW daily_interaction_summary AS
SELECT 
    DATE(created_at) as interaction_date,
    COUNT(*) as total_interactions,
    COUNT(DISTINCT user_email) as unique_users,
    COUNT(CASE WHEN interaction_type = 'course_inquiry' THEN 1 END) as course_inquiries,
    COUNT(CASE WHEN interaction_type = 'internship_inquiry' THEN 1 END) as internship_inquiries,
    COUNT(CASE WHEN interaction_type = 'contact_request' THEN 1 END) as contact_requests
FROM chat_interactions
GROUP BY DATE(created_at)
ORDER BY interaction_date DESC;

-- ===============================
-- Indexes for Performance
-- ===============================

-- Composite indexes for common queries
CREATE INDEX idx_user_interactions ON chat_interactions(user_email, created_at);
CREATE INDEX idx_interaction_type_date ON chat_interactions(interaction_type, created_at);
CREATE INDEX idx_option_selected_date ON chat_interactions(option_selected, created_at);

-- ===============================
-- Triggers
-- ===============================

-- Update user interaction count when new interaction is added
DELIMITER //
CREATE TRIGGER update_user_stats_after_interaction
    AFTER INSERT ON chat_interactions
    FOR EACH ROW
BEGIN
    CALL UpdateUserInteractionCount(NEW.user_email);
END //
DELIMITER ;

-- ===============================
-- Sample Data for Testing
-- ===============================

-- Insert sample users
INSERT INTO users (name, email, created_at) VALUES
('John Doe', 'john.doe@example.com', '2024-01-15 10:30:00'),
('Jane Smith', 'jane.smith@example.com', '2024-01-16 14:20:00'),
('Mike Johnson', 'mike.johnson@example.com', '2024-01-17 09:15:00'),
('Sarah Wilson', 'sarah.wilson@example.com', '2024-01-18 16:45:00');

-- Insert sample interactions
INSERT INTO chat_interactions (user_email, interaction_type, option_selected, user_message, bot_response, created_at) VALUES
('john.doe@example.com', 'start_chat', NULL, 'Started chat', 'Welcome message', '2024-01-15 10:31:00'),
('john.doe@example.com', 'option_select', 'courses', 'Courses', 'Great! Here are our available courses:', '2024-01-15 10:32:00'),
('john.doe@example.com', 'course_inquiry', 'digital-marketing', 'Digital Marketing', 'Course details for Digital Marketing', '2024-01-15 10:33:00'),
('jane.smith@example.com', 'start_chat', NULL, 'Started chat', 'Welcome message', '2024-01-16 14:21:00'),
('jane.smith@example.com', 'option_select', 'internships', 'Internships', 'Excellent! Here are our internship opportunities:', '2024-01-16 14:22:00'),
('jane.smith@example.com', 'internship_inquiry', 'software-dev', 'Software Development', 'Internship details for Software Development', '2024-01-16 14:23:00');

-- Generate analytics for sample dates
CALL GenerateAnalytics('2024-01-15');
CALL GenerateAnalytics('2024-01-16');
CALL GenerateAnalytics('2024-01-17');
CALL GenerateAnalytics('2024-01-18');