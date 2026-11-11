-- =====================================================================
-- SMART CAMPUSCONNECT - UNIFIED CAMPUS MANAGEMENT PORTAL
-- Database Schema and Demo Seed Data
-- Database Name: smart_campusconnect
-- Prepared for BCA 5th Semester Major Project 1
-- Compatible with MySQL / MariaDB (XAMPP / phpMyAdmin)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `smart_campusconnect` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_campusconnect`;

-- Disable foreign key checks while setting up tables
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `timetable`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `faculty_subjects`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
-- TABLE 1: users
-- Core authentication table for all three roles (admin, faculty, student)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'faculty', 'student') NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 2: departments
-- Academic departments (e.g., Computer Applications, CSE, IT)
-- ---------------------------------------------------------------------
CREATE TABLE `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dept_name` VARCHAR(100) NOT NULL,
  `dept_code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 3: courses
-- Academic programs linked to departments
-- ---------------------------------------------------------------------
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dept_id` INT NOT NULL,
  `course_name` VARCHAR(120) NOT NULL,
  `course_code` VARCHAR(30) NOT NULL UNIQUE,
  `duration_years` INT NOT NULL DEFAULT 3,
  `total_semesters` INT NOT NULL DEFAULT 6,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 4: subjects
-- Subjects taught in each course and semester
-- ---------------------------------------------------------------------
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `semester` INT NOT NULL,
  `subject_name` VARCHAR(120) NOT NULL,
  `subject_code` VARCHAR(30) NOT NULL UNIQUE,
  `credits` INT NOT NULL DEFAULT 4,
  `type` ENUM('Theory', 'Practical', 'Project') NOT NULL DEFAULT 'Theory',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_subjects_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  INDEX `idx_subject_sem` (`course_id`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 5: faculty
-- Detailed faculty profiles linked to users table
-- ---------------------------------------------------------------------
CREATE TABLE `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `employee_id` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NULL,
  `designation` VARCHAR(60) NOT NULL,
  `department_id` INT NOT NULL,
  `qualification` VARCHAR(100) NULL,
  `joining_date` DATE NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_faculty.png',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_faculty_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 6: faculty_subjects
-- Mapping faculty to subjects taught
-- ---------------------------------------------------------------------
CREATE TABLE `faculty_subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2026-2027',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fs_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_faculty_subject_year` (`faculty_id`, `subject_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 7: students
-- Detailed student profiles linked to users table
-- ---------------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `enrollment_no` VARCHAR(40) NOT NULL UNIQUE,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `dob` DATE NULL,
  `course_id` INT NOT NULL,
  `current_semester` INT NOT NULL DEFAULT 1,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2026-2027',
  `address` TEXT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_student.png',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT,
  INDEX `idx_students_class` (`course_id`, `current_semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 8: attendance
-- Daily attendance records per subject and student
-- ---------------------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
  `remarks` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_attendance_entry` (`student_id`, `subject_id`, `attendance_date`),
  INDEX `idx_att_date` (`attendance_date`),
  INDEX `idx_att_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 9: assignments
-- Assignments created by faculty for their subjects
-- ---------------------------------------------------------------------
CREATE TABLE `assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `file_path` VARCHAR(255) NULL,
  `due_date` DATETIME NOT NULL,
  `max_marks` INT NOT NULL DEFAULT 100,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_assign_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assign_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  INDEX `idx_assign_due` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 10: submissions
-- Student uploads and grading for assignments
-- ---------------------------------------------------------------------
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `submission_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Submitted', 'Graded', 'Late', 'Resubmission') NOT NULL DEFAULT 'Submitted',
  `marks_obtained` DECIMAL(5,2) NULL,
  `feedback` TEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_sub_assignment_student` (`assignment_id`, `student_id`),
  INDEX `idx_sub_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 11: notices
-- Campus-wide, student, or faculty announcements
-- ---------------------------------------------------------------------
CREATE TABLE `notices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `target_audience` ENUM('all', 'students', 'faculty') NOT NULL DEFAULT 'all',
  `category` ENUM('Academic', 'Examination', 'Event', 'Urgent', 'General') NOT NULL DEFAULT 'General',
  `posted_by_user_id` INT NOT NULL,
  `attachment_path` VARCHAR(255) NULL,
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notices_user` FOREIGN KEY (`posted_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notice_audience` (`target_audience`),
  INDEX `idx_notice_pinned` (`is_pinned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 12: timetable
-- Weekly class schedule by course, semester, day and time
-- ---------------------------------------------------------------------
CREATE TABLE `timetable` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `semester` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_no` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tt_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  INDEX `idx_tt_schedule` (`course_id`, `semester`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 13: complaints
-- Student grievance redressal system with status tracking
-- ---------------------------------------------------------------------
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `category` ENUM('Infrastructure', 'Classroom', 'Faculty', 'Library', 'Computer Lab', 'Other') NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
  `admin_remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaint_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  INDEX `idx_complaint_status` (`status`),
  INDEX `idx_complaint_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;


-- =====================================================================
-- SEED DATA INSERTION
-- Standard demo passwords:
-- Admin: Admin@123     (Hash: $2y$10$GOZo5OS2yeGuGFvz/96RKOhGmnKUiRwcQzJ1C5NH/1Nc8pRQmmO0K)
-- Faculty: Faculty@123 (Hash: $2y$10$.SGUnJDKB7uKJ1hcX45g.OT/8/kp5a.jDjAYsbc8TUvZqSx.p8oCS)
-- Student: Student@123 (Hash: $2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG)
-- =====================================================================

-- 1. Insert Users
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`) VALUES
-- Admin (ID: 1)
(1, 'admin', 'admin@campusconnect.edu', '$2y$10$GOZo5OS2yeGuGFvz/96RKOhGmnKUiRwcQzJ1C5NH/1Nc8pRQmmO0K', 'admin', 'active'),

-- Faculty (IDs: 2, 3, 4, 5)
(2, 'dr_sharma', 'prof.sharma@campusconnect.edu', '$2y$10$.SGUnJDKB7uKJ1hcX45g.OT/8/kp5a.jDjAYsbc8TUvZqSx.p8oCS', 'faculty', 'active'),
(3, 'anita_verma', 'anita.verma@campusconnect.edu', '$2y$10$.SGUnJDKB7uKJ1hcX45g.OT/8/kp5a.jDjAYsbc8TUvZqSx.p8oCS', 'faculty', 'active'),
(4, 'vikram_singh', 'vikram.singh@campusconnect.edu', '$2y$10$.SGUnJDKB7uKJ1hcX45g.OT/8/kp5a.jDjAYsbc8TUvZqSx.p8oCS', 'faculty', 'active'),
(5, 'priya_nair', 'priya.nair@campusconnect.edu', '$2y$10$.SGUnJDKB7uKJ1hcX45g.OT/8/kp5a.jDjAYsbc8TUvZqSx.p8oCS', 'faculty', 'active'),

-- Students (IDs: 6 through 15)
(6, 'bca_rahul', 'rahul.mehta@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(7, 'bca_sneha', 'sneha.patel@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(8, 'bca_amit', 'amit.kumar@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(9, 'bca_pooja', 'pooja.sharma@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(10, 'bca_rohit', 'rohit.verma@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(11, 'bca_ananya', 'ananya.sen@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(12, 'mca_karan', 'karan.malhotra@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(13, 'btech_deepika', 'deepika.joshi@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(14, 'btech_vivek', 'vivek.tiwari@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active'),
(15, 'bca_neha', 'neha.gupta@student.edu', '$2y$10$iO3mONnplGd1JHNaBDrRSu8XYBBzAFJr3Ixrvybx/cUiCZPGxTEPG', 'student', 'active');

-- 2. Insert Departments
INSERT INTO `departments` (`id`, `dept_name`, `dept_code`, `description`) VALUES
(1, 'Department of Computer Applications', 'DCA', 'Undergraduate and postgraduate programs in software design, web computing, and systems analysis.'),
(2, 'Department of Computer Science & Engineering', 'CSE', 'Engineering programs focusing on system architecture, algorithms, and artificial intelligence.'),
(3, 'Department of Information Technology', 'DIT', 'Focused on enterprise networking, cloud systems, and applied cybersecurity.');

-- 3. Insert Courses
INSERT INTO `courses` (`id`, `dept_id`, `course_name`, `course_code`, `duration_years`, `total_semesters`) VALUES
(1, 1, 'Bachelor of Computer Applications', 'BCA', 3, 6),
(2, 1, 'Master of Computer Applications', 'MCA', 2, 4),
(3, 2, 'B.Tech in Computer Science & Engineering', 'BTECH-CSE', 4, 8),
(4, 3, 'Bachelor of Science in Information Technology', 'BSC-IT', 3, 6);

-- 4. Insert Subjects
INSERT INTO `subjects` (`id`, `course_id`, `semester`, `subject_name`, `subject_code`, `credits`, `type`) VALUES
-- BCA Semester 5
(1, 1, 5, 'Web Development with PHP & MySQL', 'BCA501', 4, 'Practical'),
(2, 1, 5, 'Java Enterprise Programming', 'BCA502', 4, 'Practical'),
(3, 1, 5, 'Software Engineering & Agile Methodologies', 'BCA503', 4, 'Theory'),
(4, 1, 5, 'Cloud Computing & Virtualization', 'BCA504', 3, 'Theory'),
(5, 1, 5, 'Major Project Phase 1', 'BCA505', 6, 'Project'),
-- BCA Semester 3
(6, 1, 3, 'Data Structures & Algorithms', 'BCA301', 4, 'Practical'),
(7, 1, 3, 'Database Management Systems', 'BCA302', 4, 'Practical'),
(8, 1, 3, 'Computer Networks', 'BCA303', 3, 'Theory'),
-- MCA Semester 1
(9, 2, 1, 'Advanced Database Systems', 'MCA101', 4, 'Theory'),
(10, 2, 1, 'Object Oriented Software Engineering', 'MCA102', 4, 'Theory'),
-- B.Tech CSE Semester 5
(11, 3, 5, 'Operating Systems & System Programming', 'CSE501', 4, 'Theory'),
(12, 3, 5, 'Artificial Intelligence Fundamentals', 'CSE502', 4, 'Theory');

-- 5. Insert Faculty
INSERT INTO `faculty` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `designation`, `department_id`, `qualification`, `joining_date`, `profile_pic`) VALUES
(1, 2, 'FAC-DCA-001', 'Rajesh', 'Sharma', 'prof.sharma@campusconnect.edu', '+91 9876543210', 'Professor & HOD', 1, 'Ph.D. in Computer Science', '2016-07-15', 'default_faculty.png'),
(2, 3, 'FAC-DCA-002', 'Anita', 'Verma', 'anita.verma@campusconnect.edu', '+91 9876543211', 'Assistant Professor', 1, 'M.Tech, MCA', '2019-01-10', 'default_faculty.png'),
(3, 4, 'FAC-CSE-003', 'Vikram', 'Singh', 'vikram.singh@campusconnect.edu', '+91 9876543212', 'Associate Professor', 2, 'Ph.D. in AI & Robotics', '2017-08-01', 'default_faculty.png'),
(4, 5, 'FAC-DCA-004', 'Priya', 'Nair', 'priya.nair@campusconnect.edu', '+91 9876543213', 'Assistant Professor', 1, 'M.Tech in Software Engineering', '2021-09-01', 'default_faculty.png');

-- 6. Insert Faculty Subject Assignments
INSERT INTO `faculty_subjects` (`id`, `faculty_id`, `subject_id`, `academic_year`) VALUES
(1, 1, 1, '2026-2027'), -- Dr. Rajesh Sharma -> Web Development with PHP & MySQL (BCA501)
(2, 1, 5, '2026-2027'), -- Dr. Rajesh Sharma -> Major Project Phase 1 (BCA505)
(3, 2, 2, '2026-2027'), -- Prof. Anita Verma -> Java Enterprise Programming (BCA502)
(4, 4, 3, '2026-2027'), -- Prof. Priya Nair -> Software Engineering & Agile (BCA503)
(5, 4, 4, '2026-2027'), -- Prof. Priya Nair -> Cloud Computing (BCA504)
(6, 3, 11, '2026-2027'), -- Dr. Vikram Singh -> Operating Systems (CSE501)
(7, 3, 12, '2026-2027'); -- Dr. Vikram Singh -> AI Fundamentals (CSE502)

-- 7. Insert Students
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `first_name`, `last_name`, `email`, `phone`, `gender`, `dob`, `course_id`, `current_semester`, `academic_year`, `address`, `profile_pic`) VALUES
(1, 6, 'EN2024BCA001', 'Rahul', 'Mehta', 'rahul.mehta@student.edu', '+91 9123456780', 'Male', '2004-05-14', 1, 5, '2026-2027', 'Flat 402, Green Avenue, Campus Road', 'default_student.png'),
(2, 7, 'EN2024BCA002', 'Sneha', 'Patel', 'sneha.patel@student.edu', '+91 9123456781', 'Female', '2004-09-22', 1, 5, '2026-2027', '78 Sunshine Heights, University Enclave', 'default_student.png'),
(3, 8, 'EN2024BCA003', 'Amit', 'Kumar', 'amit.kumar@student.edu', '+91 9123456782', 'Male', '2003-12-05', 1, 5, '2026-2027', 'B-14 Civil Lines, Near Tech Park', 'default_student.png'),
(4, 9, 'EN2024BCA004', 'Pooja', 'Sharma', 'pooja.sharma@student.edu', '+91 9123456783', 'Female', '2004-03-18', 1, 5, '2026-2027', '12 Rose Valley, Subhash Nagar', 'default_student.png'),
(5, 10, 'EN2025BCA015', 'Rohit', 'Verma', 'rohit.verma@student.edu', '+91 9123456784', 'Male', '2005-08-11', 1, 3, '2026-2027', 'Plot 55, Lake View Colony', 'default_student.png'),
(6, 11, 'EN2025BCA016', 'Ananya', 'Sen', 'ananya.sen@student.edu', '+91 9123456785', 'Female', '2005-04-30', 1, 3, '2026-2027', '43 Hill Top Residence', 'default_student.png'),
(7, 12, 'EN2026MCA005', 'Karan', 'Malhotra', 'karan.malhotra@student.edu', '+91 9123456786', 'Male', '2002-11-19', 2, 1, '2026-2027', 'Tower 3, Metro Residency', 'default_student.png'),
(8, 13, 'EN2024CSE042', 'Deepika', 'Joshi', 'deepika.joshi@student.edu', '+91 9123456787', 'Female', '2004-07-25', 3, 5, '2026-2027', 'B-303 Royal Palms', 'default_student.png'),
(9, 14, 'EN2025CSE088', 'Vivek', 'Tiwari', 'vivek.tiwari@student.edu', '+91 9123456788', 'Male', '2005-01-14', 3, 3, '2026-2027', '21 Shanti Kunj, Station Road', 'default_student.png'),
(10, 15, 'EN2026BCA099', 'Neha', 'Gupta', 'neha.gupta@student.edu', '+91 9123456789', 'Female', '2006-02-10', 1, 1, '2026-2027', '88 Anand Vihar, Sector 4', 'default_student.png');

-- 8. Insert Attendance Records (BCA 5th Sem Students across several dates)
INSERT INTO `attendance` (`student_id`, `subject_id`, `faculty_id`, `attendance_date`, `status`, `remarks`) VALUES
-- Date: 2026-11-02 (Web Dev - Subject 1)
(1, 1, 1, '2026-11-02', 'Present', 'Attentive'),
(2, 1, 1, '2026-11-02', 'Present', 'Good participation'),
(3, 1, 1, '2026-11-02', 'Absent', 'Medical leave'),
(4, 1, 1, '2026-11-02', 'Present', 'On time'),
-- Date: 2026-11-04 (Java - Subject 2)
(1, 2, 2, '2026-11-04', 'Present', 'Completed lab exercise'),
(2, 2, 2, '2026-11-04', 'Present', 'Completed lab exercise'),
(3, 2, 2, '2026-11-04', 'Late', '15 mins late'),
(4, 2, 2, '2026-11-04', 'Present', 'Completed lab exercise'),
-- Date: 2026-11-05 (Software Eng - Subject 3)
(1, 3, 4, '2026-11-05', 'Present', 'Present in sprint meeting'),
(2, 3, 4, '2026-11-05', 'Present', 'Present in sprint meeting'),
(3, 3, 4, '2026-11-05', 'Present', 'Active'),
(4, 3, 4, '2026-11-05', 'Absent', 'Uninformed leave'),
-- Date: 2026-11-06 (Web Dev - Subject 1)
(1, 1, 1, '2026-11-06', 'Present', 'Demonstrated PHP session code'),
(2, 1, 1, '2026-11-06', 'Present', 'Active in class discussion'),
(3, 1, 1, '2026-11-06', 'Present', 'Caught up with notes'),
(4, 1, 1, '2026-11-06', 'Present', 'On time'),
-- Date: 2026-11-09 (Cloud Computing - Subject 4)
(1, 4, 4, '2026-11-09', 'Present', 'Configured AWS lab'),
(2, 4, 4, '2026-11-09', 'Present', 'Configured AWS lab'),
(3, 4, 4, '2026-11-09', 'Present', 'Configured AWS lab'),
(4, 4, 4, '2026-11-09', 'Present', 'Configured AWS lab'),
-- Date: 2026-11-10 (Web Dev - Subject 1)
(1, 1, 1, '2026-11-10', 'Present', 'Database connectivity workshop'),
(2, 1, 1, '2026-11-10', 'Late', 'Transit delay'),
(3, 1, 1, '2026-11-10', 'Present', 'Completed query tasks'),
(4, 1, 1, '2026-11-10', 'Present', 'Completed query tasks');

-- 9. Insert Assignments
INSERT INTO `assignments` (`id`, `subject_id`, `faculty_id`, `title`, `description`, `file_path`, `due_date`, `max_marks`) VALUES
(1, 1, 1, 'PHP & MySQL Authentication System', 'Build a secure registration and login system with bcrypt password hashing, prepared PDO statements, and role-based session authorization. Submit a zipped folder or PDF documentation with source code.', 'sample_assignment1.pdf', '2026-11-20 23:59:00', 50),
(2, 2, 2, 'Java Spring Boot REST API for Student Portal', 'Develop RESTful endpoints for student registration, profile fetching, and grade calculation using Spring Data JPA. Include Swagger documentation and unit test reports.', 'sample_assignment2.pdf', '2026-11-25 18:00:00', 50),
(3, 3, 4, 'Agile Sprint Planning & SRS Document', 'Draft a complete IEEE 830 compliant Software Requirements Specification for a unified campus portal including Use Case diagrams, ER diagrams, and Sprint Backlog.', NULL, '2026-11-18 17:00:00', 40),
(4, 4, 4, 'AWS EC2 & S3 Deployment Architecture', 'Design an enterprise cloud architecture diagram for hosting a high-traffic university web portal with load balancers, auto-scaling groups, and private RDS MySQL subnet.', 'sample_assignment4.pdf', '2026-11-30 23:59:00', 30);

-- 10. Insert Submissions
INSERT INTO `submissions` (`id`, `assignment_id`, `student_id`, `file_path`, `submission_date`, `status`, `marks_obtained`, `feedback`) VALUES
(1, 1, 1, 'assignment_1_student_1.zip', '2026-11-10 14:32:00', 'Graded', 48.00, 'Excellent implementation of PDO prepared statements and flash messages! Clean code structure.'),
(2, 1, 2, 'assignment_1_student_2.zip', '2026-11-10 16:15:00', 'Graded', 45.50, 'Well designed authentication workflow. Add stricter password complexity validation next time.'),
(3, 1, 3, 'assignment_1_student_3.zip', '2026-11-11 11:20:00', 'Submitted', NULL, NULL),
(4, 3, 1, 'assignment_3_student_1.pdf', '2026-11-09 19:40:00', 'Graded', 38.00, 'Comprehensive SRS document with well-drawn UML sequence and ER diagrams.'),
(5, 3, 4, 'assignment_3_student_4.pdf', '2026-11-11 10:05:00', 'Submitted', NULL, NULL);

-- 11. Insert Notices
INSERT INTO `notices` (`id`, `title`, `content`, `target_audience`, `category`, `posted_by_user_id`, `attachment_path`, `is_pinned`) VALUES
(1, 'Mid-Semester Examination Schedule - Winter 2026', 'All students of BCA, MCA, and B.Tech are hereby notified that the Mid-Semester Theory and Practical Examinations will commence on November 28, 2026. Detailed seating arrangement and hall tickets will be issued via the portal next week. Minimum 75% attendance is mandatory to appear for examinations.', 'all', 'Examination', 1, 'exam_schedule_2026.pdf', 1),
(2, 'Major Project Phase 1 Synopsis Submission Deadline', 'All 5th Semester BCA students must submit their Major Project Phase 1 synopses approved by their respective faculty guides on or before November 20, 2026. Viva-voce will be held in the DCA Seminar Hall.', 'students', 'Academic', 2, 'project_synopsis_format.pdf', 1),
(3, 'Faculty Development Program on Cloud Computing & AI', 'The Department of Computer Applications is organizing a 3-day Faculty Development Program on Enterprise Cloud Architecture and Generative AI from November 22-24, 2026. All faculty members are invited to register.', 'faculty', 'Event', 1, NULL, 0),
(4, 'Annual Hackathon - HackNova 2026 Registration Open', 'Registrations are now open for HackNova 2026, our university flagship 36-hour hackathon! Cash prizes up to Rs. 1,00,000 for top 3 teams. Students can form teams of 3 to 4 members. Register online before November 18.', 'students', 'Event', 1, 'hacknova_brochure.pdf', 0),
(5, 'Campus Maintenance: Server Downtime Notice', 'Scheduled maintenance on the central campus fiber network will take place on Saturday night from 11:00 PM to 3:00 AM. Portal services will remain functional on cloud redundancy.', 'all', 'General', 1, NULL, 0);

-- 12. Insert Timetable Entries (BCA 5th Sem, BCA 3rd Sem, B.Tech CSE 5th Sem)
INSERT INTO `timetable` (`course_id`, `semester`, `subject_id`, `faculty_id`, `day_of_week`, `start_time`, `end_time`, `room_no`) VALUES
-- BCA 5th Semester Schedule
(1, 5, 1, 1, 'Monday', '09:00:00', '10:00:00', 'Lab 3 (Web Tech Lab)'),
(1, 5, 2, 2, 'Monday', '10:00:00', '11:00:00', 'Room 304'),
(1, 5, 3, 4, 'Monday', '11:15:00', '12:15:00', 'Room 304'),
(1, 5, 4, 4, 'Tuesday', '09:00:00', '10:00:00', 'Room 305'),
(1, 5, 1, 1, 'Tuesday', '10:00:00', '12:00:00', 'Lab 3 (Web Tech Lab)'),
(1, 5, 2, 2, 'Wednesday', '09:00:00', '11:00:00', 'Lab 2 (Java Lab)'),
(1, 5, 5, 1, 'Wednesday', '11:15:00', '01:15:00', 'Project Seminar Hall'),
(1, 5, 3, 4, 'Thursday', '09:00:00', '10:00:00', 'Room 304'),
(1, 5, 4, 4, 'Thursday', '10:00:00', '11:00:00', 'Room 305'),
(1, 5, 1, 1, 'Friday', '09:00:00', '10:00:00', 'Lab 3 (Web Tech Lab)'),
(1, 5, 2, 2, 'Friday', '10:00:00', '11:00:00', 'Room 304'),
(1, 5, 5, 1, 'Friday', '11:15:00', '01:15:00', 'Lab 3 (Web Tech Lab)'),

-- BCA 3rd Semester Schedule
(1, 3, 6, 2, 'Monday', '09:00:00', '11:00:00', 'Lab 1 (DSA Lab)'),
(1, 3, 7, 1, 'Tuesday', '11:00:00', '01:00:00', 'Lab 2 (DBMS Lab)'),

-- B.Tech CSE 5th Semester Schedule
(3, 5, 11, 3, 'Monday', '10:00:00', '11:00:00', 'Room 402'),
(3, 5, 12, 3, 'Tuesday', '09:00:00', '10:00:00', 'Room 402');

-- 13. Insert Complaints
INSERT INTO `complaints` (`id`, `student_id`, `category`, `subject`, `description`, `status`, `admin_remarks`) VALUES
(1, 1, 'Computer Lab', 'Lab 3 Workstation #14 PHP CLI and MySQL service not booting', 'In DCA Lab 3, PC #14 exhibits hard drive read errors and XAMPP service does not start properly. Kindly replace or re-image.', 'Resolved', 'System engineer replaced the SSD and re-installed XAMPP. Workstation is now fully operational.'),
(2, 2, 'Classroom', 'Projector flickering in Lecture Hall 304', 'The ceiling mount HDMI cable causes screen flickering every few minutes during Java and Web lectures, disrupting slide viewing.', 'In Progress', 'Estate department assigned a technician. Replacement HDMI extender kit ordered.'),
(3, 3, 'Library', 'Request for additional copies of PHP 8 & MySQL Web Development book', 'The departmental library only holds 2 copies of Luke Welling\'s PHP and MySQL Web Development. Please procure 4 more copies for BCA 5th Sem students.', 'Pending', NULL),
(4, 4, 'Infrastructure', 'Wi-Fi connectivity dead zone in DCA 2nd Floor Corridor', 'The signal from access point AP-DCA-02 drops frequently near Room 304 and 305. Speeds drop below 500kbps.', 'In Progress', 'IT network administrator is testing channel bandwidth and will install an additional repeater by Friday.');
