-- =====================================================
-- Hospital Appointment & Patient Record System
-- Database Schema (v2 - aligned with project roadmap)
-- =====================================================

CREATE DATABASE IF NOT EXISTS city_hospital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE city_hospital;

-- =====================================================
-- 1. USERS  — shared login table (patient / doctor / admin)
-- =====================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    phone VARCHAR(20),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. DEPARTMENTS — e.g. Cardiology, Pediatrics
-- Doctors belong to a department. Patients pick a
-- department first when booking, then a doctor.
-- =====================================================
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 3. PATIENTS — extends users
-- =====================================================
CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address VARCHAR(255),
    blood_group VARCHAR(5),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 4. DOCTORS — extends users, belongs to a department
-- =====================================================
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    department_id INT,
    specialization VARCHAR(150) NOT NULL,
    qualification VARCHAR(255),
    license_number VARCHAR(100),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 5. DOCTOR_SCHEDULES — weekly recurring availability
-- =====================================================
CREATE TABLE doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration_minutes INT NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
) ENGINE=InnoDB;

-- =====================================================
-- 6. APPOINTMENTS — central booking table
-- =====================================================
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    reason_for_visit VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_slot (doctor_id, appointment_date, appointment_time)
) ENGINE=InnoDB;

-- =====================================================
-- 7. MEDICAL_RECORDS — created by doctors after a visit
-- =====================================================
CREATE TABLE medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    appointment_id INT,
    diagnosis VARCHAR(255),
    symptoms TEXT,
    treatment_notes TEXT,
    prescription TEXT,
    allergies VARCHAR(255),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Indexes for common queries
-- =====================================================
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX idx_medical_records_patient ON medical_records(patient_id);
CREATE INDEX idx_doctors_department ON doctors(department_id);

-- =====================================================
-- Seed data
-- =====================================================

-- Departments
INSERT INTO departments (name, description) VALUES
('Cardiology', 'Heart and cardiovascular care'),
('Pediatrics', 'Medical care for infants, children, and adolescents'),
('Dermatology', 'Skin, hair, and nail conditions'),
('Orthopedics', 'Bones, joints, muscles, and ligaments'),
('General Medicine', 'General checkups and common illnesses');

-- Admin account — password: Admin@123 (hash generated with PHP password_hash)
INSERT INTO users (full_name, email, password_hash, role, phone)
VALUES ('System Admin', 'admin@hospital.com', '$2y$10$UQ2OLRSh9P3vlMEOX/TbpeVhdYFreizBkG3ZlsidzEJBRsBsDeAIm', 'admin', '0000000000');
-- Login with: admin@hospital.com / Admin@123
