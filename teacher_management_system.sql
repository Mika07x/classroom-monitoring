-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 10:49 AM
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
-- Database: `teacher_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `target_audience` enum('all_students','subject_students') DEFAULT 'subject_students',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('draft','published','archived') DEFAULT 'published',
  `publish_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `teacher_id`, `subject_id`, `target_audience`, `priority`, `status`, `publish_date`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to the New Semester', 'Welcome all students to the new academic semester. Please check your schedules and be prepared for classes.', 1, NULL, 'all_students', 'normal', 'published', '2026-02-04 11:57:42', '2026-02-04 11:57:42', '2026-02-04 11:57:42'),
(2, 'Data Structures Assignment Due', 'Reminder: Your Data Structures assignment is due next Friday. Please submit via the portal.', 1, 2, 'subject_students', 'high', 'published', '2026-02-04 11:57:42', '2026-02-04 11:57:42', '2026-02-04 11:57:42'),
(3, 'Lab Session Cancelled', 'Tomorrow\'s lab session is cancelled due to maintenance. We will reschedule for next week.', 1, 1, 'subject_students', 'urgent', 'published', '2026-02-04 11:57:42', '2026-02-04 11:57:42', '2026-02-04 11:57:42'),
(4, 'Makinig kayo', 'mic test', 1, 2, 'subject_students', 'low', 'published', '2026-02-04 15:52:54', '2026-02-04 15:52:54', '2026-02-04 15:52:54');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_reads`
--

INSERT INTO `announcement_reads` (`id`, `announcement_id`, `student_user_id`, `read_at`) VALUES
(1, 2, 6, '2026-02-06 18:36:41'),
(5, 1, 6, '2026-02-06 18:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_code` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `strength` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_code`, `department`, `semester`, `strength`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CS-A', 'CS-2024-A', 'Computer Science', 1, 0, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(2, 'CS-B', 'CS-2024-B', 'Computer Science', 1, 0, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(3, 'MATH-A', 'MATH-2024-A', 'Mathematics', 1, 0, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `building` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `room_type` enum('classroom','lab','seminar','auditorium') DEFAULT 'classroom',
  `equipment` varchar(255) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `status` enum('active','maintenance','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classrooms`
--

INSERT INTO `classrooms` (`id`, `room_number`, `room_name`, `building`, `capacity`, `room_type`, `equipment`, `floor`, `status`, `created_at`, `updated_at`) VALUES
(1, 'A101', 'Room A101', 'Building A', 40, 'classroom', '0', 1, 'active', '2026-01-31 11:58:17', '2026-02-03 16:41:36'),
(3, 'B101', 'Lab B101', 'Building B', 30, 'lab', '0', 0, 'active', '2026-01-31 11:58:17', '2026-02-07 03:10:40'),
(4, 'B201', 'Seminar B201', 'Building B', 50, 'seminar', NULL, 2, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(6, 'CL1', 'CL1', 'Building A', 40, 'lab', 'computers', 3, 'active', '2026-02-03 16:42:42', '2026-02-03 16:42:42'),
(8, 'CL2', 'CL2', 'Building A', 35, 'lab', '0', 3, 'active', '2026-02-06 07:14:22', '2026-02-06 07:14:43'),
(9, 'CL3', 'CL3', 'Building A', 35, 'lab', '', 3, 'active', '2026-02-06 07:15:31', '2026-02-06 07:15:31'),
(10, '104', 'Lecture Room', 'Main Building', 40, 'auditorium', '0', 0, 'active', '2026-02-07 03:05:34', '2026-02-07 03:12:12'),
(11, '401', 'Lecture Room', 'Main Building', 40, 'seminar', '', 4, 'active', '2026-02-07 03:15:24', '2026-02-07 03:15:24'),
(13, '308', 'Lecture Room', 'Building B', 40, 'seminar', '', 1, 'active', '2026-02-07 03:19:41', '2026-02-07 03:19:41'),
(14, 'C4', 'Court', 'Main Building', 50, 'classroom', '', 0, 'active', '2026-02-07 03:25:52', '2026-02-07 03:25:52');

-- --------------------------------------------------------

--
-- Table structure for table `class_assignments`
--

CREATE TABLE `class_assignments` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_reservations`
--

CREATE TABLE `room_reservations` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_reservations`
--

INSERT INTO `room_reservations` (`id`, `teacher_id`, `classroom_id`, `reservation_date`, `time_slot_id`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-02-04', 1, 'pending', 'pahiram room', '2026-02-03 16:44:41', '2026-02-03 16:44:41'),
(2, 1, 4, '2026-02-04', 9, 'approved', '', '2026-02-03 17:01:14', '2026-02-03 17:01:34'),
(3, 1, 6, '2026-02-13', 2, 'approved', 'sample 2', '2026-02-05 06:33:20', '2026-02-05 07:15:50'),
(4, 1, 6, '2026-02-02', 9, 'pending', 'try lang', '2026-02-05 10:18:23', '2026-02-05 10:18:23'),
(5, 1, 1, '2026-02-05', 9, 'approved', '', '2026-02-06 17:18:57', '2026-02-06 17:19:17'),
(6, 1, 6, '2026-02-13', 3, 'approved', 'sample 3', '2026-02-07 03:23:32', '2026-02-07 03:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `teacher_id`, `subject_id`, `classroom_id`, `day_of_week`, `time_slot_id`, `semester`, `academic_year`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 3, 'Monday', 4, '1', '2026', 'active', NULL, '2026-02-01 10:54:34', '2026-02-05 06:19:57'),
(2, 1, 2, 6, 'Wednesday', 9, '1', '2026', 'active', NULL, '2026-02-03 17:00:35', '2026-02-03 17:00:35'),
(3, 1, 1, 6, 'Wednesday', 5, '2', '2026', 'active', NULL, '2026-02-05 10:11:33', '2026-02-05 10:11:33'),
(6, 1, 7, 1, 'Wednesday', 9, '1', '2026', 'active', NULL, '2026-02-05 10:22:55', '2026-02-05 10:22:55'),
(7, 1, 1, 1, 'Tuesday', 6, '1', '2026', 'active', NULL, '2026-02-05 10:23:55', '2026-02-05 10:23:55'),
(8, 1, 2, 1, 'Monday', 1, '1', '2026', 'active', NULL, '2026-02-05 10:25:30', '2026-02-05 10:25:30'),
(9, 2, 7, 9, 'Friday', 7, '2', '2026', 'active', NULL, '2026-02-06 07:17:16', '2026-02-06 07:17:16'),
(10, 1, 2, 9, 'Friday', 7, '2', '2026', 'active', NULL, '2026-02-06 07:17:50', '2026-02-06 07:17:50');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','dropped') DEFAULT 'enrolled',
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `student_user_id`, `subject_id`, `enrollment_date`, `status`, `academic_year`, `semester`) VALUES
(1, 6, 2, '2026-02-04 11:50:41', 'enrolled', '2026', '1'),
(2, 6, 5, '2026-02-06 18:40:02', 'dropped', '2026', '1');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department`, `description`, `credits`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CS101', 'Introduction to Programming', 'Computer Science', NULL, 4, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(2, 'CS201', 'Data Structures', 'Computer Science', NULL, 4, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(5, 'ENG101', 'English Literature', 'English', NULL, 3, 'active', '2026-01-31 11:58:17', '2026-01-31 11:58:17'),
(7, 'CCS 114B', 'Practicum 2', 'Computer Science', 'OJT', 3, 'active', '2026-02-05 10:09:33', '2026-02-05 10:09:52');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `qualification`, `hire_date`, `bio`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Ryan ', 'Mateo', 'ryanmateo@gmail.com', '09691675079', 'CSD', 'qualified', '2025-10-09', 'maangas', NULL, 'active', '2026-02-01 10:31:44', '2026-02-03 16:27:49'),
(2, 3, 'Sir', 'Ted', 'sirted@gmail.com', '09691675078', 'CSD', 'qualified', '2026-02-03', 'cutie', NULL, 'active', '2026-02-03 17:11:58', '2026-02-06 07:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_availability`
--

CREATE TABLE `teacher_availability` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_availability`
--

INSERT INTO `teacher_availability` (`id`, `teacher_id`, `day_of_week`, `time_slot_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', 9, 'available', '2026-02-05 06:33:36', '2026-02-05 06:33:36'),
(2, 1, 'Wednesday', 4, 'unavailable', '2026-02-05 10:21:59', '2026-02-05 10:21:59'),
(3, 1, 'Tuesday', 6, 'unavailable', '2026-02-05 10:23:21', '2026-02-05 10:23:21'),
(4, 1, 'Monday', 1, 'unavailable', '2026-02-05 10:25:09', '2026-02-05 10:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `academic_year`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026', '1', 'active', '2026-02-01 10:54:03', '2026-02-01 10:54:03'),
(2, 2, 7, '2026', '2', 'active', '2026-02-06 07:27:47', '2026-02-06 07:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `slot_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `slot_name`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(1, 'Slot 2', '08:00:00', '08:59:00', 'active', '2026-01-31 11:58:17'),
(2, 'Slot 3', '09:00:00', '09:59:00', 'active', '2026-01-31 11:58:17'),
(3, 'Slot 4', '10:00:00', '10:59:00', 'active', '2026-01-31 11:58:17'),
(4, 'Slot 5', '11:00:00', '11:59:00', 'active', '2026-01-31 11:58:17'),
(5, 'Slot 6', '12:00:00', '12:59:00', 'active', '2026-01-31 11:58:17'),
(6, 'Slot 7', '13:00:00', '13:59:00', 'active', '2026-01-31 11:58:17'),
(7, 'Slot 8', '14:00:00', '14:59:00', 'active', '2026-01-31 11:58:17'),
(8, 'Slot 9', '15:00:00', '15:59:00', 'active', '2026-01-31 11:58:17'),
(9, 'Slot 1', '07:00:00', '07:59:00', 'active', '2026-02-03 17:00:07'),
(10, 'Slot 10', '16:00:00', '16:59:00', 'active', '2026-02-06 07:25:27'),
(11, 'Slot 11', '17:00:00', '17:59:00', 'active', '2026-02-06 07:25:57'),
(12, 'Slot 12', '18:00:00', '18:59:00', 'active', '2026-02-06 07:26:23'),
(13, 'Slot 13', '19:00:00', '19:59:00', 'active', '2026-02-06 07:26:39'),
(15, 'Slot 14', '20:00:00', '21:00:00', 'active', '2026-02-06 07:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('admin','teacher','student') DEFAULT 'teacher',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_image`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@school.local', '$2y$10$gYUaIxvTaT7oJ5jMJOQV6esxSsdGie61Wi8VytmPAZIXT2ztOoW/S', 'profile_1_1770397479.png', 'admin', 'active', '2026-01-31 11:58:17', '2026-02-06 17:04:39'),
(2, 'spidermatt', 'ryanmateo@gmail.com', '$2y$10$X9LiWAanEBUnQS31gg/0guOTFqO9DZuVeXUv8ooYGomEAtstyvOJ2', 'profile_2_1770128355.jpg', 'teacher', 'active', '2026-02-01 10:31:44', '2026-02-05 07:17:25'),
(3, 'cathy123', 'Cathllena@gmail.com', '$2y$10$lREMXOWcpmtBMMx0rVelseBxEXXDWzpzl1PJmYYjBDFKeV8/mDB5u', NULL, 'teacher', 'active', '2026-02-03 17:11:58', '2026-02-03 17:11:58'),
(6, 'eizleyas', 'eizley@gmail.com', '$2y$10$aIqGesMVAutLyC0RDK47QOYTprRR0us3NhF5ffK5IlV2VueqDU5mC', 'profile_6_1770398139.jpg', 'student', 'active', '2026-02-04 11:26:53', '2026-02-07 03:44:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_teacher` (`teacher_id`),
  ADD KEY `idx_announcements_subject` (`subject_id`),
  ADD KEY `idx_announcements_status` (`status`,`publish_date`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`announcement_id`,`student_user_id`),
  ADD KEY `idx_announcement_reads_student` (`student_user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_classroom_building` (`building`);

--
-- Indexes for table `class_assignments`
--
ALTER TABLE `class_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_assignment` (`class_id`,`subject_id`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_class_assignment_academic` (`academic_year`);

--
-- Indexes for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `classroom_id` (`classroom_id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`teacher_id`,`classroom_id`,`day_of_week`,`time_slot_id`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `time_slot_id` (`time_slot_id`),
  ADD KEY `idx_schedule_teacher` (`teacher_id`),
  ADD KEY `idx_schedule_classroom` (`classroom_id`),
  ADD KEY `idx_schedule_day_time` (`day_of_week`,`time_slot_id`),
  ADD KEY `idx_schedule_academic` (`academic_year`,`semester`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_user_id`,`subject_id`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_subject_department` (`department`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_teacher_user` (`user_id`),
  ADD KEY `idx_teacher_department` (`department`),
  ADD KEY `idx_teacher_status` (`status`);

--
-- Indexes for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_availability` (`teacher_id`,`day_of_week`,`time_slot_id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`teacher_id`,`subject_id`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`slot_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `class_assignments`
--
ALTER TABLE `class_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_reservations`
--
ALTER TABLE `room_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD CONSTRAINT `announcement_reads_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reads_ibfk_2` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_assignments`
--
ALTER TABLE `class_assignments`
  ADD CONSTRAINT `class_assignments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_assignments_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD CONSTRAINT `room_reservations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_reservations_ibfk_2` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`),
  ADD CONSTRAINT `room_reservations_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`),
  ADD CONSTRAINT `schedules_ibfk_4` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD CONSTRAINT `teacher_availability_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_availability_ibfk_2` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
