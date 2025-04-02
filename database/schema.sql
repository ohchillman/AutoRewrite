-- AutoRewrite Database Schema

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS autorewrite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE autorewrite;

-- Таблица для общих настроек
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Таблица для прокси
CREATE TABLE IF NOT EXISTS proxies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    ip VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    username VARCHAR(255),
    password VARCHAR(255),
    protocol ENUM('http', 'https', 'socks4', 'socks5') NOT NULL,
    country VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_check TIMESTAMP NULL,
    status ENUM('working', 'failed', 'unchecked') DEFAULT 'unchecked',
    ip_change_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Таблица для типов аккаунтов
CREATE TABLE IF NOT EXISTS account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Таблица для аккаунтов
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255),
    password VARCHAR(255),
    api_key TEXT,
    api_secret TEXT,
    access_token TEXT,
    access_token_secret TEXT,
    refresh_token TEXT,
    additional_data JSON,
    proxy_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id) ON DELETE CASCADE,
    FOREIGN KEY (proxy_id) REFERENCES proxies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Таблица для источников парсинга
CREATE TABLE IF NOT EXISTS parsing_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    source_type ENUM('twitter', 'linkedin', 'youtube', 'blog', 'rss', 'other') NOT NULL,
    parsing_frequency INT DEFAULT 60, -- в минутах
    last_parsed TIMESTAMP NULL,
    proxy_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    additional_settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proxy_id) REFERENCES proxies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Таблица для оригинального контента
CREATE TABLE IF NOT EXISTS original_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_id INT NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    url TEXT,
    author VARCHAR(255),
    published_date TIMESTAMP NULL,
    media_urls JSON,
    parsed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_processed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (source_id) REFERENCES parsing_sources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Таблица для реврайтнутого контента
CREATE TABLE IF NOT EXISTS rewritten_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    rewrite_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_posted BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'rewritten', 'posted', 'failed') DEFAULT 'pending',
    FOREIGN KEY (original_id) REFERENCES original_content(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Таблица для постов в аккаунтах
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rewritten_id INT NOT NULL,
    account_id INT NOT NULL,
    post_url TEXT,
    posted_at TIMESTAMP NULL,
    status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rewritten_id) REFERENCES rewritten_content(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Таблица для задач планировщика
CREATE TABLE IF NOT EXISTS scheduled_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_type ENUM('parsing', 'rewriting', 'posting') NOT NULL,
    entity_id INT NOT NULL, -- ID связанной сущности (источника, контента или аккаунта)
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    scheduled_time TIMESTAMP NOT NULL,
    completed_time TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Вставка начальных данных для типов аккаунтов
INSERT INTO account_types (name, description) VALUES 
('twitter', 'Twitter аккаунты'),
('linkedin', 'LinkedIn аккаунты'),
('youtube', 'YouTube аккаунты'),
('threads', 'Threads аккаунты (через Selenium)');

-- Вставка начальных настроек
INSERT INTO settings (setting_key, setting_value) VALUES 
('makecom_api_key', ''),
('makecom_api_url', ''),
('rewrite_template', 'Перепиши следующий текст, сохраняя смысл, но изменяя формулировки: {content}'),
('max_parsing_threads', '3'),
('max_rewrite_threads', '2'),
('max_posting_threads', '5');
