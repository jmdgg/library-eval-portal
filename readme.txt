--new schema. please drop old one and run this new one tysm
--then after, run initial_setup.php for survey instances proper.
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2026 at 01:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_eval_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_department`
--

CREATE TABLE `academic_department` (
  `acad_dept_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_details` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college`
--

CREATE TABLE `college` (
  `college_id` int(11) NOT NULL,
  `college_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_period`
--

CREATE TABLE `evaluation_period` (
  `period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_report`
--

CREATE TABLE `generated_report` (
  `report_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `download_url` varchar(255) NOT NULL,
  `generation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `dashboard_data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_department`
--

CREATE TABLE `library_department` (
  `lib_dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_service`
--

CREATE TABLE `library_service` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patron_type`
--

CREATE TABLE `patron_type` (
  `patron_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_metric`
--

CREATE TABLE `question_metric` (
  `question_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `max_score` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `response_detail`
--

CREATE TABLE `response_detail` (
  `detail_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submission_service`
--

CREATE TABLE `submission_service` (
  `submission_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `other_service_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_submission`
--

CREATE TABLE `survey_submission` (
  `submission_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `lib_dept_id` int(11) NOT NULL,
  `patron_type_id` int(11) NOT NULL,
  `other_patron_details` varchar(255) DEFAULT NULL,
  `college_id` int(11) DEFAULT NULL,
  `acad_dept_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `is_satisfied` tinyint(1) NOT NULL,
  `overall_rating` decimal(3,2) NOT NULL,
  `recommendations` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_department`
--
ALTER TABLE `academic_department`
  ADD PRIMARY KEY (`acad_dept_id`),
  ADD KEY `idx_college` (`college_id`);

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `idx_username` (`username`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `college`
--
ALTER TABLE `college`
  ADD PRIMARY KEY (`college_id`),
  ADD UNIQUE KEY `idx_college_name` (`college_name`);

--
-- Indexes for table `evaluation_period`
--
ALTER TABLE `evaluation_period`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `generated_report`
--
ALTER TABLE `generated_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_period_id` (`period_id`);

--
-- Indexes for table `library_department`
--
ALTER TABLE `library_department`
  ADD PRIMARY KEY (`lib_dept_id`),
  ADD UNIQUE KEY `idx_dept_name` (`dept_name`);

--
-- Indexes for table `library_service`
--
ALTER TABLE `library_service`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `idx_service_name` (`service_name`);

--
-- Indexes for table `patron_type`
--
ALTER TABLE `patron_type`
  ADD PRIMARY KEY (`patron_type_id`),
  ADD UNIQUE KEY `idx_type_name` (`type_name`);

--
-- Indexes for table `question_metric`
--
ALTER TABLE `question_metric`
  ADD PRIMARY KEY (`question_id`);

--
-- Indexes for table `response_detail`
--
ALTER TABLE `response_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `submission_service`
--
ALTER TABLE `submission_service`
  ADD PRIMARY KEY (`submission_id`,`service_id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Indexes for table `survey_submission`
--
ALTER TABLE `survey_submission`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_period` (`period_id`),
  ADD KEY `idx_dept` (`lib_dept_id`),
  ADD KEY `idx_patron` (`patron_type_id`),
  ADD KEY `idx_college` (`college_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_acad_dept` (`acad_dept_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_department`
--
ALTER TABLE `academic_department`
  MODIFY `acad_dept_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college`
--
ALTER TABLE `college`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_period`
--
ALTER TABLE `evaluation_period`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_report`
--
ALTER TABLE `generated_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_department`
--
ALTER TABLE `library_department`
  MODIFY `lib_dept_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_service`
--
ALTER TABLE `library_service`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patron_type`
--
ALTER TABLE `patron_type`
  MODIFY `patron_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_metric`
--
ALTER TABLE `question_metric`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response_detail`
--
ALTER TABLE `response_detail`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_submission`
--
ALTER TABLE `survey_submission`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_department`
--
ALTER TABLE `academic_department`
  ADD CONSTRAINT `fk_acad_college` FOREIGN KEY (`college_id`) REFERENCES `college` (`college_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_user` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `generated_report`
--
ALTER TABLE `generated_report`
  ADD CONSTRAINT `fk_report_period` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`) ON DELETE CASCADE;

--
-- Constraints for table `response_detail`
--
ALTER TABLE `response_detail`
  ADD CONSTRAINT `fk_detail_question` FOREIGN KEY (`question_id`) REFERENCES `question_metric` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_sub` FOREIGN KEY (`submission_id`) REFERENCES `survey_submission` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_service`
--
ALTER TABLE `submission_service`
  ADD CONSTRAINT `fk_junction_service` FOREIGN KEY (`service_id`) REFERENCES `library_service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_junction_sub` FOREIGN KEY (`submission_id`) REFERENCES `survey_submission` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_submission`
--
ALTER TABLE `survey_submission`
  ADD CONSTRAINT `fk_sub_acad_dept` FOREIGN KEY (`acad_dept_id`) REFERENCES `academic_department` (`acad_dept_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sub_college` FOREIGN KEY (`college_id`) REFERENCES `college` (`college_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sub_dept` FOREIGN KEY (`lib_dept_id`) REFERENCES `library_department` (`lib_dept_id`),
  ADD CONSTRAINT `fk_sub_patron` FOREIGN KEY (`patron_type_id`) REFERENCES `patron_type` (`patron_type_id`),
  ADD CONSTRAINT `fk_sub_period` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
