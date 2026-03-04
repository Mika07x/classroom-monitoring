-- Announcements System Database Changes
-- Run this SQL to add announcement functionality to the TFMS

-- Create announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    teacher_id INT NOT NULL,
    subject_id INT NULL, -- NULL for general announcements, specific subject_id for subject-specific
    target_audience ENUM('all_students', 'subject_students') DEFAULT 'subject_students',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    publish_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Create announcement_reads table to track which students have read announcements
CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    announcement_id INT NOT NULL,
    student_user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (announcement_id, student_user_id)
);

-- Add indexes for performance
CREATE INDEX idx_announcements_teacher ON announcements(teacher_id);
CREATE INDEX idx_announcements_subject ON announcements(subject_id);
CREATE INDEX idx_announcements_status ON announcements(status, publish_date);
CREATE INDEX idx_announcement_reads_student ON announcement_reads(student_user_id);

-- Insert sample announcements (optional)
INSERT INTO announcements (title, message, teacher_id, subject_id, target_audience, priority) VALUES
('Welcome to the New Semester', 'Welcome all students to the new academic semester. Please check your schedules and be prepared for classes.', 1, NULL, 'all_students', 'normal'),
('Data Structures Assignment Due', 'Reminder: Your Data Structures assignment is due next Friday. Please submit via the portal.', 1, 2, 'subject_students', 'high'),
('Lab Session Cancelled', 'Tomorrow\'s lab session is cancelled due to maintenance. We will reschedule for next week.', 1, 1, 'subject_students', 'urgent');