-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.17.0.7270
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for guardreport_db
CREATE DATABASE IF NOT EXISTS `guardreport_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `guardreport_db`;

-- Dumping structure for table guardreport_db.activity_log
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `module` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_time` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.activity_log: ~9 rows (approximately)
REPLACE INTO `activity_log` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
	(1, 1, 'create', 'incidents', 'Incident #1 submitted: Umukriya yibye ipine', '::1', '2026-06-19 10:03:15'),
	(2, 2, 'create', 'users', 'User account created', '::1', '2026-06-19 10:09:49'),
	(3, 1, 'create', 'incidents', 'Incident #2 submitted: Short Accident in Parking', '::1', '2026-06-19 10:24:02'),
	(4, 1, 'status_change', 'incidents', 'Incident #2: open → resolved', '::1', '2026-06-19 10:24:20'),
	(5, 3, 'create', 'users', 'User account created', '::1', '2026-06-19 10:26:44'),
	(6, 4, 'create', 'users', 'User account created', '::1', '2026-06-19 10:27:45'),
	(7, 3, 'create', 'incidents', 'Incident #3 submitted: Fire Extinguisher Fall down', '::1', '2026-06-19 10:48:51'),
	(8, 1, 'settings_update', 'settings', 'Updated 14 system setting(s)', '::1', '2026-06-20 03:53:26'),
	(9, 1, 'report_export', 'reports', 'Exported incidents report (3 rows)', '::1', '2026-06-20 04:01:57'),
	(10, 4, 'profile_update', 'profile', 'Updated profile information', '::1', '2026-06-20 15:57:22');

-- Dumping structure for table guardreport_db.incident_evidence
CREATE TABLE IF NOT EXISTS `incident_evidence` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` int(10) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(80) NOT NULL,
  `file_size` int(10) unsigned NOT NULL DEFAULT 0,
  `uploaded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_incident` (`incident_id`),
  CONSTRAINT `incident_evidence_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incident_evidence_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.incident_evidence: ~2 rows (approximately)
REPLACE INTO `incident_evidence` (`id`, `incident_id`, `file_path`, `file_name`, `file_type`, `file_size`, `uploaded_by`, `created_at`) VALUES
	(1, 2, 'evidence/2/6518e88090b247d8_1781864642.pdf', 'Letter.pdf', 'application/pdf', 115567, 1, '2026-06-19 10:24:02'),
	(2, 3, 'evidence/3/6442a589d9511ba7_1781866131.jpg', 'fire.jpg', 'image/jpeg', 23077, 3, '2026-06-19 10:48:51');

-- Dumping structure for table guardreport_db.incident_types
CREATE TABLE IF NOT EXISTS `incident_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `default_severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `icon` varchar(80) DEFAULT 'ri-alert-line',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.incident_types: ~10 rows (approximately)
REPLACE INTO `incident_types` (`id`, `name`, `description`, `default_severity`, `icon`, `is_active`, `sort_order`, `created_at`) VALUES
	(1, 'Theft', 'Theft of property or assets', 'high', 'ri-shopping-bag-line', 1, 1, '2026-06-17 19:00:27'),
	(2, 'Vandalism', 'Deliberate damage to property', 'medium', 'ri-hammer-line', 1, 2, '2026-06-17 19:00:27'),
	(3, 'Trespassing', 'Unauthorised entry to restricted areas', 'medium', 'ri-door-open-line', 1, 3, '2026-06-17 19:00:27'),
	(4, 'Fire / Smoke', 'Fire alarm, smoke detected, or fire incident', 'critical', 'ri-fire-line', 1, 4, '2026-06-17 19:00:27'),
	(5, 'Medical Emergency', 'Injury, illness, or medical response needed', 'critical', 'ri-heart-pulse-line', 1, 5, '2026-06-17 19:00:27'),
	(6, 'Suspicious Person', 'Unidentified or suspicious individual on premises', 'high', 'ri-user-search-line', 1, 6, '2026-06-17 19:00:27'),
	(7, 'Assault', 'Physical altercation or assault on premises', 'critical', 'ri-boxing-line', 1, 7, '2026-06-17 19:00:27'),
	(8, 'Vehicle Incident', 'Accident, unauthorized vehicle, or parking issue', 'medium', 'ri-car-line', 1, 8, '2026-06-17 19:00:27'),
	(9, 'System Failure', 'CCTV, access control, or alarm system failure', 'high', 'ri-cpu-line', 1, 9, '2026-06-17 19:00:27'),
	(10, 'Other', 'Any incident not covered by above categories', 'low', 'ri-more-line', 1, 10, '2026-06-17 19:00:27');

-- Dumping structure for table guardreport_db.incident_updates
CREATE TABLE IF NOT EXISTS `incident_updates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_incident` (`incident_id`),
  CONSTRAINT `incident_updates_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incident_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.incident_updates: ~4 rows (approximately)
REPLACE INTO `incident_updates` (`id`, `incident_id`, `user_id`, `old_status`, `new_status`, `notes`, `created_at`) VALUES
	(1, 1, 1, NULL, 'open', 'Incident submitted', '2026-06-19 10:03:15'),
	(2, 2, 1, NULL, 'open', 'Incident submitted', '2026-06-19 10:24:02'),
	(3, 2, 1, 'open', 'resolved', '', '2026-06-19 10:24:20'),
	(4, 3, 3, NULL, 'open', 'Incident submitted', '2026-06-19 10:48:51');

-- Dumping structure for table guardreport_db.incidents
CREATE TABLE IF NOT EXISTS `incidents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  `reported_by` int(10) unsigned NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','reviewing','resolved','closed') NOT NULL DEFAULT 'open',
  `incident_date` datetime NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_reported` (`reported_by`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_date` (`incident_date`),
  CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `incident_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `incidents_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.incidents: ~3 rows (approximately)
REPLACE INTO `incidents` (`id`, `title`, `description`, `type_id`, `site_id`, `reported_by`, `severity`, `status`, `incident_date`, `latitude`, `longitude`, `location_note`, `created_at`, `updated_at`) VALUES
	(1, 'Umukriya yibye ipine', 'Yaritwaye yigize nkuwishyuye ariko ahita yirukanka', 1, 2, 1, 'high', 'open', '2026-06-19 12:01:00', -1.9463107, 30.0601837, 'Yirukankiye munce za Kacyiru', '2026-06-19 10:03:15', '2026-06-19 10:03:15'),
	(2, 'Short Accident in Parking', 'Umu cliya yagonze undi icyakora Police intervene and solved issue', 8, 3, 1, 'low', 'resolved', '2026-06-19 12:19:00', -1.5463107, 30.0601837, 'Conventional Center Parking ya Kabiri', '2026-06-19 10:24:02', '2026-06-19 10:24:20'),
	(3, 'Fire Extinguisher Fall down', 'There is a need to repair the Fire extinguisher hanger', 4, 1, 3, 'critical', 'open', '2026-06-19 12:45:00', -1.3463107, 30.0101837, 'Convention center', '2026-06-19 10:48:51', '2026-06-19 10:48:51');

-- Dumping structure for table guardreport_db.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(60) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_id` int(10) unsigned DEFAULT NULL,
  `related_type` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`user_id`,`is_read`),
  KEY `idx_time` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.notifications: ~0 rows (approximately)

-- Dumping structure for table guardreport_db.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.password_resets: ~1 rows (approximately)
REPLACE INTO `password_resets` (`id`, `email`, `otp`, `expires_at`, `used`, `created_at`) VALUES
	(2, 'info.abaremy@gmail.com', '796266', '2026-06-20 19:38:28', 1, '2026-06-20 17:23:28');

-- Dumping structure for table guardreport_db.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `module` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.permissions: ~29 rows (approximately)
REPLACE INTO `permissions` (`id`, `key`, `name`, `module`, `description`) VALUES
	(1, 'incidents.view', 'View incidents', 'Incidents', 'List and read incident records'),
	(2, 'incidents.create', 'Submit incidents', 'Incidents', 'Submit new incident reports'),
	(3, 'incidents.update', 'Edit incidents', 'Incidents', 'Edit existing incident details'),
	(4, 'incidents.delete', 'Delete incidents', 'Incidents', 'Permanently remove incident records'),
	(5, 'incidents.close', 'Close incidents', 'Incidents', 'Mark incidents as closed'),
	(6, 'evidence.upload', 'Upload evidence', 'Evidence', 'Attach files to an incident'),
	(7, 'evidence.delete', 'Delete evidence', 'Evidence', 'Remove evidence files'),
	(8, 'sites.view', 'View sites', 'Sites', 'List and read site records'),
	(9, 'sites.create', 'Create sites', 'Sites', 'Add new client site'),
	(10, 'sites.update', 'Edit sites', 'Sites', 'Modify site details'),
	(11, 'sites.delete', 'Delete sites', 'Sites', 'Remove a site'),
	(12, 'shifts.view', 'View shifts', 'Shifts', 'See shift schedules'),
	(13, 'shifts.create', 'Create shifts', 'Shifts', 'Schedule guard shifts'),
	(14, 'shifts.update', 'Edit shifts', 'Shifts', 'Modify shift details'),
	(15, 'shifts.delete', 'Delete shifts', 'Shifts', 'Cancel a shift'),
	(16, 'users.view', 'View users', 'Users', 'List and read user records'),
	(17, 'users.create', 'Create users', 'Users', 'Add new user accounts'),
	(18, 'users.update', 'Edit users', 'Users', 'Modify user details and roles'),
	(19, 'users.delete', 'Delete users', 'Users', 'Remove user accounts'),
	(20, 'users.deactivate', 'Suspend users', 'Users', 'Suspend or deactivate accounts'),
	(21, 'roles.view', 'View roles', 'Roles', 'List roles'),
	(22, 'roles.create', 'Create roles', 'Roles', 'Add new roles'),
	(23, 'roles.edit', 'Edit roles', 'Roles', 'Modify role details'),
	(24, 'roles.delete', 'Delete roles', 'Roles', 'Remove roles'),
	(25, 'roles.assign_permissions', 'Assign permissions', 'Roles', 'Change role permission sets'),
	(26, 'reports.view', 'View reports', 'Reports', 'Access analytics and summaries'),
	(27, 'reports.export', 'Export reports', 'Reports', 'Download PDF/Excel reports'),
	(28, 'settings.view', 'View settings', 'Settings', 'Read system configuration'),
	(29, 'settings.manage', 'Manage settings', 'Settings', 'Change system configuration');

-- Dumping structure for table guardreport_db.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.role_permissions: ~40 rows (approximately)
REPLACE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
	(2, 1),
	(2, 2),
	(2, 3),
	(2, 5),
	(2, 6),
	(2, 7),
	(2, 8),
	(2, 9),
	(2, 10),
	(2, 11),
	(2, 12),
	(2, 13),
	(2, 14),
	(2, 15),
	(2, 16),
	(2, 17),
	(2, 18),
	(2, 20),
	(2, 21),
	(2, 23),
	(2, 26),
	(2, 27),
	(2, 28),
	(2, 29),
	(3, 1),
	(3, 3),
	(3, 5),
	(3, 6),
	(3, 7),
	(3, 8),
	(3, 11),
	(3, 12),
	(3, 14),
	(3, 16),
	(3, 26),
	(3, 27),
	(4, 1),
	(4, 2),
	(4, 6),
	(4, 12);

-- Dumping structure for table guardreport_db.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.roles: ~4 rows (approximately)
REPLACE INTO `roles` (`id`, `name`, `description`, `is_system`, `created_at`) VALUES
	(1, 'Super Admin', 'Unrestricted access to all system features', 1, '2026-06-17 19:00:27'),
	(2, 'Administrator', 'Manages sites, guards, users, and reports', 1, '2026-06-17 19:00:27'),
	(3, 'Supervisor', 'Monitors incidents and manages field guards', 1, '2026-06-17 19:00:27'),
	(4, 'Guard', 'Submits incident reports and uploads evidence', 1, '2026-06-17 19:00:27');

-- Dumping structure for table guardreport_db.shifts
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guard_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('scheduled','active','completed','missed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_guard` (`guard_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_time` (`start_time`,`end_time`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`guard_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `shifts_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `shifts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.shifts: ~0 rows (approximately)
REPLACE INTO `shifts` (`id`, `guard_id`, `site_id`, `start_time`, `end_time`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 3, 3, '2026-06-19 18:00:00', '2026-06-20 18:00:00', 'active', 'Please follow this', 1, '2026-06-19 20:07:13', '2026-06-19 20:07:33');

-- Dumping structure for table guardreport_db.sites
CREATE TABLE IF NOT EXISTS `sites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `client_phone` varchar(30) DEFAULT NULL,
  `client_email` varchar(180) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','under_review') NOT NULL DEFAULT 'active',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.sites: ~3 rows (approximately)
REPLACE INTO `sites` (`id`, `name`, `address`, `city`, `client_name`, `client_phone`, `client_email`, `latitude`, `longitude`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 'Kigali Convention Centre', 'KG 2 Ave, Kigali', 'Kigali', 'Rwanda Convention Bureau', '+250788000001', NULL, NULL, NULL, NULL, 'active', 1, '2026-06-17 19:00:27', '2026-06-17 19:00:27'),
	(2, 'Simba Supermarket HQ', 'KN 5 Rd, Kacyiru', 'Kigali', 'Simba Group', '+250788000002', NULL, NULL, NULL, NULL, 'active', 1, '2026-06-17 19:00:27', '2026-06-17 19:00:27'),
	(3, 'Amahoro Stadium', 'KG 13 Ave, Remera', 'Kigali', 'City of Kigali', '+250788000003', NULL, NULL, NULL, NULL, 'active', 1, '2026-06-17 19:00:27', '2026-06-17 19:00:27');

-- Dumping structure for table guardreport_db.system_settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `label` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.system_settings: ~17 rows (approximately)
REPLACE INTO `system_settings` (`key`, `value`, `type`, `label`, `updated_at`) VALUES
	('allowed_file_ext', '["jpg","jpeg","png","gif","pdf","doc","docx"]', 'json', 'Allowed Upload Extensions', '2026-06-17 19:00:27'),
	('allowed_file_types', '["jpg","jpeg","png","pdf","mp4"]', 'json', 'Allowed File Extensions', '2026-06-20 03:53:26'),
	('app_name', 'GuardReport', 'string', 'Application Name', '2026-06-20 03:53:26'),
	('app_timezone', 'Africa/Kigali', 'string', 'Server Timezone', '2026-06-20 03:53:26'),
	('company_name', 'TopSec', 'string', 'Company / Agency Name', '2026-06-20 03:53:26'),
	('date_format', 'Y-m-d', 'string', 'Default Date Format', '2026-06-20 03:53:26'),
	('default_incident_severity', 'medium', 'string', 'Default Severity for New Incidents', '2026-06-20 03:53:26'),
	('force_password_change_days', '0', 'integer', 'Force Password Change Every (days, 0 = never)', '2026-06-20 03:53:26'),
	('incident_auto_close_days', '30', 'integer', 'Auto-close resolved incidents after N days', '2026-06-20 03:53:26'),
	('max_file_size', '10485760', 'integer', 'Max Upload Size (bytes)', '2026-06-17 19:00:27'),
	('max_upload_size_mb', '10', 'integer', 'Max Evidence File Size (MB)', '2026-06-20 03:53:26'),
	('notify_on_incident_submit', '1', 'boolean', 'Notify supervisors when a new incident is submitted', '2026-06-20 03:53:26'),
	('notify_on_shift_reminder', '1', 'boolean', 'Send shift reminder notifications', '2026-06-20 03:53:26'),
	('notify_on_submit', '1', 'boolean', 'Notify supervisors on new incident', '2026-06-17 19:00:27'),
	('password_min_length', '8', 'integer', 'Minimum Password Length', '2026-06-20 03:53:26'),
	('report_footer_text', 'Confidential — for internal use only', 'string', 'Report Footer / Confidentiality Note', '2026-06-20 03:53:26'),
	('session_timeout_minutes', '60', 'integer', 'Session Timeout (minutes)', '2026-06-20 03:53:26');

-- Dumping structure for table guardreport_db.user_notification_settings
CREATE TABLE IF NOT EXISTS `user_notification_settings` (
  `user_id` int(10) unsigned NOT NULL,
  `email_login` tinyint(1) NOT NULL DEFAULT 1,
  `email_incident_updates` tinyint(1) NOT NULL DEFAULT 1,
  `email_shift_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `push_new_incidents` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.user_notification_settings: ~0 rows (approximately)
REPLACE INTO `user_notification_settings` (`user_id`, `email_login`, `email_incident_updates`, `email_shift_reminders`, `push_new_incidents`, `updated_at`) VALUES
	(1, 1, 1, 1, 1, '2026-06-20 03:48:05');

-- Dumping structure for table guardreport_db.user_settings
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int(10) unsigned NOT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'en',
  `timezone` varchar(60) NOT NULL DEFAULT 'Africa/Kigali',
  `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.user_settings: ~0 rows (approximately)
REPLACE INTO `user_settings` (`user_id`, `language`, `timezone`, `date_format`, `created_at`, `updated_at`) VALUES
	(1, 'en', 'Africa/Kigali', 'Y-m-d', '2026-06-20 03:47:37', '2026-06-20 03:47:37');

-- Dumping structure for table guardreport_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(80) NOT NULL,
  `lastname` varchar(80) NOT NULL,
  `username` varchar(80) DEFAULT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_alt` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) unsigned NOT NULL DEFAULT 4,
  `account_status` enum('pending','active','inactive','suspended') NOT NULL DEFAULT 'pending',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `phone` (`phone`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table guardreport_db.users: ~4 rows (approximately)
REPLACE INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `phone`, `phone_alt`, `password`, `role_id`, `account_status`, `is_super_admin`, `photo`, `bio`, `gender`, `date_of_birth`, `address`, `otp_code`, `otp_expiry`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 'System', 'Admin', 'superadmin', 'admin@guardreport.rw', NULL, NULL, '$2y$10$cTKQFPz493I5.QQkU1MwzOW.YLOdQKqnHbWzpsnO13eI54jLUnCt6', 1, 'active', 1, 'users/05d39fa6f455_1.jpg', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-20 17:29:48', NULL, '2026-06-17 19:00:27', '2026-06-20 15:29:48'),
	(2, 'MUNANA', 'Issa', NULL, 'supervisor@guardrep.com', '25784666312', NULL, '$2y$10$t2iSTI5vKqaFzzVWSj7H2eDYtcmeLDhXv6htkfBcmPtw0zauB7zim', 3, 'active', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 12:40:47', 1, '2026-06-19 10:09:49', '2026-06-19 10:40:47'),
	(3, 'Adele', 'Mubano', NULL, 'adele@gmail.com', '07855544', NULL, '$2y$10$4oINP6UaGIJNbrWnuvq.u.ImRQSEMfClsj9irpbn32H2zuOC0NGPO', 4, 'active', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 12:45:36', 1, '2026-06-19 10:26:44', '2026-06-19 10:45:36'),
	(4, 'MUSIRIKARE', 'Fabrice', 'musirikare', 'info.abaremy@gmail.com', '0788998855', NULL, '$2y$10$Sd1VbnJAEwqFhK8T8JYz/eG1j7J0Evdun3VtWooVV/LRFD.K34LwO', 4, 'active', 0, 'users/2fbd4edfaedc_4.jpg', '', '', '1999-06-20', '', NULL, NULL, '2026-06-20 19:24:36', 1, '2026-06-19 10:27:45', '2026-06-20 17:24:36');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
