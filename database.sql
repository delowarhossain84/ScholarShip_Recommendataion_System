-- ScholarMatch database
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `scholarship_recommendation_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `scholarship_recommendation_system`;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `application_documents`,`applications`,`student_documents`,`notifications`,`scholarships`,`users`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(120) NOT NULL, `email` varchar(150) NOT NULL,
  `ielts_score` decimal(3,1) DEFAULT NULL, `password` varchar(255) NOT NULL,
  `role` enum('student','provider','admin') NOT NULL DEFAULT 'student',
  `education_level` varchar(80) DEFAULT NULL, `field_of_study` varchar(120) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL, `income` decimal(12,2) DEFAULT NULL, `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `gre_score` int(11) DEFAULT NULL,
  `toefl_score` int(11) DEFAULT NULL, `sat_score` int(11) DEFAULT NULL, `extracurricular` text DEFAULT NULL,
  `research_experience` text DEFAULT NULL, `work_experience` text DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `ielts_score`, `password`, `role`, `education_level`, `field_of_study`, `cgpa`, `income`, `country`, `created_at`, `gre_score`, `toefl_score`, `sat_score`, `extracurricular`, `research_experience`, `work_experience`) VALUES
(1, 'System Admin', 'admin@scholarship.com', NULL, '$2y$12$uZgpAHml4xEDDCGErl61xOLhcnFRvhIJ5yXgeKMtOkkZC76xG3zq2', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-09-07 10:24:50', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Demo Student', 'student@scholarship.com', NULL, '$2y$12$rN95vCly6QRO7DLTIrYFhOleU6xeohWdqKGzKRTY2wOENwT9TujSO', 'student', 'Undergraduate', 'Computer Science', 3.65, 250000.00, 'Bangladesh', '2026-09-07 10:24:50', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Demo Provider', 'provider@scholarship.com', NULL, '$2y$12$JJ70/P.oZnyOJBUhwuCRw.Oux/nCVLsqGcH60IH5suiWkRkd9XtHy', 'provider', NULL, NULL, NULL, NULL, 'Bangladesh', '2026-09-07 10:24:50', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'imran', 'imran@gmail.com', 7.0, '$2y$10$qV/IP8/LvezfEnbykDVPI.VzA9pUNAzXe1Yb.MZSuuEpwFDP/agRy', 'student', 'Graduate', 'Architecture', 3.50, 500000.00, 'Canada', '2026-09-07 10:39:52', 300, NULL, NULL, 'zxcvsfvsfdvasfdas', NULL, NULL),
(5, 'asdfasd', 'asdf@gmail.com', NULL, '$2y$10$wqzSnEYsnh3y9fmobjs0.ujnq/6kQiqlbNLAugITo2Qr3I70yuoMO', 'provider', '', '', NULL, NULL, '', '2026-09-07 10:45:42', NULL, NULL, NULL, '', NULL, NULL),
(6, 'jon', 'jon@gmail.com', 7.0, '$2y$10$bPdI6Su8ufutFyUQDY4oXuNsuHdqZ7nzCOgIUNS2vihavUVl1ZmR6', 'student', 'Graduate', 'CSE', 3.80, 600000.00, 'Bangladesh', '2026-09-07 10:49:57', 300, NULL, NULL, 'dfvbfa dfa', NULL, NULL);

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `provider_id` int(11) NOT NULL, `title` varchar(200) NOT NULL,
  `description` text NOT NULL, `provider_name` varchar(150) NOT NULL, `amount` decimal(12,2) DEFAULT 0.00,
  `deadline` date NOT NULL, `min_cgpa` decimal(4,2) DEFAULT 0.00, `education_level` varchar(80) DEFAULT 'Any',
  `field_of_study` varchar(120) DEFAULT 'Any', `country` varchar(100) DEFAULT 'Any',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `provider_id` (`provider_id`),
  CONSTRAINT `fk_scholarship_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `scholarships` (`id`, `provider_id`, `title`, `description`, `provider_name`, `amount`, `deadline`, `min_cgpa`, `education_level`, `field_of_study`, `country`, `created_at`) VALUES
(1, 3, 'Future Technology Scholarship', 'Financial support for students studying technology and computing subjects.', 'Demo Provider', 50000.00, '2030-06-30', 3.20, 'Undergraduate', 'Computer Science', 'Bangladesh', '2026-09-07 10:24:50'),
(2, 3, 'Academic Excellence Scholarship', 'Merit based scholarship for high-performing university students.', 'Demo Provider', 75000.00, '2030-08-15', 3.50, 'Undergraduate', 'Any', 'Bangladesh', '2026-09-07 10:24:50'),
(3, 3, 'Global Graduate Scholarship', 'Support for graduate students in any academic discipline.', 'Demo Provider', 100000.00, '2030-10-15', 3.00, 'Graduate', 'Any', 'Any', '2026-09-07 10:24:50'),
(4, 3, 'Engineering Innovation Scholarship', 'Scholarship for students interested in engineering and innovation.', 'Demo Provider', 60000.00, '2030-09-30', 3.00, 'Undergraduate', 'Engineering', 'Bangladesh', '2026-09-07 10:24:50'),
(5, 5, 'MEXT schoarship', 'asdf asdf asdfasd asdvasd asdvasdv SD', 'asdfasd', 50000.00, '2026-09-23', 3.50, 'Graduate', 'CSE', 'Bangladesh', '2026-09-07 10:47:14');

CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `scholarship_id` int(11) NOT NULL, `student_id` int(11) NOT NULL,
  `ielts_score` decimal(3,1) DEFAULT NULL, `gre_score` decimal(5,2) DEFAULT NULL, `extracurricular` text DEFAULT NULL,
  `personal_statement` text DEFAULT NULL, `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`), UNIQUE KEY `unique_application` (`scholarship_id`,`student_id`), KEY `student_id` (`student_id`),
  CONSTRAINT `fk_application_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `applications` (`id`, `scholarship_id`, `student_id`, `ielts_score`, `gre_score`, `extracurricular`, `personal_statement`, `status`, `applied_at`, `updated_at`) VALUES
(1, 5, 6, 7.0, 300.00, 'dfvbfa dfa', 'sdfa sdf', 'Pending', '2026-09-07 10:50:50', '2026-09-07 10:50:50');

CREATE TABLE `application_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `application_id` int(11) NOT NULL,
  `document_type` enum('Academic Certificate','IELTS Certificate','GRE Certificate','CV / Resume') NOT NULL,
  `file_name` varchar(255) NOT NULL, `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `application_id` (`application_id`),
  CONSTRAINT `fk_document_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `application_documents` (`id`, `application_id`, `document_type`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 1, 'Academic Certificate', 'Report 1.pdf', 'uploads/application_documents/application_1_c1e4c9f06ed37209905d0088c5ec48aa.pdf', '2026-09-07 10:50:50'),
(2, 1, 'GRE Certificate', 'Project Proposal Imran.pdf', 'uploads/application_documents/application_1_bc85c035fd8e31d29497f92f8286b447.pdf', '2026-09-07 10:50:50'),
(3, 1, 'CV / Resume', 'Project Propossal Ronju.pdf', 'uploads/application_documents/application_1_1c930a48559c404acdd8854e9c1e8a47.pdf', '2026-09-07 10:50:50');

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `student_id` int(11) NOT NULL, `user_id` int(11) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL, `file_name` varchar(255) DEFAULT NULL, `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(), `file_size` int(11) DEFAULT NULL, `status` varchar(50) DEFAULT 'pending',
  PRIMARY KEY (`id`), KEY `user_id` (`user_id`),
  CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `application_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL, `message` text NOT NULL, `type` varchar(50) DEFAULT 'application',
  `is_read` tinyint(1) DEFAULT 0, `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`), KEY `user_id` (`user_id`), KEY `application_id` (`application_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
