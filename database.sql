-- Teacher Faculty Management System Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS teacher_management_system;
USE teacher_management_system;

-- Users Table (Admin and Teachers)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'teacher',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Teachers Table
CREATE TABLE IF NOT EXISTS teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    qualification VARCHAR(150),
    hire_date DATE,
    bio TEXT,
    profile_image VARCHAR(255),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email (email)
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(50) UNIQUE NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    credits INT DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classrooms/Rooms Table
CREATE TABLE IF NOT EXISTS classrooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    building VARCHAR(100),
    capacity INT,
    room_type ENUM('classroom', 'lab', 'seminar', 'auditorium') DEFAULT 'classroom',
    equipment VARCHAR(255),
    floor INT,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Time Slots Table
CREATE TABLE IF NOT EXISTS time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (slot_name)
);

-- Schedules Table (Timetable)
CREATE TABLE IF NOT EXISTS schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    classroom_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    time_slot_id INT NOT NULL,
    semester VARCHAR(20),
    academic_year VARCHAR(20),
    status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_schedule (teacher_id, classroom_id, day_of_week, time_slot_id, academic_year)
);

-- Teacher Subject Assignments Table
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (teacher_id, subject_id, academic_year)
);

-- Classes Table (Groups/Sections)
CREATE TABLE IF NOT EXISTS classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    semester INT,
    strength INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Class-Subject-Teacher Assignment Table
CREATE TABLE IF NOT EXISTS class_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    semester VARCHAR(20),
    academic_year VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_class_assignment (class_id, subject_id, academic_year)
);

-- Audit Log Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    table_name VARCHAR(100),
    record_id INT,
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create Indexes for Better Performance
CREATE INDEX idx_teacher_user ON teachers(user_id);
CREATE INDEX idx_teacher_department ON teachers(department);
CREATE INDEX idx_teacher_status ON teachers(status);
CREATE INDEX idx_schedule_teacher ON schedules(teacher_id);
CREATE INDEX idx_schedule_classroom ON schedules(classroom_id);
CREATE INDEX idx_schedule_day_time ON schedules(day_of_week, time_slot_id);
CREATE INDEX idx_schedule_academic ON schedules(academic_year, semester);
CREATE INDEX idx_classroom_building ON classrooms(building);
CREATE INDEX idx_subject_department ON subjects(department);
CREATE INDEX idx_class_assignment_academic ON class_assignments(academic_year);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, password, role, status) VALUES 
('admin', 'admin@school.local', '$2y$10$92IXUNpkm8VnCXIlqUIeOu8cH3FQGcBR7qp7C2xQFqEzKnqd3NUPA', 'admin', 'active');

-- Insert Sample Time Slots
INSERT INTO time_slots (slot_name, start_time, end_time) VALUES 
('Slot 1', '08:00:00', '08:50:00'),
('Slot 2', '09:00:00', '09:50:00'),
('Slot 3', '10:00:00', '10:50:00'),
('Slot 4', '11:00:00', '11:50:00'),
('Slot 5', '12:00:00', '12:50:00'),
('Slot 6', '13:30:00', '14:20:00'),
('Slot 7', '14:30:00', '15:20:00'),
('Slot 8', '15:30:00', '16:20:00');

-- Insert Sample Classrooms
INSERT INTO classrooms (room_number, room_name, building, capacity, room_type, floor) VALUES 
('A101', 'Room A101', 'Building A', 45, 'classroom', 1),
('A102', 'Room A102', 'Building A', 45, 'classroom', 1),
('B101', 'Lab B101', 'Building B', 30, 'lab', 1),
('B201', 'Seminar B201', 'Building B', 50, 'seminar', 2),
('C101', 'Auditorium C101', 'Building C', 200, 'auditorium', 1);

-- Insert Sample Subjects
INSERT INTO subjects (subject_code, subject_name, department, credits) VALUES 
('CS101', 'Introduction to Programming', 'Computer Science', 4),
('CS201', 'Data Structures', 'Computer Science', 4),
('MATH101', 'Calculus I', 'Mathematics', 4),
('PHYS101', 'Physics I', 'Physics', 4),
('ENG101', 'English Literature', 'English', 3);

-- Insert Sample Classes
INSERT INTO classes (class_name, class_code, department, semester) VALUES 
('CS-A', 'CS-2024-A', 'Computer Science', 1),
('CS-B', 'CS-2024-B', 'Computer Science', 1),
('MATH-A', 'MATH-2024-A', 'Mathematics', 1);
