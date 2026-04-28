-- SETTING UP Database for this current branch.
-- 1. Create database called 'library_eval_db'
-- 2. Paste the following code in under SQL tabs for library_eval_db. 


-- Disable foreign key checks temporarily to allow clean dropping
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Wipe Old Tables
DROP TABLE IF EXISTS `audit_log`, `admin_user`, `generated_report`, `response_detail`, `survey_submission`, `question_metric`, `evaluation_period`, `department`;

-- 2. Build Master Tables
CREATE TABLE `department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department_name` varchar(100) NOT NULL,
  `weight_multiplier` decimal(5,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `evaluation_period` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `eval_month` varchar(20) NOT NULL,
  `eval_year` int(11) NOT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `question_metric` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category` varchar(50) NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `max_score` int(11) NOT NULL DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Build Core Transaction Tables (3NF)
CREATE TABLE `survey_submission` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `period_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `submission_date` date NOT NULL,
  `role` varchar(100) NOT NULL,
  `college` varchar(255) DEFAULT NULL,
  `academic_department` varchar(255) DEFAULT NULL,
  `services_availed` text NOT NULL,
  `is_satisfied` varchar(10) NOT NULL,
  `overall_rating` varchar(50) NOT NULL,
  `recommendations` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`),
  FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `response_detail` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  FOREIGN KEY (`submission_id`) REFERENCES `survey_submission` (`submission_id`),
  FOREIGN KEY (`question_id`) REFERENCES `question_metric` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Build Reporting & Security Tables
CREATE TABLE `generated_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `period_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `download_url` varchar(255) NOT NULL,
  `generation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `dashboard_data` longtext NOT NULL,
  FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `admin_user` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_details` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (`admin_id`) REFERENCES `admin_user` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Seed the Data
INSERT INTO `department` (`department_id`, `department_name`) VALUES
(1, 'Circulation Section'), (2, 'General Reference Section'), (3, 'Computer and Multimedia Services (CMS)'),
(4, 'Health Sciences Library'), (5, 'Filipiniana Section'), (6, 'College of Business and Accountancy Library'), (7, 'PS Library');

INSERT INTO `evaluation_period` (`period_id`, `eval_month`, `eval_year`) VALUES (1, 'APRIL', 2026);

INSERT INTO `question_metric` (`question_id`, `category`, `question_text`) VALUES
(1, 'Feedback', 'The library has sufficient resources for my research and information needs'),
(2, 'Feedback', 'Library staff provided assistance in a timely and helpful manner'),
(3, 'Feedback', 'The process of borrowing, returning and renewal of library resources is convenient'),
(4, 'Feedback', 'The information/procedure provided by the library staff were easy to understand');

SET FOREIGN_KEY_CHECKS = 1;