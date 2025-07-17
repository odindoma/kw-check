-- Facebook Ads Analytics Database Setup
-- Create database and tables for analyzing Facebook advertising campaign data

CREATE DATABASE IF NOT EXISTS facebook_ads_analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE facebook_ads_analytics;

-- Table to track uploaded CSV files to avoid duplicates
CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    row_count INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_filename (filename)
);

-- Main table for Facebook ads data
CREATE TABLE IF NOT EXISTS facebook_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advertiser VARCHAR(255),
    facebook_page_id VARCHAR(50) NOT NULL,
    ad_id VARCHAR(50) NOT NULL,
    region VARCHAR(100),
    campaign VARCHAR(500),
    ad_title TEXT,
    ad_description TEXT,
    ad_media_type VARCHAR(50),
    ad_media_hash VARCHAR(100),
    target_url_base VARCHAR(500),
    target_url_full TEXT,
    first_shown_at DATETIME,
    last_shown_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_facebook_page_id (facebook_page_id),
    INDEX idx_ad_id (ad_id),
    INDEX idx_ad_media_hash (ad_media_hash),
    INDEX idx_target_url_base (target_url_base),
    INDEX idx_first_shown_at (first_shown_at),
    INDEX idx_last_shown_at (last_shown_at),
    INDEX idx_page_target (facebook_page_id, target_url_base),
    INDEX idx_page_media (facebook_page_id, ad_media_hash)
);

-- Create a user for the application (optional, for security)
-- CREATE USER IF NOT EXISTS 'facebook_ads_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE ON facebook_ads_analytics.* TO 'facebook_ads_user'@'localhost';
-- FLUSH PRIVILEGES;

