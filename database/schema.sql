CREATE DATABASE IF NOT EXISTS autopilot_db;
USE autopilot_db;

-- 1. Agencies (no dependencies)
CREATE TABLE IF NOT EXISTS agencies (
  agency_id INT AUTO_INCREMENT PRIMARY KEY,
  agency_name VARCHAR(255) NOT NULL UNIQUE
);

-- 2. Users (depends on agencies)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NULL DEFAULT NULL ,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone_number VARCHAR(32) NULL,
  notes TEXT NULL,
  role ENUM('user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE SET NULL
);

-- 3. Agency Admins (depends on agencies)
CREATE TABLE IF NOT EXISTS agency_admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  agency_name VARCHAR(255) NOT NULL,
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

-- 4. Agency Users (depends on agencies, users)
CREATE TABLE IF NOT EXISTS agency_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  user_id INT NOT NULL
);

-- 5. Internal (no dependencies)
CREATE TABLE IF NOT EXISTS internal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('internal') DEFAULT 'internal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Automations (depends on agencies, agency_admins)
CREATE TABLE IF NOT EXISTS automations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  admin_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  api VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  automation_notes TEXT,
  type ENUM('Lead Gen','Scraper','Cold Email','Data Enrichment','CRM Sync','Social Media','Outreach','Form Filler','Email Verifier','Other') NOT NULL DEFAULT 'Other',
  pricing INT NOT NULL,
  pricing_model ENUM('one_time', 'monthly', 'per_run', 'free_trial', 'first_run_free') DEFAULT 'one_time',
  is_trial_available BOOLEAN DEFAULT FALSE,
  run_limit INT DEFAULT NULL,
  tags VARCHAR(255),
  scheduling ENUM('manual', 'daily', 'weekly', 'monthly') DEFAULT 'manual',
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_automations_admin FOREIGN KEY (admin_id) REFERENCES agency_admins(id) ON DELETE CASCADE
);

-- 7. Automation-User Sharing (depends on automations, users)
DROP TABLE IF EXISTS automation_users;
CREATE TABLE IF NOT EXISTS automation_users (
  automation_id INT NOT NULL,
  user_id INT NOT NULL,
  custom_pricing DECIMAL(10,2) NULL,
  custom_pricing_model VARCHAR(32) NULL,
  custom_schedule VARCHAR(32) NULL,
  active BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (automation_id, user_id),
  CONSTRAINT fk_automation_users_automation FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
  CONSTRAINT fk_automation_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Activity Log (depends on agencies, users, agency_admins)
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

-- 9. Automation Runs (depends on automations, users, agencies)
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

-- 10. Automation Errors (depends on automation_runs, automations, agencies, users)
CREATE TABLE IF NOT EXISTS automation_errors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  automation_run_id INT NOT NULL,
  automation_id INT NOT NULL,
  agency_id INT NOT NULL,
  user_id INT,
  error_type VARCHAR(100),
  error_message TEXT,
  error_details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_error_run FOREIGN KEY (automation_run_id) REFERENCES automation_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_error_automation FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
  CONSTRAINT fk_error_agency FOREIGN KEY (agency_id) REFERENCES agencies(agency_id) ON DELETE CASCADE,
  CONSTRAINT fk_error_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);




