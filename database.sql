-- AgencyOS MySQL Database Schema
-- Vor dem Import: Datenbank in phpMyAdmin links anklicken, dann importieren.

SET NAMES utf8;
SET CHARACTER SET utf8;
SET collation_connection = utf8_general_ci;

-- ============================================================
-- SESSIONS (ersetzt JWT)
-- ============================================================
CREATE TABLE `sessions` (
  `token`      VARCHAR(64)  NOT NULL,
  `user_id`    VARCHAR(36)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE `users` (
  `id`                   VARCHAR(36)  NOT NULL,
  `username`             VARCHAR(100) NOT NULL UNIQUE,
  `email`                VARCHAR(255) NOT NULL UNIQUE,
  `password_hash`        VARCHAR(255) NOT NULL,
  `first_name`           VARCHAR(100) DEFAULT '',
  `last_name`            VARCHAR(100) DEFAULT '',
  `role`                 ENUM('CHEF','ACCOUNT_MANAGER','EMPLOYEE','BUCHHALTUNG') NOT NULL DEFAULT 'EMPLOYEE',
  `is_active`            TINYINT(1)  DEFAULT 1,
  `profile_image`        VARCHAR(500) DEFAULT NULL,
  `color`                VARCHAR(20)  DEFAULT '#3B82F6',
  `position`             VARCHAR(200) DEFAULT '',
  `phone`                VARCHAR(50)  DEFAULT '',
  `birthday`             DATE         DEFAULT NULL,
  `weekly_hours`         DECIMAL(5,2) DEFAULT 40.00,
  `vacation_days`        INT          DEFAULT 28,
  `vacation_days_used`   INT          DEFAULT 0,
  `vacation_days_carry`  INT          DEFAULT 0,
  `start_date`           DATE         DEFAULT NULL,
  `last_login`           DATETIME     DEFAULT NULL,
  `last_active`          DATETIME     DEFAULT NULL,
  `notification_prefs`   JSON         DEFAULT NULL COMMENT 'notification preferences per category',
  `chat_theme`           VARCHAR(50)  DEFAULT 'default',
  `chat_settings`        JSON         DEFAULT NULL,
  `invitation_token`     VARCHAR(255) DEFAULT NULL,
  `invitation_expires`   DATETIME     DEFAULT NULL,
  `setup_completed`      TINYINT(1)  DEFAULT 0,
  `created_at`           DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CUSTOM MENU ITEMS
-- ============================================================
CREATE TABLE `custom_menu_items` (
  `id`         VARCHAR(36)  NOT NULL,
  `user_id`    VARCHAR(36)  NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `icon`       VARCHAR(100) DEFAULT NULL,
  `sort_order` INT          DEFAULT 0,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- TASK STATUSES
-- ============================================================
CREATE TABLE `task_statuses` (
  `id`          VARCHAR(36)  NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `color`       VARCHAR(20)  DEFAULT '#6B7280',
  `emoji`       VARCHAR(10)  DEFAULT NULL,
  `sort_order`  INT          DEFAULT 0,
  `is_default`  TINYINT(1)  DEFAULT 0,
  `is_done`     TINYINT(1)  DEFAULT 0,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CUSTOMERS
-- ============================================================
CREATE TABLE `customers` (
  `id`               VARCHAR(36)  NOT NULL,
  `name`             VARCHAR(255) NOT NULL,
  `company`          VARCHAR(255) DEFAULT '',
  `email`            VARCHAR(255) DEFAULT '',
  `phone`            VARCHAR(100) DEFAULT '',
  `website`          VARCHAR(500) DEFAULT '',
  `address`          TEXT         DEFAULT NULL,
  `notes`            TEXT         DEFAULT NULL,
  `logo_url`         VARCHAR(500) DEFAULT NULL,
  `color`            VARCHAR(20)  DEFAULT '#3B82F6',
  `status`           ENUM('active','archived') DEFAULT 'active',
  `account_manager`  VARCHAR(36)  DEFAULT NULL,
  `created_by`       VARCHAR(36)  DEFAULT NULL,
  `is_archived`      TINYINT(1)  DEFAULT 0,
  `archived_at`      DATETIME     DEFAULT NULL,
  `created_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_account_manager` (`account_manager`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CUSTOMER ACCESS (shared users)
-- ============================================================
CREATE TABLE `customer_access` (
  `customer_id` VARCHAR(36) NOT NULL,
  `user_id`     VARCHAR(36) NOT NULL,
  PRIMARY KEY (`customer_id`, `user_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- PUBLIC PAGE SETTINGS
-- ============================================================
CREATE TABLE `public_page_settings` (
  `id`                VARCHAR(36)  NOT NULL,
  `customer_id`       VARCHAR(36)  NOT NULL UNIQUE,
  `is_enabled`        TINYINT(1)  DEFAULT 0,
  `password`          VARCHAR(255) DEFAULT NULL,
  `title`             VARCHAR(255) DEFAULT NULL,
  `description`       TEXT         DEFAULT NULL,
  `expires_at`        DATETIME     DEFAULT NULL,
  `show_content_plan` TINYINT(1)  DEFAULT 0,
  `show_reports`      TINYINT(1)  DEFAULT 0,
  `created_at`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- PUBLIC PAGE LINKS
-- ============================================================
CREATE TABLE `public_page_links` (
  `id`          VARCHAR(36)  NOT NULL,
  `customer_id` VARCHAR(36)  NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `url`         VARCHAR(500) NOT NULL,
  `icon`        VARCHAR(100) DEFAULT NULL,
  `sort_order`  INT          DEFAULT 0,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CUSTOMER CREDENTIALS
-- ============================================================
CREATE TABLE `customer_credentials` (
  `id`          VARCHAR(36)  NOT NULL,
  `customer_id` VARCHAR(36)  NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `category`    VARCHAR(100) DEFAULT 'general',
  `username`    VARCHAR(500) DEFAULT '',
  `password`    VARCHAR(500) DEFAULT '',
  `url`         VARCHAR(500) DEFAULT '',
  `notes`       TEXT         DEFAULT NULL,
  `created_by`  VARCHAR(36)  DEFAULT NULL,
  `is_visible_to_all` TINYINT(1) DEFAULT 0,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `credential_visibility` (
  `credential_id` VARCHAR(36) NOT NULL,
  `user_id`       VARCHAR(36) NOT NULL,
  PRIMARY KEY (`credential_id`, `user_id`),
  FOREIGN KEY (`credential_id`) REFERENCES `customer_credentials`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CONTENT PLAN
-- ============================================================
CREATE TABLE `content_plan` (
  `id`          VARCHAR(36)  NOT NULL,
  `customer_id` VARCHAR(36)  NOT NULL,
  `title`       VARCHAR(500) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `platform`    VARCHAR(100) DEFAULT '',
  `status`      VARCHAR(50)  DEFAULT 'draft' COMMENT 'draft,in_review,approved,revision,published',
  `publish_date` DATE        DEFAULT NULL,
  `created_by`  VARCHAR(36)  DEFAULT NULL,
  `feedback`    TEXT         DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_publish_date` (`publish_date`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `content_plan_media` (
  `id`         VARCHAR(36)  NOT NULL,
  `post_id`    VARCHAR(36)  NOT NULL,
  `file_url`   VARCHAR(500) NOT NULL,
  `file_name`  VARCHAR(255) DEFAULT '',
  `file_type`  VARCHAR(100) DEFAULT '',
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_post_id` (`post_id`),
  FOREIGN KEY (`post_id`) REFERENCES `content_plan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `content_plan_pdfs` (
  `id`          VARCHAR(36)  NOT NULL,
  `customer_id` VARCHAR(36)  NOT NULL,
  `file_url`    VARCHAR(500) NOT NULL,
  `file_name`   VARCHAR(255) DEFAULT '',
  `month`       VARCHAR(7)   DEFAULT NULL COMMENT 'YYYY-MM',
  `created_by`  VARCHAR(36)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CUSTOMER REPORTS
-- ============================================================
CREATE TABLE `customer_reports` (
  `id`          VARCHAR(36)  NOT NULL,
  `customer_id` VARCHAR(36)  NOT NULL,
  `title`       VARCHAR(500) NOT NULL,
  `content`     LONGTEXT     DEFAULT NULL,
  `month`       VARCHAR(7)   DEFAULT NULL COMMENT 'YYYY-MM',
  `created_by`  VARCHAR(36)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- PROJECTS
-- ============================================================
CREATE TABLE `projects` (
  `id`              VARCHAR(36)  NOT NULL,
  `name`            VARCHAR(500) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `customer_id`     VARCHAR(36)  DEFAULT NULL,
  `status`          VARCHAR(50)  DEFAULT 'active' COMMENT 'active,completed,on_hold,archived',
  `deadline`        DATE         DEFAULT NULL,
  `budget`          DECIMAL(12,2) DEFAULT NULL,
  `color`           VARCHAR(20)  DEFAULT '#3B82F6',
  `created_by`      VARCHAR(36)  DEFAULT NULL,
  `is_archived`     TINYINT(1)  DEFAULT 0,
  `archived_at`     DATETIME     DEFAULT NULL,
  `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `project_members` (
  `project_id` VARCHAR(36) NOT NULL,
  `user_id`    VARCHAR(36) NOT NULL,
  PRIMARY KEY (`project_id`, `user_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `project_milestones` (
  `id`           VARCHAR(36)  NOT NULL,
  `project_id`   VARCHAR(36)  NOT NULL,
  `title`        VARCHAR(500) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `due_date`     DATE         DEFAULT NULL,
  `is_completed` TINYINT(1)  DEFAULT 0,
  `completed_at` DATETIME     DEFAULT NULL,
  `completed_by` VARCHAR(36)  DEFAULT NULL,
  `sort_order`   INT          DEFAULT 0,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `project_files` (
  `id`          VARCHAR(36)  NOT NULL,
  `project_id`  VARCHAR(36)  NOT NULL,
  `file_url`    VARCHAR(500) NOT NULL,
  `file_name`   VARCHAR(255) DEFAULT '',
  `file_size`   INT          DEFAULT 0,
  `file_type`   VARCHAR(100) DEFAULT '',
  `uploaded_by` VARCHAR(36)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- TASKS
-- ============================================================
CREATE TABLE `tasks` (
  `id`               VARCHAR(36)  NOT NULL,
  `title`            VARCHAR(1000) NOT NULL,
  `description`      LONGTEXT      DEFAULT NULL,
  `project_id`       VARCHAR(36)   DEFAULT NULL,
  `customer_id`      VARCHAR(36)   DEFAULT NULL,
  `status_id`        VARCHAR(36)   DEFAULT NULL,
  `priority`         ENUM('LOW','MEDIUM','HIGH') DEFAULT 'MEDIUM',
  `deadline`         DATETIME      DEFAULT NULL,
  `estimated_hours`  DECIMAL(6,2)  DEFAULT NULL,
  `assigned_to`      VARCHAR(36)   DEFAULT NULL,
  `account_manager`  VARCHAR(36)   DEFAULT NULL,
  `created_by`       VARCHAR(36)   DEFAULT NULL,
  `is_archived`      TINYINT(1)   DEFAULT 0,
  `archived_at`      DATETIME      DEFAULT NULL,
  `sort_order`       INT           DEFAULT 0,
  `is_recurring`     TINYINT(1)   DEFAULT 0,
  `recurring_config` JSON          DEFAULT NULL COMMENT '{interval: daily|weekly|biweekly|monthly}',
  `parent_task_id`   VARCHAR(36)   DEFAULT NULL COMMENT 'for recurring task chains',
  `tags`             JSON          DEFAULT NULL,
  `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_assigned_to` (`assigned_to`),
  INDEX `idx_status_id` (`status_id`),
  INDEX `idx_deadline` (`deadline`),
  INDEX `idx_is_archived` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `task_comments` (
  `id`         VARCHAR(36)   NOT NULL,
  `task_id`    VARCHAR(36)   NOT NULL,
  `user_id`    VARCHAR(36)   DEFAULT NULL,
  `content`    TEXT          NOT NULL,
  `created_at` DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_task_id` (`task_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `task_files` (
  `id`          VARCHAR(36)  NOT NULL,
  `task_id`     VARCHAR(36)  NOT NULL,
  `file_url`    VARCHAR(500) NOT NULL,
  `file_name`   VARCHAR(255) DEFAULT '',
  `file_size`   INT          DEFAULT 0,
  `file_type`   VARCHAR(100) DEFAULT '',
  `uploaded_by` VARCHAR(36)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_task_id` (`task_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- TIME ENTRIES
-- ============================================================
CREATE TABLE `time_entries` (
  `id`           VARCHAR(36)   NOT NULL,
  `user_id`      VARCHAR(36)   NOT NULL,
  `task_id`      VARCHAR(36)   DEFAULT NULL,
  `project_id`   VARCHAR(36)   DEFAULT NULL,
  `customer_id`  VARCHAR(36)   DEFAULT NULL,
  `description`  TEXT          DEFAULT NULL,
  `start_time`   DATETIME      NOT NULL,
  `end_time`     DATETIME      DEFAULT NULL,
  `duration`     INT           DEFAULT 0 COMMENT 'seconds',
  `pause_time`   INT           DEFAULT 0 COMMENT 'seconds',
  `is_billable`  TINYINT(1)   DEFAULT 1,
  `activity_type` VARCHAR(100) DEFAULT NULL,
  `is_locked`    TINYINT(1)   DEFAULT 0,
  `created_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_task_id` (`task_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_start_time` (`start_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CALENDAR EVENTS
-- ============================================================
CREATE TABLE `calendar_events` (
  `id`               VARCHAR(36)   NOT NULL,
  `user_id`          VARCHAR(36)   NOT NULL,
  `title`            VARCHAR(500)  NOT NULL,
  `description`      TEXT          DEFAULT NULL,
  `location`         VARCHAR(500)  DEFAULT NULL,
  `start_date`       DATETIME      NOT NULL,
  `end_date`         DATETIME      NOT NULL,
  `all_day`          TINYINT(1)   DEFAULT 0,
  `color`            VARCHAR(20)   DEFAULT '#3B82F6',
  `category`         VARCHAR(100)  DEFAULT 'general',
  `is_recurring`     TINYINT(1)   DEFAULT 0,
  `recurring_rule`   JSON          DEFAULT NULL COMMENT '{frequency: daily|weekly|monthly|yearly, interval: 1, end_date: null}',
  `external_id`      VARCHAR(500)  DEFAULT NULL COMMENT 'Google/iCloud event ID',
  `source`           VARCHAR(50)   DEFAULT 'internal',
  `vacation_id`      VARCHAR(36)   DEFAULT NULL,
  `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_end_date` (`end_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `event_participants` (
  `event_id` VARCHAR(36) NOT NULL,
  `user_id`  VARCHAR(36) NOT NULL,
  PRIMARY KEY (`event_id`, `user_id`),
  FOREIGN KEY (`event_id`) REFERENCES `calendar_events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user_calendar_settings` (
  `user_id`          VARCHAR(36)  NOT NULL,
  `google_connected` TINYINT(1)  DEFAULT 0,
  `google_tokens`    JSON         DEFAULT NULL,
  `icoud_connected`  TINYINT(1)  DEFAULT 0,
  `ical_calendars`   JSON         DEFAULT NULL COMMENT 'array of {name, url, color, enabled}',
  `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- VACATIONS
-- ============================================================
CREATE TABLE `vacations` (
  `id`            VARCHAR(36)  NOT NULL,
  `user_id`       VARCHAR(36)  NOT NULL,
  `start_date`    DATE         NOT NULL,
  `end_date`      DATE         NOT NULL,
  `days`          INT          DEFAULT 1,
  `status`        ENUM('pending','approved','rejected') DEFAULT 'pending',
  `reason`        TEXT         DEFAULT NULL,
  `approved_by`   VARCHAR(36)  DEFAULT NULL,
  `approved_at`   DATETIME     DEFAULT NULL,
  `rejected_at`   DATETIME     DEFAULT NULL,
  `reject_reason` TEXT         DEFAULT NULL,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_start_date` (`start_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE `notifications` (
  `id`          VARCHAR(36)   NOT NULL,
  `user_id`     VARCHAR(36)   NOT NULL,
  `type`        VARCHAR(100)  NOT NULL,
  `title`       VARCHAR(500)  NOT NULL,
  `message`     TEXT          DEFAULT NULL,
  `link`        VARCHAR(500)  DEFAULT NULL,
  `is_read`     TINYINT(1)   DEFAULT 0,
  `data`        JSON          DEFAULT NULL,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- ACHIEVEMENTS
-- ============================================================
CREATE TABLE `achievements` (
  `id`           VARCHAR(100) NOT NULL,
  `name`         VARCHAR(255) NOT NULL,
  `description`  TEXT         NOT NULL,
  `icon`         VARCHAR(10)  DEFAULT '',
  `category`     VARCHAR(100) DEFAULT 'general',
  `points`       INT          DEFAULT 0,
  `requirement`  INT          DEFAULT 1,
  `is_hidden`    TINYINT(1)  DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user_achievements` (
  `id`           VARCHAR(36)  NOT NULL,
  `user_id`      VARCHAR(36)  NOT NULL,
  `achievement_id` VARCHAR(100) NOT NULL,
  `earned_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_achievement` (`user_id`, `achievement_id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- GAMIFICATION / POINTS
-- ============================================================
CREATE TABLE `point_transactions` (
  `id`         VARCHAR(36)   NOT NULL,
  `user_id`    VARCHAR(36)   NOT NULL,
  `points`     INT           NOT NULL,
  `type`       VARCHAR(100)  NOT NULL,
  `reason`     VARCHAR(500)  DEFAULT '',
  `reward_id`  VARCHAR(36)   DEFAULT NULL,
  `created_by` VARCHAR(36)   DEFAULT NULL,
  `created_at` DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `rewards` (
  `id`             VARCHAR(36)   NOT NULL,
  `title`          VARCHAR(255)  NOT NULL,
  `description`    TEXT          DEFAULT NULL,
  `points_required` INT          NOT NULL DEFAULT 0,
  `icon`           VARCHAR(10)   DEFAULT '',
  `is_active`      TINYINT(1)   DEFAULT 1,
  `created_by`     VARCHAR(36)   DEFAULT NULL,
  `created_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `reward_redemptions` (
  `id`         VARCHAR(36)  NOT NULL,
  `user_id`    VARCHAR(36)  NOT NULL,
  `reward_id`  VARCHAR(36)  NOT NULL,
  `points`     INT          NOT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reward_id`) REFERENCES `rewards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- BENEFITS (Vorteilspass)
-- ============================================================
CREATE TABLE `benefits` (
  `id`          VARCHAR(36)   NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `discount`    VARCHAR(100)  DEFAULT '',
  `category`    VARCHAR(100)  DEFAULT 'general',
  `location`    VARCHAR(255)  DEFAULT '',
  `terms`       TEXT          DEFAULT NULL,
  `valid_from`  DATE          DEFAULT NULL,
  `valid_until` DATE          DEFAULT NULL,
  `logo_url`    VARCHAR(500)  DEFAULT NULL,
  `is_active`   TINYINT(1)   DEFAULT 1,
  `created_by`  VARCHAR(36)   DEFAULT NULL,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- CHAT
-- ============================================================
CREATE TABLE `conversations` (
  `id`          VARCHAR(36)  NOT NULL,
  `type`        ENUM('private','group','team') DEFAULT 'private',
  `name`        VARCHAR(255) DEFAULT NULL,
  `is_archived` TINYINT(1)  DEFAULT 0,
  `created_by`  VARCHAR(36)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `conversation_participants` (
  `conversation_id` VARCHAR(36) NOT NULL,
  `user_id`         VARCHAR(36) NOT NULL,
  `joined_at`       DATETIME    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`, `user_id`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `messages` (
  `id`              VARCHAR(36)   NOT NULL,
  `conversation_id` VARCHAR(36)   NOT NULL,
  `user_id`         VARCHAR(36)   DEFAULT NULL,
  `content`         LONGTEXT      DEFAULT NULL,
  `type`            VARCHAR(50)   DEFAULT 'text' COMMENT 'text,image,file,voice,system',
  `file_url`        VARCHAR(500)  DEFAULT NULL,
  `file_name`       VARCHAR(255)  DEFAULT NULL,
  `file_type`       VARCHAR(100)  DEFAULT NULL,
  `reply_to`        VARCHAR(36)   DEFAULT NULL,
  `is_edited`       TINYINT(1)   DEFAULT 0,
  `edited_at`       DATETIME      DEFAULT NULL,
  `is_deleted`      TINYINT(1)   DEFAULT 0,
  `deleted_at`      DATETIME      DEFAULT NULL,
  `created_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_conversation_id` (`conversation_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `message_reactions` (
  `id`         VARCHAR(36)  NOT NULL,
  `message_id` VARCHAR(36)  NOT NULL,
  `user_id`    VARCHAR(36)  NOT NULL,
  `emoji`      VARCHAR(20)  NOT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reaction` (`message_id`, `user_id`, `emoji`),
  INDEX `idx_message_id` (`message_id`),
  FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `message_reads` (
  `message_id`      VARCHAR(36) NOT NULL,
  `user_id`         VARCHAR(36) NOT NULL,
  `read_at`         DATETIME    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`, `user_id`),
  FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- NOTES
-- ============================================================
CREATE TABLE `notes` (
  `id`         VARCHAR(36)   NOT NULL,
  `user_id`    VARCHAR(36)   NOT NULL,
  `title`      VARCHAR(500)  DEFAULT '',
  `content`    LONGTEXT      DEFAULT NULL,
  `color`      VARCHAR(20)   DEFAULT '#FFFFFF',
  `is_pinned`  TINYINT(1)   DEFAULT 0,
  `created_at` DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- TOOLS (Agency tools with passwords)
-- ============================================================
CREATE TABLE `tools` (
  `id`          VARCHAR(36)   NOT NULL,
  `name`        VARCHAR(255)  NOT NULL,
  `url`         VARCHAR(500)  DEFAULT '',
  `username`    VARCHAR(500)  DEFAULT '',
  `password`    VARCHAR(500)  DEFAULT '',
  `notes`       TEXT          DEFAULT NULL,
  `category`    VARCHAR(100)  DEFAULT 'general',
  `created_by`  VARCHAR(36)   DEFAULT NULL,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- PERSONAL PASSWORDS
-- ============================================================
CREATE TABLE `personal_passwords` (
  `id`         VARCHAR(36)  NOT NULL,
  `user_id`    VARCHAR(36)  NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `username`   VARCHAR(500) DEFAULT '',
  `password`   VARCHAR(500) DEFAULT '',
  `url`        VARCHAR(500) DEFAULT '',
  `notes`      TEXT         DEFAULT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- QUESTIONNAIRE (Onboarding)
-- ============================================================
CREATE TABLE `questionnaires` (
  `id`               VARCHAR(36)  NOT NULL,
  `user_id`          VARCHAR(36)  NOT NULL UNIQUE,
  `branches`         JSON         DEFAULT NULL,
  `strengths`        JSON         DEFAULT NULL,
  `weaknesses`       JSON         DEFAULT NULL,
  `work_values`      JSON         DEFAULT NULL,
  `learning_goals`   JSON         DEFAULT NULL,
  `social_media`     JSON         DEFAULT NULL,
  `favorite_profiles` JSON        DEFAULT NULL,
  `hobbies`          JSON         DEFAULT NULL,
  `submitted_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- SHOOTING DOCUMENTATION
-- ============================================================
CREATE TABLE `shooting_docs` (
  `id`               VARCHAR(36)   NOT NULL,
  `user_id`          VARCHAR(36)   NOT NULL,
  `customer_id`      VARCHAR(36)   DEFAULT NULL,
  `title`            VARCHAR(500)  NOT NULL,
  `date`             DATE          DEFAULT NULL,
  `location`         VARCHAR(500)  DEFAULT '',
  `start_time`       TIME          DEFAULT NULL,
  `end_time`         TIME          DEFAULT NULL,
  `description`      TEXT          DEFAULT NULL,
  `team_members`     JSON          DEFAULT NULL,
  `social_contacts`  JSON          DEFAULT NULL,
  `status`           VARCHAR(50)   DEFAULT 'draft',
  `created_by`       VARCHAR(36)   DEFAULT NULL,
  `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- EXPENSE REPORTS
-- ============================================================
CREATE TABLE `expense_reports` (
  `id`              VARCHAR(36)   NOT NULL,
  `user_id`         VARCHAR(36)   NOT NULL,
  `title`           VARCHAR(500)  NOT NULL,
  `description`     TEXT          DEFAULT NULL,
  `amount`          DECIMAL(10,2) DEFAULT 0.00,
  `currency`        VARCHAR(10)   DEFAULT 'EUR',
  `category`        VARCHAR(100)  DEFAULT 'general',
  `receipt_url`     VARCHAR(500)  DEFAULT NULL,
  `date`            DATE          DEFAULT NULL,
  `status`          ENUM('submitted','approved','rejected') DEFAULT 'submitted',
  `admin_comment`   TEXT          DEFAULT NULL,
  `approved_by`     VARCHAR(36)   DEFAULT NULL,
  `approved_at`     DATETIME      DEFAULT NULL,
  `rejected_at`     DATETIME      DEFAULT NULL,
  `items`           JSON          DEFAULT NULL,
  `created_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
CREATE TABLE `system_settings` (
  `id`              VARCHAR(36)   NOT NULL DEFAULT 'default',
  `company_name`    VARCHAR(255)  DEFAULT 'AgencyOS',
  `company_logo`    VARCHAR(500)  DEFAULT NULL,
  `primary_color`   VARCHAR(20)   DEFAULT '#3B82F6',
  `secondary_color` VARCHAR(20)   DEFAULT '#1E40AF',
  `app_url`         VARCHAR(500)  DEFAULT NULL,
  `frontend_url`    VARCHAR(500)  DEFAULT NULL,
  `activity_types`  JSON          DEFAULT NULL,
  `permissions`     JSON          DEFAULT NULL COMMENT 'role-based permissions map',
  `google_client_id` VARCHAR(500) DEFAULT NULL,
  `google_client_secret` VARCHAR(500) DEFAULT NULL,
  `updated_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE `audit_logs` (
  `id`          VARCHAR(36)   NOT NULL,
  `user_id`     VARCHAR(36)   DEFAULT NULL,
  `user_name`   VARCHAR(255)  DEFAULT '',
  `action`      VARCHAR(100)  NOT NULL,
  `entity_type` VARCHAR(100)  DEFAULT '',
  `entity_id`   VARCHAR(36)   DEFAULT NULL,
  `details`     JSON          DEFAULT NULL,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_entity_type` (`entity_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default system settings
INSERT INTO `system_settings` (`id`, `company_name`, `primary_color`, `secondary_color`)
VALUES ('default', 'AgencyOS', '#3B82F6', '#1E40AF');

-- Default task statuses
INSERT INTO `task_statuses` (`id`, `name`, `color`, `emoji`, `sort_order`, `is_default`, `is_done`) VALUES
('status_todo',       'To Do',       '#6B7280', '📋', 0, 1, 0),
('status_inprogress', 'In Arbeit',   '#3B82F6', '⚡', 1, 0, 0),
('status_review',     'Im Review',   '#F59E0B', '👀', 2, 0, 0),
('status_done',       'Erledigt',    '#10B981', '✅', 3, 0, 1),
('status_blocked',    'Blockiert',   '#EF4444', '🚫', 4, 0, 0);

-- Achievements
INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `category`, `points`, `requirement`, `is_hidden`) VALUES
('first_task',       'Erste Aufgabe',       'Erste Aufgabe abgeschlossen',           '🎯', 'tasks',   10,  1,  0),
('task_machine',     'Aufgaben-Maschine',   '50 Aufgaben abgeschlossen',             '⚙️', 'tasks',   50,  50, 0),
('task_100',         'Centurion',           '100 Aufgaben abgeschlossen',            '💯', 'tasks',   100, 100,0),
('deadline_hero',    'Deadline-Hero',       '10 Aufgaben pünktlich abgeschlossen',   '⏰', 'tasks',   30,  10, 0),
('focus_pro',        'Focus Pro',           '5 High-Priority Aufgaben erledigt',     '🎯', 'tasks',   25,  5,  0),
('first_project',    'Projektstart',        'Erstes Projekt abgeschlossen',          '🚀', 'projects',20,  1,  0),
('milestone_master', 'Milestone Master',    '10 Meilensteine erreicht',              '🏁', 'projects',40,  10, 0),
('time_tracker',     'Zeiterfassung Pro',   '30 Zeiteinträge erstellt',              '⏱️', 'time',    30,  30, 0),
('overtime_king',    'Überstunden-König',   '100 Stunden Überstunden gesammelt',     '👑', 'time',    50,  100,0),
('vacation_planner', 'Urlaub-Planer',       'Erster Urlaub beantragt',               '🌴', 'vacation',15,  1,  0),
('communicator',     'Kommunikator',        '100 Chat-Nachrichten gesendet',         '💬', 'chat',    30,  100,0),
('motivator',        'Motivator',           '50 Reaktionen auf Nachrichten',         '🎉', 'chat',    25,  50, 0),
('early_bird',       'Frühaufsteher',       'An 5 Tagen vor 8 Uhr eingeloggt',       '🌅', 'special', 20,  5,  1),
('marathon_runner',  'Marathon-Läufer',     'Einen 8+ Stunden-Tag protokolliert',    '🏃', 'time',    20,  1,  1),
('silent_hero',      'Stiller Held',        '20 Aufgaben ohne Kommentar erledigt',   '🦸', 'tasks',   15,  20, 1),
('team_player',      'Teamplayer',          'An 5 gemeinsamen Projekten gearbeitet', '🤝', 'team',    35,  5,  0),
('level_5',          'Level 5',             'Level 5 erreicht',                      '⭐', 'levels',  50,  500,0),
('level_10',         'Level 10',            'Level 10 erreicht',                     '🌟', 'levels',  100, 1000,0);

-- Admin user (password: admin123 - CHANGE THIS!)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `color`, `weekly_hours`, `vacation_days`, `setup_completed`) VALUES
('usr_admin_001', 'admin', 'admin@agencyos.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'CHEF', 1, '#3B82F6', 40.00, 28, 1);
-- Note: Default password is 'password' - change immediately after first login!
