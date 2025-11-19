/**************************************************************
 * Yantra - Optimized CREATE TABLE migration
 * - All tables defined with IF NOT EXISTS
 * - InnoDB, utf8mb4, ROW_FORMAT=DYNAMIC
 * - FK constraints, indexes, UNIQUE keys for upsert-friendly ops
 **************************************************************/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1) Users core
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(64) NOT NULL,
  `user_email` VARCHAR(191) NOT NULL,
  `user_mobile` VARCHAR(32) DEFAULT NULL,
  `user_pass` VARCHAR(255) NOT NULL,            -- store password_hash()
  `nickname` VARCHAR(64) DEFAULT NULL,
  `display_name` VARCHAR(128) DEFAULT NULL,
  `full_name` VARCHAR(191) DEFAULT NULL,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_user_email` (`user_email`),
  UNIQUE KEY `uq_user_name` (`user_name`),
  INDEX `idx_user_mobile` (`user_mobile`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2) User meta (one key per user enforced by UNIQUE)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_user_meta` (
  `meta_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `meta_key` VARCHAR(128) NOT NULL,
  `meta_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `uq_user_meta` (`user_id`, `meta_key`),
  INDEX `idx_user_meta_user` (`user_id`),
  CONSTRAINT `fk_user_meta_user` FOREIGN KEY (`user_id`) REFERENCES `yt_users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3) Generic meta (meta for any record type)
--    UNIQUE(meta_type, record_id, meta_key)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_meta` (
  `meta_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `meta_type` VARCHAR(16) NOT NULL,         -- slightly larger than 8 for flexibility
  `record_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` VARCHAR(128) NOT NULL,
  `meta_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `uq_meta_type_record_key` (`meta_type`, `record_id`, `meta_key`),
  INDEX `idx_meta_record` (`record_id`),
  INDEX `idx_meta_type` (`meta_type`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4) Options (key/value) with scoping (level + scope_id)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_options` (
  `op_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `op_key` VARCHAR(191) NOT NULL,
  `op_value` TEXT DEFAULT NULL,
  `level` VARCHAR(64) NOT NULL DEFAULT 'global',  -- global, site, user, tenant, etc.
  `scope_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `autoload` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`op_id`),
  UNIQUE KEY `uq_op_key_level_scope` (`op_key`, `level`, `scope_id`),
  INDEX `idx_op_level_scope` (`level`, `scope_id`),
  INDEX `idx_op_key` (`op_key`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5) Admin users & roles (backend)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_admins` (
  `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(191) DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_username` (`username`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_roles` (
  `role_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_role_slug` (`slug`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_permissions` (
  `perm_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`perm_id`),
  UNIQUE KEY `uq_perm_slug` (`slug`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_admin_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_role` (`admin_id`, `role_id`),
  INDEX `idx_admin_roles_admin` (`admin_id`),
  CONSTRAINT `fk_admin_roles_admin` FOREIGN KEY (`admin_id`) REFERENCES `yt_admins`(`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_admin_roles_role` FOREIGN KEY (`role_id`) REFERENCES `yt_roles`(`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_role_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `perm_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`role_id`, `perm_id`),
  CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `yt_roles`(`role_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_perm_perm` FOREIGN KEY (`perm_id`) REFERENCES `yt_permissions`(`perm_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_admin_meta` (
  `meta_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `meta_key` VARCHAR(128) NOT NULL,
  `meta_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `uq_admin_meta` (`admin_id`, `meta_key`),
  INDEX `idx_admin_meta_admin` (`admin_id`),
  CONSTRAINT `fk_admin_meta_admin` FOREIGN KEY (`admin_id`) REFERENCES `yt_admins`(`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6) Admin activity log (audit)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_activity_log` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NULL,
  `action` VARCHAR(191) NOT NULL,
  `target_type` VARCHAR(64) DEFAULT NULL,
  `target_id` INT UNSIGNED DEFAULT NULL,
  `meta` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  INDEX `idx_activity_admin` (`admin_id`),
  CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `yt_admins`(`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7) User roles / permissions (frontend)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_user_roles` (
  `role_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_user_role_slug` (`slug`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_user_permissions` (
  `perm_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`perm_id`),
  UNIQUE KEY `uq_user_perm_slug` (`slug`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_user_role_map` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role_map` (`user_id`, `role_id`),
  INDEX `idx_user_role_user` (`user_id`),
  INDEX `idx_user_role_role` (`role_id`),
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `yt_users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) REFERENCES `yt_user_roles`(`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_user_role_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `perm_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role_perm` (`role_id`, `perm_id`),
  CONSTRAINT `fk_user_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `yt_user_roles`(`role_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_perm_perm` FOREIGN KEY (`perm_id`) REFERENCES `yt_user_permissions`(`perm_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8) Posts / Pages / Content
--    - FULLTEXT indexes for search
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_posts` (
  `post_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_type` VARCHAR(64) NOT NULL DEFAULT 'post',   -- 'post','page','product', etc.
  `post_title` VARCHAR(255) NOT NULL,
  `post_slug` VARCHAR(191) NOT NULL,
  `post_excerpt` TEXT DEFAULT NULL,
  `post_content` LONGTEXT DEFAULT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('draft','published','private','trash') NOT NULL DEFAULT 'draft',
  `comment_status` TINYINT(1) NOT NULL DEFAULT 1,
  `published_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `uq_post_slug` (`post_slug`),
  INDEX `idx_post_type_status` (`post_type`, `status`),
  INDEX `idx_posts_author` (`author_id`),
  FULLTEXT KEY `ft_post_title_content` (`post_title`, `post_content`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_pages` (
  `page_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('draft','published','private','trash') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `uq_page_slug` (`slug`),
  INDEX `idx_pages_author` (`author_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Ensure foreign key constraints between yt_posts/yt_pages → yt_users
-- ------------------------------------------------------------

-- Drop existing FK if it already exists (safe for repeated migrations)
ALTER TABLE `yt_posts`
  DROP FOREIGN KEY IF EXISTS `fk_posts_author`;

ALTER TABLE `yt_pages`
  DROP FOREIGN KEY IF EXISTS `fk_pages_author`;

-- Now re-add (fresh) the correct constraints
ALTER TABLE `yt_posts`
  ADD CONSTRAINT `fk_posts_author`
  FOREIGN KEY (`author_id`)
  REFERENCES `yt_users`(`user_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `yt_pages`
  ADD CONSTRAINT `fk_pages_author`
  FOREIGN KEY (`author_id`)
  REFERENCES `yt_users`(`user_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;


-- ------------------------------------------------------------
-- 9) Terms / Taxonomy and post-terms mapping
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_terms` (
  `term_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `taxonomy` VARCHAR(64) NOT NULL DEFAULT 'category', -- category, tag, custom
  `parent` INT UNSIGNED DEFAULT 0,
  `count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `uq_term_slug_tax` (`slug`, `taxonomy`),
  INDEX `idx_taxonomy` (`taxonomy`),
  INDEX `idx_parent` (`parent`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_post_terms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `term_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_term` (`post_id`, `term_id`),
  INDEX `idx_pt_post` (`post_id`),
  INDEX `idx_pt_term` (`term_id`),
  CONSTRAINT `fk_pt_post` FOREIGN KEY (`post_id`) REFERENCES `yt_posts`(`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pt_term` FOREIGN KEY (`term_id`) REFERENCES `yt_terms`(`term_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10) Media / Uploads
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_media` (
  `media_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(512) NOT NULL,         -- store web path or storage path (CDN)
  `mime_type` VARCHAR(128) DEFAULT NULL,
  `file_size` BIGINT UNSIGNED DEFAULT 0,
  `uploaded_by` INT UNSIGNED DEFAULT NULL,
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `caption` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`media_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `yt_users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11) Comments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_comments` (
  `comment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `author_name` VARCHAR(191) DEFAULT NULL,
  `author_email` VARCHAR(191) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  INDEX `idx_comment_post` (`post_id`),
  INDEX `idx_comment_parent` (`parent_id`),
  INDEX `idx_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `yt_posts`(`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `yt_users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12) Site menus
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_site_menus` (
  `menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(191) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `uq_menu_slug` (`slug`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yt_site_menu_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(191) NOT NULL,
  `url` VARCHAR(512) NOT NULL,
  `target` VARCHAR(32) NOT NULL DEFAULT '_self',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  INDEX `idx_menu_items_menu` (`menu_id`),
  CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `yt_site_menus`(`menu_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13) Redirects
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_redirects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(1024) NOT NULL,
  `target` VARCHAR(1024) NOT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redirect_source` (`source`(255))
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14) Cron / scheduled tasks
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yt_cron` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `hook` VARCHAR(191) NOT NULL,
  `payload` JSON DEFAULT NULL,
  `schedule_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('pending','running','done','failed') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cron_schedule` (`schedule_at`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15) Analytics star-schema tables (recommended for OLAP / reporting)
--     Keep in a separate database if possible. Example optimized columns.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dim_time` (
  `time_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dt` DATE NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `quarter` TINYINT UNSIGNED NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `day` TINYINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL,
  `hour` TINYINT UNSIGNED NOT NULL,
  `minute` TINYINT UNSIGNED NOT NULL,
  `second` TINYINT UNSIGNED NOT NULL,
  `is_weekend` TINYINT(1) NOT NULL DEFAULT 0,
  `dt_timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`time_id`),
  UNIQUE KEY `uq_time_dt_ts` (`dt_timestamp`),
  INDEX `idx_dt` (`dt`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dim_user` (
  `user_sk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL, -- original user id
  `user_name` VARCHAR(64) NULL,
  `email_domain` VARCHAR(128) NULL,
  `signup_date` DATE NULL,
  `user_role` VARCHAR(64) NULL,
  `country_code` CHAR(2) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `attributes` JSON DEFAULT NULL,
  PRIMARY KEY (`user_sk`),
  UNIQUE KEY `uq_dim_user_userid` (`user_id`),
  INDEX `idx_user_role` (`user_role`),
  INDEX `idx_country` (`country_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dim_content` (
  `content_sk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` INT UNSIGNED NULL,
  `content_type` VARCHAR(64) NULL,
  `slug` VARCHAR(255) NULL,
  `title` VARCHAR(255) NULL,
  `author_id` INT UNSIGNED NULL,
  `category` VARCHAR(128) NULL,
  `tags` JSON DEFAULT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`content_sk`),
  UNIQUE KEY `uq_content_id_type` (`content_id`, `content_type`),
  INDEX `idx_content_type` (`content_type`),
  INDEX `idx_author` (`author_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dim_device` (
  `device_sk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_family` VARCHAR(128) NULL,
  `os_family` VARCHAR(64) NULL,
  `browser_family` VARCHAR(64) NULL,
  `is_mobile` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`device_sk`),
  UNIQUE KEY `uq_device` (`device_family`, `os_family`, `browser_family`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dim_geo` (
  `geo_sk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `country` CHAR(2) NULL,
  `region` VARCHAR(128) NULL,
  `city` VARCHAR(128) NULL,
  `latitude` DECIMAL(9,6) NULL,
  `longitude` DECIMAL(9,6) NULL,
  PRIMARY KEY (`geo_sk`),
  INDEX `idx_country` (`country`),
  INDEX `idx_region` (`region`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dim_campaign` (
  `campaign_sk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(128) NULL,
  `medium` VARCHAR(128) NULL,
  `campaign` VARCHAR(191) NULL,
  `term` VARCHAR(191) NULL,
  `content` VARCHAR(191) NULL,
  PRIMARY KEY (`campaign_sk`),
  UNIQUE KEY `uq_campaign_full` (`source`, `medium`, `campaign`, `term`, `content`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fact_event (recommended partitioning by month/day depending on size)
CREATE TABLE IF NOT EXISTS `fact_event` (
  `event_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_time_id` INT UNSIGNED NOT NULL,
  `event_dt` DATE NOT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `user_sk` INT UNSIGNED NULL,
  `content_sk` INT UNSIGNED NULL,
  `device_sk` INT UNSIGNED NULL,
  `geo_sk` INT UNSIGNED NULL,
  `campaign_sk` INT UNSIGNED NULL,
  `session_id` VARCHAR(128) NULL,
  `page_url` VARCHAR(1024) NULL,
  `referrer` VARCHAR(1024) NULL,
  `duration_seconds` INT UNSIGNED DEFAULT 0,
  `revenue` DECIMAL(12,4) DEFAULT 0,
  `value` DOUBLE DEFAULT NULL,
  `properties` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  INDEX `idx_event_time` (`event_time_id`),
  INDEX `idx_event_dt_type` (`event_dt`, `event_type`),
  INDEX `idx_user_sk` (`user_sk`),
  INDEX `idx_content_sk` (`content_sk`),
  INDEX `idx_session` (`session_id`),
  INDEX `idx_campaign` (`campaign_sk`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fact_order (if using e-commerce; partition by month recommended)
CREATE TABLE IF NOT EXISTS `fact_order` (
  `order_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_dt` DATE NOT NULL,
  `order_time_id` INT UNSIGNED NOT NULL,
  `user_sk` INT UNSIGNED NULL,
  `total_amount` DECIMAL(14,4) NOT NULL DEFAULT 0,
  `currency` CHAR(3) DEFAULT 'USD',
  `items_count` INT UNSIGNED DEFAULT 0,
  `payment_method` VARCHAR(64) NULL,
  `order_status` VARCHAR(64) NULL,
  `properties` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  INDEX `idx_order_dt` (`order_dt`),
  INDEX `idx_user_sk_order` (`user_sk`),
  INDEX `idx_order_status` (`order_status`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `yt_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(100) NOT NULL,
  `payload` JSON NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `available_at` INT NOT NULL,
  `created_at` INT NOT NULL,
  `last_error` TEXT NULL,
  INDEX (`queue`),
  INDEX (`available_at`)
);
CREATE TABLE IF NOT EXISTS `yt_batches` (
  `batch_id` VARCHAR(64) NOT NULL PRIMARY KEY,
  `total_jobs` INT NOT NULL DEFAULT 0,
  `pending` INT NOT NULL DEFAULT 0,
  `failed` INT NOT NULL DEFAULT 0,
  `status` ENUM('pending','running','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `payload` JSON NULL,
  `then_job` TEXT NULL,        -- optional job to run when batch completes (Class@method or class)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL,
  INDEX (`status`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE yt_batch_failed (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  job_id INT NOT NULL,
  job_name VARCHAR(255),
  payload JSON,
  error_message TEXT,
  error_trace LONGTEXT,
  failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Mail queue: storing outgoing emails to be sent by worker
CREATE TABLE IF NOT EXISTS `yt_mail_queue` (
  `mail_id` INT AUTO_INCREMENT PRIMARY KEY,
  `to_email` VARCHAR(255) NOT NULL,
  `to_name` VARCHAR(255) DEFAULT NULL,
  `from_email` VARCHAR(255) DEFAULT NULL,
  `from_name` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` LONGTEXT,
  `body_text` LONGTEXT,
  `headers` TEXT,
  `attempts` INT DEFAULT 0,
  `max_attempts` INT DEFAULT 5,
  `queue` VARCHAR(50) DEFAULT 'default',
  `status` ENUM('pending','sending','sent','failed') DEFAULT 'pending',
  `last_error` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App notifications: user-visible notifications (in-app)
CREATE TABLE IF NOT EXISTS `yt_notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(100) NOT NULL, -- e.g., 'comment', 'system', 'invoice'
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `meta` JSON DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `delivered_via_email` TINYINT(1) DEFAULT 0,
  `delivered_via_push` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE yt_mail_queue
  ADD COLUMN attachments JSON DEFAULT NULL AFTER headers;

-- Abuse reports (human-readable audit)
CREATE TABLE IF NOT EXISTS `yt_abuse_reports` (
  `report_id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL, -- user_id, api_key, or ip:prefix (e.g., "ip:1.2.3.4")
  `type` VARCHAR(100) NOT NULL,       -- e.g., 'rate_limit', 'spam', 'login_bruteforce'
  `meta` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blocks / temporary bans
CREATE TABLE IF NOT EXISTS `yt_abuse_blocks` (
  `block_id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL, -- same format as above
  `reason` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME DEFAULT NULL,  -- NULL => permanent block
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional whitelist (admins/system)
CREATE TABLE IF NOT EXISTS `yt_abuse_whitelist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL UNIQUE, -- user_id, api_key, or ip
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `yt_scheduled_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `expression` VARCHAR(64) NOT NULL,
  `command` TEXT NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `last_run_at` DATETIME NULL,
  `next_run_at` DATETIME NULL,
  `attempts` INT DEFAULT 0,
  `meta` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Restore FK checks
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- End of optimized migration script
-- ====================================================================
