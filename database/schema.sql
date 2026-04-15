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
    `user_permissions` (`name`, `display_name`, `description`)
VALUES
    (
        'PROFILE_MANAGEMENT',
        'Profile Management',
        'Can view and edit own profile'
    ),
    (
        'USER_VIEW',
        'View Users',
        'Can list and view user accounts'
    ),
    (
        'USER_EDIT',
        'Edit Users',
        'Can update user accounts'
    ),
    (
        'USER_DELETE',
        'Delete Users',
        'Can soft-delete user accounts'
    ),
    (
        'ROLE_VIEW',
        'View Roles',
        'Can list and view roles'
    ),
    (
        'ROLE_CREATE',
        'Create Roles',
        'Can create new roles'
    ),
    (
        'ROLE_EDIT',
        'Edit Roles',
        'Can assign/revoke permissions on roles'
    ),
    (
        'PERMISSION_VIEW',
        'View Permissions',
        'Can list permissions'
    ),
    (
        'PERMISSION_CREATE',
        'Create Permissions',
        'Can create new permissions'
    ),
    (
        'PERMISSION_EDIT',
        'Edit Permissions',
        'Can update permissions'
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
-- 4. user_mains
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `user_mains` (
        `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL DEFAULT 'EXTERNAL_AUTH',
        `service_number` VARCHAR(50) DEFAULT NULL,
        `role_id` INT UNSIGNED NOT NULL DEFAULT 3,
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        INDEX `idx_email` (`email`),
        CONSTRAINT `fk_um_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- =============================================================================
-- 5. user_details
-- =============================================================================
CREATE TABLE
    IF NOT EXISTS `user_details` (
        `user_id` INT UNSIGNED NOT NULL,
        -- Personal info
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100) NOT NULL,
        `phone_no` VARCHAR(20) DEFAULT NULL,
        `nic` VARCHAR(20) DEFAULT NULL,
        `dob` DATE DEFAULT NULL,
        `address` TEXT DEFAULT NULL,
        `gender` ENUM ('Male', 'Female', 'Other') DEFAULT NULL,
        `marital_status` ENUM ('Single', 'Married', 'Divorced', 'Widowed') DEFAULT NULL,
        `blood_group` ENUM ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') DEFAULT NULL,
        -- Org references
        `role_id` INT UNSIGNED DEFAULT NULL,
        `department_id` INT UNSIGNED DEFAULT NULL,
        `branch_id` INT UNSIGNED DEFAULT NULL,
        -- Employment
        `employment_type` ENUM ('Full-Time', 'Part-Time', 'Contract', 'Intern') DEFAULT NULL,
        `date_joined` DATE DEFAULT NULL,
        `probation_end_date` DATE DEFAULT NULL,
        `date_left` DATE DEFAULT NULL,
        -- Financial
        `basic_salary` DECIMAL(12, 2) DEFAULT NULL,
        `bank_account_number` VARCHAR(50) DEFAULT NULL,
        `tax_id` VARCHAR(50) DEFAULT NULL,
        `epf_no` VARCHAR(50) DEFAULT NULL,
        -- Hierarchy
        `manager_id` INT UNSIGNED DEFAULT NULL,
        -- Emergency contact
        `emergency_contact_name` VARCHAR(150) DEFAULT NULL,
        `emergency_contact_relationship` VARCHAR(100) DEFAULT NULL,
        `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
        -- Misc
        `additional_details` TEXT DEFAULT NULL,
        `pro_pic` VARCHAR(500) DEFAULT NULL,
        -- Flags & timestamps
        `is_active` TINYINT (1) NOT NULL DEFAULT 1,
        `is_delete` TINYINT (1) NOT NULL DEFAULT 0,
        `is_online` TINYINT (1) NOT NULL DEFAULT 0,
        `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        -- Indexes
        INDEX `idx_ud_manager` (`manager_id`),
        -- Foreign Keys
        CONSTRAINT `fk_ud_user` FOREIGN KEY (`user_id`) REFERENCES `user_mains` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_ud_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE SET NULL,
        CONSTRAINT `fk_ud_manager` FOREIGN KEY (`manager_id`) REFERENCES `user_details` (`user_id`) ON DELETE SET NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- =============================================================================
-- 6. Dummy data — user_mains
--    2 per role → 6 rows total
--    Passwords are bcrypt placeholders (replace with real hashes in production)
-- =============================================================================
INSERT INTO
    `user_mains` (`email`, `password`, `service_number`, `role_id`)
VALUES
    -- Admins (role_id = 1)
    (
        'admin.alexandra@learnpro.com',
        '$2b$12$AdminHash.Alexandra000',
        'CE522941',
        1
    ),
    (
        'admin.james@learnpro.com',
        '$2b$12$AdminHash.James0000000',
        'CE522942',
        1
    ),
    -- Trainers (role_id = 2)
    (
        'trainer.daniel@learnpro.com',
        '$2b$12$TrainerHash.Daniel0000',
        'CE522943',
        2
    ),
    (
        'trainer.priya@learnpro.com',
        '$2b$12$TrainerHash.Priya00000',
        'CE522944',
        2
    ),
    -- Trainees (role_id = 3)
    (
        'trainee.sithumi@learnpro.com',
        '$2b$12$TraineeHash.Sithumi000',
        'CE522945',
        3
    ),
    (
        'trainee.kasun@learnpro.com',
        '$2b$12$TraineeHash.Kasun00000',
        'CE522946',
        3
    );

-- =============================================================================
-- 7. Dummy data — user_details
--    FIXED: Changed 'user_id' to `user_id` (backticks instead of single quotes)
--    Inserted in hierarchy order:
--      Admins first (no manager) → user_details IDs 1, 2
--      Trainers next (manager = Admin 1) → IDs 3, 4
--      Trainees last (manager = Trainer 3) → IDs 5, 6
-- =============================================================================
-- ── Admin 1 — Alexandra Morgan ───────────────────────────────────────────────
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        1,
        'Alexandra',
        'Morgan',
        '+94771000001',
        '198501201234',
        '1985-01-20',
        '12 Admin Lane, Colombo 03',
        'Female',
        'Married',
        'O+',
        1,
        1,
        1,
        'Full-Time',
        '2018-03-01',
        '2018-06-01',
        NULL,
        250000.00,
        'BOC-ACC-00001',
        'TAX-ADM-0001',
        'EPF-ADM-0001',
        NULL,
        'Robert Morgan',
        'Spouse',
        '+94771900001',
        'System administrator with full platform access.',
        'profiles/alexandra_morgan.jpg',
        1,
        0,
        1
    );

-- ── Admin 2 — James Wickramasinghe ───────────────────────────────────────────
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        2,
        'James',
        'Wickramasinghe',
        '+94771000002',
        '198808152345',
        '1988-08-15',
        '34 Bauddhaloka Mawatha, Colombo 07',
        'Male',
        'Single',
        'A+',
        1,
        1,
        1,
        'Full-Time',
        '2019-06-15',
        '2019-09-15',
        NULL,
        245000.00,
        'HNB-ACC-00002',
        'TAX-ADM-0002',
        'EPF-ADM-0002',
        NULL,
        'Susan Wickramasinghe',
        'Mother',
        '+94771900002',
        'Co-administrator responsible for user and role management.',
        'profiles/james_wickramasinghe.jpg',
        1,
        0,
        0
    );

-- ── Trainer 1 — Daniel Perera ─────────────────────────────────────────────────
--    manager_id = 1  (Admin Alexandra)
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        3,
        'Daniel',
        'Perera',
        '+94771000003',
        '199203153456',
        '1992-03-15',
        '45 Trainer Road, Kandy',
        'Male',
        'Single',
        'B+',
        2,
        2,
        1,
        'Full-Time',
        '2020-07-01',
        '2020-10-01',
        NULL,
        120000.00,
        'COM-ACC-00003',
        'TAX-TRN-0003',
        'EPF-TRN-0003',
        1,
        'Mary Perera',
        'Mother',
        '+94771900003',
        'Senior trainer specialising in IT and software development modules.',
        'profiles/daniel_perera.jpg',
        1,
        0,
        1
    );

-- ── Trainer 2 — Priya Ranasinghe ──────────────────────────────────────────────
--    manager_id = 1  (Admin Alexandra)
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        4,
        'Priya',
        'Ranasinghe',
        '+94771000004',
        '199507224567',
        '1995-07-22',
        '67 Palm Grove, Negombo',
        'Female',
        'Married',
        'AB+',
        2,
        2,
        2,
        'Full-Time',
        '2021-01-10',
        '2021-04-10',
        NULL,
        115000.00,
        'NSB-ACC-00004',
        'TAX-TRN-0004',
        'EPF-TRN-0004',
        1,
        'Nimal Ranasinghe',
        'Spouse',
        '+94771900004',
        'Trainer specialising in business communication and soft skills.',
        'profiles/priya_ranasinghe.jpg',
        1,
        0,
        0
    );

-- ── Trainee 1 — Sithumi Fernando ─────────────────────────────────────────────
--    manager_id = 3  (Trainer Daniel)
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        5,
        'Sithumi',
        'Fernando',
        '+94771000005',
        '200108085678',
        '2001-08-08',
        '78 Beach Road, Negombo',
        'Female',
        'Single',
        'A+',
        3,
        2,
        2,
        'Intern',
        '2024-01-15',
        '2024-04-15',
        NULL,
        35000.00,
        'BOC-ACC-00005',
        'TAX-TNE-0005',
        'EPF-TNE-0005',
        3,
        'Kamal Fernando',
        'Father',
        '+94771900005',
        'Enrolled in the web development programme, batch 2024-A.',
        'profiles/sithumi_fernando.jpg',
        1,
        0,
        0
    );

-- ── Trainee 2 — Kasun Jayawardena ────────────────────────────────────────────
--    manager_id = 3  (Trainer Daniel)
INSERT INTO
    `user_details` (
        `user_id`,
        `first_name`,
        `last_name`,
        `phone_no`,
        `nic`,
        `dob`,
        `address`,
        `gender`,
        `marital_status`,
        `blood_group`,
        `role_id`,
        `department_id`,
        `branch_id`,
        `employment_type`,
        `date_joined`,
        `probation_end_date`,
        `date_left`,
        `basic_salary`,
        `bank_account_number`,
        `tax_id`,
        `epf_no`,
        `manager_id`,
        `emergency_contact_name`,
        `emergency_contact_relationship`,
        `emergency_contact_phone`,
        `additional_details`,
        `pro_pic`,
        `is_active`,
        `is_delete`,
        `is_online`
    )
VALUES
    (
        6,
        'Kasun',
        'Jayawardena',
        '+94771000006',
        '200212106789',
        '2002-12-10',
        '90 Lake Drive, Colombo 08',
        'Male',
        'Single',
        'O-',
        3,
        3,
        1,
        'Intern',
        '2024-02-01',
        '2024-05-01',
        NULL,
        32000.00,
        'HNB-ACC-00006',
        'TAX-TNE-0006',
        'EPF-TNE-0006',
        3,
        'Anoma Jayawardena',
        'Mother',
        '+94771900006',
        'Enrolled in the data analytics programme, batch 2024-B.',
        'profiles/kasun_jayawardena.jpg',
        1,
        0,
        1
    );

-- =============================================================================
-- END OF SCRIPT
-- =============================================================================