CREATE DATABASE IF NOT EXISTS autopilot_db;
USE autopilot_db;

-- Internal
CREATE TABLE IF NOT EXISTS internal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('internal') DEFAULT 'internal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Agencies (business units managed by internal)
CREATE TABLE IF NOT EXISTS agencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agency Admins
CREATE TABLE IF NOT EXISTS agency_admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('adminagency') DEFAULT 'adminagency',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

-- Users 
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

-- Automations 
CREATE TABLE IF NOT EXISTS automations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  config JSON,
  status VARCHAR(50) DEFAULT 'active',
  shared_with_user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
  FOREIGN KEY (shared_with_user_id) REFERENCES users(id)
);

-- Leads/Scraped Data
CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  user_id INT,
  automation_id INT,
  data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (automation_id) REFERENCES automations(id)
);

-- Support Requests
CREATE TABLE IF NOT EXISTS support_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  admin_id INT,
  user_id INT,
  subject VARCHAR(255),
  message TEXT,
  status VARCHAR(50) DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES agency_admins(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Portal Access Rules 
CREATE TABLE IF NOT EXISTS portal_access_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  user_id INT NOT NULL,
  automation_id INT,
  can_view BOOLEAN DEFAULT TRUE,
  can_edit BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (automation_id) REFERENCES automations(id)
);

-- Sessions for agency_admins
CREATE TABLE IF NOT EXISTS agency_admin_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  session_token VARCHAR(255) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES agency_admins(id)
);

-- Sessions for users
CREATE TABLE IF NOT EXISTS user_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_token VARCHAR(255) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_agency_admins_email ON agency_admins(email);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_automations_agency_id ON automations(agency_id);
CREATE INDEX idx_leads_agency_id ON leads(agency_id);
CREATE INDEX idx_support_requests_agency_id ON support_requests(agency_id);
CREATE INDEX idx_portal_access_rules_agency_id ON portal_access_rules(agency_id);
