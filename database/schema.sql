-- ─────────────────────────────────────────────────────────────────────────────
-- LearnPro DB Schema — pb_learnpro_db
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS `pb_learnpro_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pb_learnpro_db`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. user_roles
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_roles` (
    `role_id`   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(100)     NOT NULL,
    `level`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Higher = more privileged',
    `is_active` TINYINT(1)       NOT NULL DEFAULT 1,
    `is_delete` TINYINT(1)       NOT NULL DEFAULT 0,
    `createdAt` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updatedAt` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default roles
INSERT INTO `user_roles` (`role_name`, `level`) VALUES
  ('Super Admin', 100),
  ('Admin',       80),
  ('Manager',     50),
  ('Staff',       10);

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. user_permissions
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `permission_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)  NOT NULL UNIQUE COMMENT 'Machine key e.g. USER_VIEW',
    `display_name`  VARCHAR(150)  NOT NULL,
    `description`   TEXT,
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `createdAt`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updatedAt`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default permissions
INSERT INTO `user_permissions` (`name`, `display_name`, `description`) VALUES
  ('PROFILE_MANAGEMENT', 'Profile Management',  'Can view and edit own profile'),
  ('USER_VIEW',          'View Users',           'Can list and view user accounts'),
  ('USER_EDIT',          'Edit Users',           'Can update user accounts'),
  ('USER_DELETE',        'Delete Users',         'Can soft-delete user accounts'),
  ('ROLE_VIEW',          'View Roles',           'Can list and view roles'),
  ('ROLE_CREATE',        'Create Roles',         'Can create new roles'),
  ('ROLE_EDIT',          'Edit Roles',           'Can assign/revoke permissions on roles'),
  ('PERMISSION_VIEW',    'View Permissions',     'Can list permissions'),
  ('PERMISSION_CREATE',  'Create Permissions',   'Can create new permissions'),
  ('PERMISSION_EDIT',    'Edit Permissions',     'Can update permissions');

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. role_permissions  (pivot)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `createdAt`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updatedAt`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `user_roles`       (`role_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `user_permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Super Admin gets all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `permission_id` FROM `user_permissions`;

-- Admin gets most permissions (not role/permission management)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `permission_id` FROM `user_permissions`
WHERE `name` IN ('PROFILE_MANAGEMENT','USER_VIEW','USER_EDIT','USER_DELETE','ROLE_VIEW');

-- Staff gets profile only
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `permission_id` FROM `user_permissions`
WHERE `name` = 'PROFILE_MANAGEMENT';

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. user_mains
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_mains` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `email`          VARCHAR(255)  NOT NULL UNIQUE,
    `password`       VARCHAR(255)  NOT NULL DEFAULT 'EXTERNAL_AUTH',
    `service_number` VARCHAR(50)   DEFAULT NULL,
    `role_id`        INT UNSIGNED  NOT NULL DEFAULT 4,
    `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
    `is_delete`      TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    CONSTRAINT `fk_um_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
