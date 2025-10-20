SET FOREIGN_KEY_CHECKS = 0;

-- Create Database
CREATE DATABASE IF NOT EXISTS hospital_db;
USE hospital_db;

-- Shared Users Table for Login/Auth
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Table (linked to users)
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctors Table (linked to users)
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    profile_pic VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Patients Table (linked to users)
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    profile_pic VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appointments Table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Scheduled', 'Completed', 'Cancelled', 'Online', 'Offline') DEFAULT 'Pending',
    type VARCHAR(20) NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Schema Updates
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `schedule` TIME;
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255);
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20);
ALTER TABLE `patients` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255);
ALTER TABLE `patients` ADD COLUMN IF NOT EXISTS `username` VARCHAR(255);
ALTER TABLE `patients` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20);
ALTER TABLE `appointments` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255);
ALTER TABLE `appointments` DROP COLUMN IF EXISTS `time`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `name` VARCHAR(255);
ALTER TABLE `admin` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255);

-- Create specializations table
CREATE TABLE IF NOT EXISTS specializations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Populate specializations table
INSERT IGNORE INTO specializations (name) VALUES
('Cardiology'), ('Dermatology'), ('Neurology'), ('Oncology'), ('Pediatrics'),
('Psychiatry'), ('Radiology'), ('Urology'), ('Gastroenterology'), ('Endocrinology'),
('Nephrology'), ('Pulmonology'), ('Rheumatology'), ('Ophthalmology'), ('Otolaryngology (ENT)'),
('Gynecology'), ('Orthopedics');

-- Modify doctors table
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `specialization_id` INT;
ALTER TABLE `doctors` ADD FOREIGN KEY IF NOT EXISTS (`specialization_id`) REFERENCES `specializations`(`id`);
ALTER TABLE `doctors` DROP COLUMN IF EXISTS `specialization`;
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `degrees` VARCHAR(255);

-- Messaging Tables
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT DEFAULT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_content TEXT NOT NULL,
    message_type VARCHAR(10) NOT NULL DEFAULT 'text',
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant1_id INT NOT NULL,
    participant2_id INT NOT NULL,
    last_message_id INT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    appointment_id INT DEFAULT NULL,
    FOREIGN KEY (participant1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- Update existing default profile pictures to use the full relative path
UPDATE admin SET profile_pic = 'assets/images/default-avatar.png' WHERE profile_pic = 'default-avatar.png';
UPDATE doctors SET profile_pic = 'assets/images/default-avatar.png' WHERE profile_pic = 'default-avatar.png';
UPDATE patients SET profile_pic = 'assets/images/default-avatar.png' WHERE profile_pic = 'default-avatar.png';

SET FOREIGN_KEY_CHECKS = 1;

-- Video Calls Table
CREATE TABLE IF NOT EXISTS video_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'failed') DEFAULT 'scheduled',
    meeting_link VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);
