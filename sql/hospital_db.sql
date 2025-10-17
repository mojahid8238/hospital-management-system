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
    email VARCHAR(100) NOT NULL UNIQUE,
    profile_pic VARCHAR(255) DEFAULT 'default-avatar.png',
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctors Table (linked to users)
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    profile_pic VARCHAR(255) DEFAULT 'default-avatar.png',
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
    profile_pic VARCHAR(255) DEFAULT 'default-avatar.png',
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
-- The following ALTER TABLE statements might fail if the columns already exist.
-- This is not an error, and you can safely ignore these errors if the columns are already in your tables.

ALTER TABLE `doctors` ADD `schedule` TIME;
ALTER TABLE `doctors` ADD `image` VARCHAR(255);
ALTER TABLE `doctors` ADD `phone` VARCHAR(20);
ALTER TABLE `patients` ADD `image` VARCHAR(255);
ALTER TABLE `patients` ADD `username` VARCHAR(255);
ALTER TABLE `patients` ADD `phone` VARCHAR(20);
ALTER TABLE `appointments` ADD `image` VARCHAR(255);
ALTER TABLE `appointments` DROP COLUMN IF EXISTS `time`;
ALTER TABLE `users` ADD `name` VARCHAR(255);
ALTER TABLE `admin` ADD `image` VARCHAR(255);

-- Create specializations table
CREATE TABLE IF NOT EXISTS specializations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Populate specializations table
-- Using INSERT IGNORE to avoid errors if the specializations already exist.
INSERT IGNORE INTO specializations (name) VALUES
('Cardiology'),
('Dermatology'),
('Neurology'),
('Oncology'),
('Pediatrics'),
('Psychiatry'),
('Radiology'),
('Urology'),
('Gastroenterology'),
('Endocrinology'),
('Nephrology'),
('Pulmonology'),
('Rheumatology'),
('Ophthalmology'),
('Otolaryngology (ENT)'),
('Gynecology'),
('Orthopedics');

-- Modify doctors table
ALTER TABLE `doctors` ADD `specialization_id` INT;
ALTER TABLE `doctors` ADD FOREIGN KEY (`specialization_id`) REFERENCES `specializations`(`id`);
ALTER TABLE `doctors` DROP COLUMN IF EXISTS `specialization`;
ALTER TABLE `doctors` ADD `degrees` VARCHAR(255);

-- Modify appointments table to include 'Online' and 'Offline' status

