-- Check Card Enterprise - Comprehensive Database Setup
-- 1. Bảng quản lý tiến độ chạy ngầm (Jobs)
CREATE TABLE IF NOT EXISTS `checkcard_jobs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gate_id` VARCHAR(50) NOT NULL,
    `gate_name` VARCHAR(100) NOT NULL,
    `config_json` LONGTEXT NULL,
    `threads` INT DEFAULT 5,
    `total_target` INT DEFAULT 0,
    `checked_count` INT DEFAULT 0,
    `live_count` INT DEFAULT 0,
    `dead_count` INT DEFAULT 0,
    `err_count` INT DEFAULT 0,
    `status` ENUM('running', 'stopped', 'paused', 'finished') DEFAULT 'stopped',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Bảng lưu trữ danh sách thẻ Live (Approved) với Metadata chi tiết
CREATE TABLE IF NOT EXISTS `checkcard_lives` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_id` INT NOT NULL,
    `card` VARCHAR(255) NOT NULL,
    `bank` VARCHAR(150) NULL,
    `country` VARCHAR(100) NULL,
    `flag` VARCHAR(20) NULL,
    `scheme` VARCHAR(50) NULL,
    `type` VARCHAR(50) NULL,
    `brand` VARCHAR(50) NULL,
    `extra_info` VARCHAR(100) NULL,
    `gate_name` VARCHAR(100) NOT NULL,
    `message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
