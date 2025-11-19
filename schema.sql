-- Create database and tables
CREATE DATABASE IF NOT EXISTS question_bank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE question_bank;

CREATE TABLE IF NOT EXISTS department (
  department_id INT AUTO_INCREMENT PRIMARY KEY,
  department_code VARCHAR(20) NOT NULL UNIQUE,
  department_name VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS course (
  course_id INT AUTO_INCREMENT PRIMARY KEY,
  course_name VARCHAR(100) NOT NULL,
  duration VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS subject (
  subject_id INT AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(30),
  subject_name VARCHAR(150) NOT NULL,
  department_id INT NOT NULL,
  semester TINYINT,
  FOREIGN KEY (department_id) REFERENCES department(department_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uploader (
  uploader_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE
);

CREATE TABLE IF NOT EXISTS paper (
  paper_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  department_id INT NOT NULL,
  course_id INT DEFAULT NULL,
  subject_id INT NOT NULL,
  year YEAR NOT NULL,
  semester TINYINT,
  exam_type VARCHAR(50),
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  uploaded_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  uploader_id INT DEFAULT NULL,
  FOREIGN KEY (department_id) REFERENCES department(department_id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES course(course_id) ON DELETE SET NULL,
  FOREIGN KEY (subject_id) REFERENCES subject(subject_id) ON DELETE CASCADE,
  FOREIGN KEY (uploader_id) REFERENCES uploader(uploader_id) ON DELETE SET NULL
);

-- JIS University departments
INSERT INTO department (department_code, department_name) VALUES
('ENG',  'Faculty of Engineering & Technology'),
('CSE',  'Computer Science & Engineering'),
('SCI',  'Faculty of Science'),
('PHARM','Faculty of Pharmacy / Pharmaceutical Technology'),
('LAW',  'Faculty of Juridical Sciences (Law)'),
('MGMT', 'Faculty of Management (BBA / MBA)'),
('AGRI', 'Faculty of Agriculture / Agricultural Sciences'),
('EDU',  'Faculty of Education'),
('HOSP', 'Faculty of Hospitality & Hotel Administration'),
('BIO',  'Faculty of Bio-Science / Life Sciences'),
('CHEM', 'Faculty of Chemistry'),
('JIASR','JIS Institute of Advanced Studies & Research'),
('MED',  'School of Medical Science & Research');

-- Admin users table for site administration
CREATE TABLE IF NOT EXISTS admin_user (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- To create an initial admin user, either:
-- 1) Use the provided `create_admin.php` script in the project root (recommended),
-- 2) Or generate a password hash in PHP: `<?php echo password_hash('yourpassword', PASSWORD_DEFAULT); ?>`
--    and insert using SQL: INSERT INTO admin_user (username, password_hash) VALUES ('admin', '<hash>');

-- Students table for read-only student accounts
CREATE TABLE IF NOT EXISTS student_user (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  full_name VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
