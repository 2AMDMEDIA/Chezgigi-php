-- Schéma MySQL pour ChezGigi - Planning de réservations
-- Compatible MySQL 5.7+ / MariaDB 10.2+

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ----------------------------------------------------
-- Table des sources iCal (Airbnb, Booking, etc.)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ical_source` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `url` TEXT NOT NULL,
  `last_sync_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- Table des réservations
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guest_name` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `source` VARCHAR(50) NOT NULL DEFAULT 'MANUAL',
  `ical_uid` VARCHAR(255) NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `price` DECIMAL(10,2) NULL DEFAULT NULL,
  `is_menage` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ical_uid_unique` (`ical_uid`),
  KEY `start_date_idx` (`start_date`),
  KEY `end_date_idx` (`end_date`),
  KEY `source_idx` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
