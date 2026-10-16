CREATE TABLE IF NOT EXISTS `event_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `default_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_sections_name` (`event_id`, `name`),
  UNIQUE KEY `uq_event_sections_id_event_id` (`id`, `event_id`),
  CONSTRAINT `fk_event_sections_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_rows` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `section_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(40) NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_rows_section_label` (`section_id`, `label`),
  UNIQUE KEY `uq_event_rows_id_event_id` (`id`, `event_id`),
  CONSTRAINT `fk_event_rows_section`
    FOREIGN KEY (`section_id`, `event_id`) REFERENCES `event_sections` (`id`, `event_id`) ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_seats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `row_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(40) NOT NULL,
  `x_position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `y_position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `price_override` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('available', 'blocked', 'locked', 'reserved', 'sold') NOT NULL DEFAULT 'available',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_seats_event_label` (`event_id`, `label`),
  KEY `idx_event_seats_status` (`row_id`, `status`),
  CONSTRAINT `fk_event_seats_row`
    FOREIGN KEY (`row_id`, `event_id`) REFERENCES `event_rows` (`id`, `event_id`) ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
