-- =============================================================================
--  LearnPro DB Schema — pb_learnpro_db
--  Generated: 2026-04-09
--  FIXED VERSION
--  Includes: user_roles, user_permissions, role_permissions,
--            user_mains, user_details
--            + dummy data (2 users per role = 6 total)
-- =============================================================================
CREATE DATABASE IF NOT EXISTS `pb_learnpro_db` CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `pb_learnpro_db`;

-- =============================================================================
-- 1. user_roles
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `user_roles` (
        `role_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `role_name` VARCHAR(100) NOT NULL,
        `level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Higher = more privileged',
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`role_id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `user_roles` (`role_name`, `level`)
VALUES
    ('Admin', 100),
    ('Trainer', 80),
    ('Trainee', 50);

-- =============================================================================
-- 2. user_permissions
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `user_permissions` (
        `permission_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Machine key e.g. USER_VIEW',
        `display_name` VARCHAR(150) NOT NULL,
        `description` TEXT,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`permission_id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `user_permissions` (
        `permission_id`,
        `name`,
        `display_name`,
        `description`,
        `is_active`,
        `createdAt`,
        `updatedAt`
    )
VALUES
    (
        1,
        'PROFILE_MANAGEMENT',
        'Profile Management',
        'Can view and edit own profile',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        2,
        'USER_VIEW',
        'View Users',
        'Can list and view user accounts',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        3,
        'USER_EDIT',
        'Edit Users',
        'Can update user accounts',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        4,
        'USER_DELETE',
        'Delete Users',
        'Can soft-delete user accounts',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        5,
        'ROLE_VIEW',
        'View Roles',
        'Can list and view roles',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        6,
        'ROLE_CREATE',
        'Create Roles',
        'Can create new roles',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        7,
        'ROLE_EDIT',
        'Edit Roles',
        'Can assign/revoke permissions on roles',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        8,
        'PERMISSION_VIEW',
        'View Permissions',
        'Can list permissions',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        9,
        'PERMISSION_CREATE',
        'Create Permissions',
        'Can create new permissions',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        10,
        'PERMISSION_EDIT',
        'Edit Permissions',
        'Can update permissions',
        1,
        '2026-04-15 14:33:17',
        '2026-04-15 14:33:17'
    ),
    (
        11,
        'USER_CREATE',
        'User Management',
        NULL,
        1,
        '2026-04-17 10:18:17',
        '2026-04-17 10:18:17'
    ),
    (
        12,
        'TRAINING_CATEGORY_VIEW',
        '',
        NULL,
        1,
        '2026-04-20 09:21:38',
        '2026-04-20 09:21:38'
    ),
    (
        13,
        'TRAINING_CATEGORY_CREATE',
        '',
        NULL,
        1,
        '2026-04-20 09:22:20',
        '2026-04-20 09:22:20'
    ),
    (
        14,
        'TRAINING_CATEGORY_EDIT',
        '',
        NULL,
        1,
        '2026-04-20 09:22:20',
        '2026-04-20 09:22:20'
    ),
    (
        15,
        'TRAINING_CATEGORY_DELETE',
        '',
        NULL,
        1,
        '2026-04-20 10:56:11',
        '2026-04-20 10:56:11'
    ),
    (
        16,
        'TRAINING_SESSION_VIEW',
        '',
        NULL,
        1,
        '2026-04-20 15:56:59',
        '2026-04-20 15:56:59'
    ),
    (
        17,
        'TRAINING_SESSION_CREATE',
        '',
        NULL,
        1,
        '2026-04-20 15:56:59',
        '2026-04-20 15:56:59'
    ),
    (
        18,
        'TRAINING_SESSION_EDIT',
        '',
        NULL,
        1,
        '2026-04-20 15:57:46',
        '2026-04-20 15:57:46'
    ),
    (
        19,
        'TRAINING_SESSION_DELETE',
        '',
        NULL,
        1,
        '2026-04-20 15:57:46',
        '2026-04-20 15:57:46'
    ),
    (
        20,
        'TRAINING_ALLOCATION_VIEW',
        'View Training Allocations',
        'Allows viewing of training allocation records',
        1,
        '2026-04-21 09:06:19',
        '2026-04-21 09:06:19'
    ),
    (
        21,
        'TRAINING_ALLOCATION_CREATE',
        'Create Training Allocation',
        'Allows creating new training allocations',
        1,
        '2026-04-21 09:06:19',
        '2026-04-21 09:06:19'
    ),
    (
        22,
        'TRAINING_ALLOCATION_EDIT',
        'Edit Training Allocation',
        'Allows updating existing training allocations',
        1,
        '2026-04-21 09:06:19',
        '2026-04-21 09:06:19'
    ),
    (
        23,
        'TRAINING_ALLOCATION_DELETE',
        'Delete Training Allocation',
        'Allows soft deleting training allocations',
        1,
        '2026-04-21 09:06:19',
        '2026-04-21 09:06:19'
    ),
    (
        24,
        'PROFILE_VIEW',
        'View Profile',
        'Allows user to view their profile',
        1,
        '2026-04-21 13:58:56',
        '2026-04-21 13:58:56'
    ),
    (
        25,
        'PROFILE_EDIT',
        'Edit Profile',
        'Allows user to update their profile',
        1,
        '2026-04-21 13:58:56',
        '2026-04-21 13:58:56'
    );

-- =============================================================================
-- 3. role_permissions  (pivot)
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `role_permissions` (
        `role_id` INT UNSIGNED NOT NULL,
        `permission_id` INT UNSIGNED NOT NULL,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`role_id`, `permission_id`),
        CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `user_permissions` (`permission_id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Admin (role_id = 1) → all permissions
INSERT INTO
    `role_permissions` (`role_id`, `permission_id`)
SELECT
    1,
    `permission_id`
FROM
    `user_permissions`;

-- Trainer (role_id = 2) → profile + user read + role read
INSERT INTO
    `role_permissions` (`role_id`, `permission_id`)
SELECT
    2,
    `permission_id`
FROM
    `user_permissions`
WHERE
    `name` IN (
        'PROFILE_MANAGEMENT',
        'USER_VIEW',
        'USER_EDIT',
        'USER_DELETE',
        'ROLE_VIEW'
    );

-- Trainee (role_id = 3) → profile only
INSERT INTO
    `role_permissions` (`role_id`, `permission_id`)
SELECT
    3,
    `permission_id`
FROM
    `user_permissions`
WHERE
    `name` = 'PROFILE_MANAGEMENT';

-- =============================================================================
-- 5. branches
-- =============================================================================
CREATE TABLE
    `branches` (
        `branch_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `branch_name` VARCHAR(255) NOT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `contact_number` VARCHAR(50) DEFAULT NULL,
        `email` VARCHAR(255) DEFAULT NULL,
        `fax` VARCHAR(50) DEFAULT NULL,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

INSERT INTO
    `branches` (
        `branch_name`,
        `location`,
        `contact_number`,
        `email`,
        `fax`
    )
VALUES
    (
        'Colombo Main Branch',
        'Colombo 01',
        '+94 11 2345678',
        'colombo@company.com',
        '+94 11 2345679'
    );

-- =============================================================================
-- departments
-- =============================================================================
CREATE TABLE
    `departments` (
        `dep_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `dep_name` VARCHAR(255) NOT NULL,
        `branch_id` INT UNSIGNED NOT NULL,
        `contact_number` VARCHAR(50) DEFAULT NULL,
        `email` VARCHAR(255) DEFAULT NULL,
        `fax` VARCHAR(50) DEFAULT NULL,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_department_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE RESTRICT ON UPDATE CASCADE
    );

INSERT INTO
    `departments` (
        `dep_name`,
        `branch_id`,
        `contact_number`,
        `email`,
        `fax`
    )
VALUES
    (
        'Human Resources',
        1,
        '+94 11 2233445',
        'hr@company.com',
        NULL
    );

-- =============================================================================
-- 6. Dummy data — user_mains
--    Use INSERT IGNORE to skip if rows already exist,
--    or run the TRUNCATE first for a clean re-seed.
-- =============================================================================
-- Option A: Clean re-seed (use if you want to reset completely)
SET
    FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `user_details`;

TRUNCATE TABLE `user_mains`;

SET
    FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `user_mains` AUTO_INCREMENT = 1;

INSERT INTO
    `user_mains` (
        `user_id`,
        `email`,
        `password`,
        `service_number`,
        `role_id`
    )
VALUES
    -- Admins (role_id = 1)
    (
        1,
        'admin.alexandra@learnpro.com',
        '$2b$12$AdminHash.Alexandra000',
        'CE522941',
        1
    ),
    (
        2,
        'admin.james@learnpro.com',
        '$2b$12$AdminHash.James0000000',
        'CE522942',
        1
    ),
    -- Trainers (role_id = 2)
    (
        3,
        'trainer.daniel@learnpro.com',
        '$2b$12$TrainerHash.Daniel0000',
        'CE522943',
        2
    ),
    (
        4,
        'trainer.priya@learnpro.com',
        '$2b$12$TrainerHash.Priya00000',
        'CE522944',
        2
    ),
    -- Trainees (role_id = 3)
    (
        5,
        'trainee.sithumi@learnpro.com',
        '$2b$12$TraineeHash.Sithumi000',
        'CE522945',
        3
    ),
    (
        6,
        'trainee.kasun@learnpro.com',
        '$2b$12$TraineeHash.Kasun00000',
        'CE522946',
        3
    );

-- =============================================================================
-- 7. Dummy data — user_details
--    All department_id = 1, branch_id = 1 (only one of each exists)
-- =============================================================================
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `profile_picture`,
        `bio`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `date_joined`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    -- Admin 1 — Alexandra Morgan
    (
        1,
        'Alexandra',
        'Morgan',
        '+94771000001',
        'profiles/alexandra_morgan.jpg',
        'System administrator with full platform access.',
        1,
        1,
        1,
        '2018-03-01',
        1,
        0,
        1
    ),
    -- Admin 2 — James Wickramasinghe
    (
        2,
        'James',
        'Wickramasinghe',
        '+94771000002',
        'profiles/james_wickramasinghe.jpg',
        'Co-administrator responsible for user and role management.',
        1,
        1,
        1,
        '2019-06-15',
        1,
        0,
        0
    ),
    -- Trainer 1 — Daniel Perera
    (
        3,
        'Daniel',
        'Perera',
        '+94771000003',
        'profiles/daniel_perera.jpg',
        'Senior trainer specialising in IT and software development modules.',
        2,
        1,
        1,
        '2020-07-01',
        1,
        0,
        1
    ),
    -- Trainer 2 — Priya Ranasinghe
    (
        4,
        'Priya',
        'Ranasinghe',
        '+94771000004',
        'profiles/priya_ranasinghe.jpg',
        'Trainer specialising in business communication and soft skills.',
        2,
        1,
        1,
        '2021-01-10',
        1,
        0,
        0
    ),
    -- Trainee 1 — Sithumi Fernando
    (
        5,
        'Sithumi',
        'Fernando',
        '+94771000005',
        'profiles/sithumi_fernando.jpg',
        'Enrolled in the web development programme, batch 2024-A.',
        3,
        1,
        1,
        '2024-01-15',
        1,
        0,
        0
    ),
    -- Trainee 2 — Kasun Jayawardena
    (
        6,
        'Kasun',
        'Jayawardena',
        '+94771000006',
        'profiles/kasun_jayawardena.jpg',
        'Enrolled in the data analytics programme, batch 2024-B.',
        3,
        1,
        1,
        '2024-02-01',
        1,
        0,
        1
    );

-- =============================================================================
-- Training category table (for future use in course management module)
-- =============================================================================
CREATE TABLE
    training_category (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL,
        additional_details TEXT,
        created_by INT UNSIGNED NOT NULL,
        updated_by INT UNSIGNED NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        is_delete TINYINT (1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_training_category_created_by FOREIGN KEY (created_by) REFERENCES user_details (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_training_category_updated_by FOREIGN KEY (updated_by) REFERENCES user_details (user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE = InnoDB;

-- =============================================================================
-- Training Session Table
-- =============================================================================
CREATE TABLE
    training_session (
        session_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id INT NOT NULL, -- FIXED HERE
        trainer_id INT UNSIGNED NOT NULL,
        location VARCHAR(255) NOT NULL,
        session_date DATE NOT NULL,
        session_time TIME NOT NULL,
        check_in TIME NULL DEFAULT NULL,
        check_out TIME NULL DEFAULT NULL,
        total_hours DECIMAL(5, 2) NULL DEFAULT NULL,
        additional_details TEXT NULL DEFAULT NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        is_delete TINYINT (1) NOT NULL DEFAULT 0,
        created_by INT UNSIGNED NOT NULL,
        updated_by INT UNSIGNED NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (session_id),
        INDEX idx_ts_category (category_id),
        INDEX idx_ts_trainer (trainer_id),
        INDEX idx_ts_date (session_date),
        CONSTRAINT fk_ts_category FOREIGN KEY (category_id) REFERENCES training_category (category_id),
        CONSTRAINT fk_ts_trainer FOREIGN KEY (trainer_id) REFERENCES user_details (user_id),
        CONSTRAINT fk_ts_created_by FOREIGN KEY (created_by) REFERENCES user_details (user_id),
        CONSTRAINT fk_ts_updated_by FOREIGN KEY (updated_by) REFERENCES user_details (user_id)
    ) ENGINE = InnoDB;

INSERT INTO
    training_session (
        category_id,
        trainer_id,
        location,
        session_date,
        session_time,
        check_in,
        check_out,
        total_hours,
        additional_details,
        is_active,
        is_delete,
        created_by,
        updated_by
    )
VALUES
    (
        1, -- existing training_category.category_id
        3, -- trainer (Daniel Perera)
        'Conference Room A',
        '2026-05-01',
        '09:00:00',
        '09:05:00',
        '17:00:00',
        8.00,
        'Full day training on software architecture fundamentals.',
        1,
        0,
        1, -- created_by (Admin Alexandra)
        1 -- updated_by
    );

-- =============================================================================
-- Training allocation table 
-- =============================================================================
CREATE TABLE
    training_allocations (
        training_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trainee_id INT UNSIGNED NOT NULL,
        session_id INT UNSIGNED NOT NULL,
        created_by INT UNSIGNED NOT NULL,
        training_date DATE NOT NULL,
        start_time TIME NULL,
        end_time TIME NULL,
        status TINYINT UNSIGNED DEFAULT 0,
        is_active TINYINT (1) DEFAULT 1,
        is_delete TINYINT (1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        -- Foreign Keys
        CONSTRAINT fk_training_allocation_trainee FOREIGN KEY (trainee_id) REFERENCES user_details (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_training_allocation_session FOREIGN KEY (session_id) REFERENCES training_session (session_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_training_allocation_user FOREIGN KEY (created_by) REFERENCES user_details (user_id) ON DELETE RESTRICT ON UPDATE CASCADE
    );

-- =============================================================================
-- User feedback table 
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `user_feedback` (
        `review_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `training_id` INT UNSIGNED NOT NULL,
        `rating` TINYINT NOT NULL COMMENT '1–5 star rating',
        `comment` TEXT DEFAULT NULL,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_by` INT UNSIGNED DEFAULT NULL,
        `updated_by` INT UNSIGNED DEFAULT NULL,
        PRIMARY KEY (`review_id`),
        CONSTRAINT `fk_uf_user` FOREIGN KEY (`user_id`) REFERENCES `user_details` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_uf_training` FOREIGN KEY (`training_id`) REFERENCES `training_session` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_uf_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_details` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
        CONSTRAINT `fk_uf_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `user_details` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
    );

-- =============================================================================
-- learning materials table 
-- =============================================================================
CREATE TABLE
    learning_materials (
        material_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        training_id INT UNSIGNED NOT NULL,
        material_type VARCHAR(50) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        additional_details TEXT DEFAULT NULL,
        uploaded_by INT UNSIGNED NOT NULL,
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        is_delete TINYINT (1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (material_id),
        KEY idx_learning_materials_training (training_id),
        KEY idx_learning_materials_uploaded_by (uploaded_by),
        CONSTRAINT fk_learning_materials_training FOREIGN KEY (training_id) REFERENCES training_session (session_id) ON DELETE CASCADE,
        CONSTRAINT fk_learning_materials_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES user_details (user_id) ON DELETE RESTRICT
    );

-- =============================================================================
-- END OF SCRIPT
-- =============================================================================