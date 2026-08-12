-- ============================================================
-- FASBY STUDIO - Veritabanı Şeması
-- Charset: utf8mb4 (tam Türkçe & emoji desteği)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Kategori tablosu
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `sort_order`  INT(11)      NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ürün tablosu
CREATE TABLE IF NOT EXISTS `products` (
    `id`          INT(11)        NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)   NOT NULL,
    `description` TEXT           DEFAULT NULL,
    `price`       DECIMAL(10,2)  DEFAULT NULL,
    `currency`    VARCHAR(10)    NOT NULL DEFAULT 'USD',
    `category_id` INT(11)        DEFAULT NULL,
    `etsy_link`   VARCHAR(1000)  DEFAULT NULL,
    `image`       VARCHAR(500)   DEFAULT NULL,
    `tags`        VARCHAR(1000)  DEFAULT NULL,
    `featured`    TINYINT(1)     NOT NULL DEFAULT 0,
    `active`      TINYINT(1)     NOT NULL DEFAULT 1,
    `sort_order`  INT(11)        NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_active`   (`active`),
    KEY `idx_featured` (`featured`),
    CONSTRAINT `fk_product_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin kullanıcı tablosu
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(100) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) DEFAULT NULL,
    `last_login` TIMESTAMP    DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İletişim mesajları tablosu
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(500) DEFAULT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Örnek kategoriler
-- ============================================================
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Grafik Sanat',   'grafik-sanat',   'Yaratıcı grafik illüstrasyonlu tişörtler',   1),
('Tipografi',      'tipografi',      'Yazı sanatı ve anlamlı sözlerle bezeli tişörtler', 2),
('Soyut',          'soyut',          'Soyut ve sanatsal desenli tişörtler',         3),
('Doğa & Botanik', 'doga-botanik',   'Doğa ilhamlı çiçek ve bitki motifleri',      4),
('Minimalist',     'minimalist',     'Sade, şık ve minimal tasarımlar',             5),
('Vintage',        'vintage',        'Retro ve vintage ilhamlı tişörtler',          6);

-- ============================================================
-- Admin kullanıcısı
-- ŞİFRE: admin123 (kurulumdan sonra setup.php üzerinden değiştirin!)
-- ============================================================
INSERT IGNORE INTO `admin_users` (`username`, `password`, `email`) VALUES
('admin', '$2y$12$oYCmx.5LJeGDMZkev/jC6eQuy0dqzenHb58U2CBlxKOd4ocbp6V4q', 'admin@fasbystudio.com');
