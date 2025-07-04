CREATE DATABASE IF NOT EXISTS autopilot_db;
USE autopilot_db;

-- Agencies
CREATE TABLE IF NOT EXISTS agencies (
  agency_id INT AUTO_INCREMENT PRIMARY KEY,
  agency_name VARCHAR(255) NOT NULL UNIQUE
);

-- Users 
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NULL DEFAULT NULL ,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS agency_admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  users INT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('adminagency') DEFAULT 'adminagency',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_admins_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS agency_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  user_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS internal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('internal') DEFAULT 'internal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS automations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  admin_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  api VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  type VARCHAR(100),
  config JSON,
  status VARCHAR(50) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_automations_admin FOREIGN KEY (admin_id) REFERENCES agency_admins(id) ON DELETE CASCADE
);

-- Automation-User Sharing (many-to-many)
CREATE TABLE IF NOT EXISTS automation_users (
  automation_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY (automation_id, user_id),
  CONSTRAINT fk_automation_users_automation FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
  CONSTRAINT fk_automation_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  user_id INT,
  admin_id INT,
  type VARCHAR(50), -- e.g. 'automation_run', 'client_update', etc.
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE CASCADE,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_log_admin FOREIGN KEY (admin_id) REFERENCES agency_admins(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS automation_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  automation_id INT NOT NULL,
  user_id INT,
  agency_id INT NOT NULL,
  status ENUM('success', 'failure') NOT NULL,
  error_type VARCHAR(100),
  time_saved_hours FLOAT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_run_automation FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
  CONSTRAINT fk_run_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_run_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE CASCADE
);
