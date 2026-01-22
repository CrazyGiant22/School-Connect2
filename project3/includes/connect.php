<?php
// Centralized database connection file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "schoolproject";

$data = mysqli_connect($host, $user, $password, $db);

if (!$data) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: Set charset to utf8
mysqli_set_charset($data, "utf8");

// Load database helper functions for prepared statements
require_once __DIR__ . '/db-helpers.php';
require_once __DIR__ . '/security-helpers.php';
require_once __DIR__ . '/email-helpers.php';

// -----------------------------------------------------------------------------
// Academic term tables (for sessions/terms linked with students)
// -----------------------------------------------------------------------------

// Main term table: e.g. "First Term 2024", "Second Term 2024", etc.
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS academic_terms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  academic_year INT NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_year_name (academic_year, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Link table: which students belong to which term
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS student_terms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  term_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_term (student_id, term_id),
  INDEX idx_student (student_id),
  INDEX idx_term (term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// -----------------------------------------------------------------------------
// Core application tables (basic bootstrap so pages don't crash on fresh DB)
// -----------------------------------------------------------------------------

// Users table (admin, lecturer, student)
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS user (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  usertype VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Courses table
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS course (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  lecturer_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Admission applications table
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS admission (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  message TEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Lecturer-course mapping (many-to-many)
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS lecturer_course (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lecturer_id INT NOT NULL,
  course_id INT NOT NULL,
  UNIQUE KEY uniq_lecturer_course (lecturer_id, course_id),
  INDEX idx_lecturer (lecturer_id),
  INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Student-course mapping
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS student_course (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  UNIQUE KEY uniq_student_course (student_id, course_id),
  INDEX idx_student (student_id),
  INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Legacy enrollment table used by some older pages (safe no-op if unused)
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS student_enrollment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_student (student_id),
  INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Continuous Assessment tables (if not already created elsewhere)
mysqli_query($data, "
CREATE TABLE IF NOT EXISTS ca_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  type VARCHAR(30) NULL,
  max_score DECIMAL(7,2) NOT NULL DEFAULT 100.00,
  weight DECIMAL(6,3) NULL,
  assess_date DATE NULL,
  term_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

mysqli_query($data, "
CREATE TABLE IF NOT EXISTS ca_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT NOT NULL,
  student_id INT NOT NULL,
  score DECIMAL(7,2) NOT NULL,
  remarks VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_assessment_student (assessment_id, student_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Set session variable if available (backward-compatible, no PHP 7+ operators)
if (isset($_SESSION['username'])) {
    $name = $_SESSION['username'];
} elseif (isset($_SESSION['student_id'])) {
    $name = $_SESSION['student_id'];
} else {
    $name = null;
}
