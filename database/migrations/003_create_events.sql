CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(160) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `venue` VARCHAR(190) NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `poster_filename` VARCHAR(40) DEFAULT NULL,
  `status` ENUM('draft', 'published', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_status_starts_at` (`status`, `starts_at`),
  KEY `idx_events_created_by` (`created_by`),
  CONSTRAINT `fk_events_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;