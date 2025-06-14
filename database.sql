-- -----------------------------------------------------
-- База данни: presentation_generator
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS presentation_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE presentation_generator;

-- -----------------------------------------------------
-- Таблица: users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Таблица: workspaces
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS workspaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Таблица: user_workspaces (много-към-много с роли)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS user_workspaces (
    user_id INT NOT NULL,
    workspace_id INT NOT NULL,
    role ENUM('owner', 'editor', 'member') DEFAULT 'member',
    PRIMARY KEY (user_id, workspace_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Таблица: presentations
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS presentations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    language VARCHAR(10) DEFAULT 'bg',
    theme VARCHAR(50) DEFAULT 'light',
    version VARCHAR(50),
    navigation_rules TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Таблица: slides
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presentation_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slide_order INT NOT NULL,
    layout VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Таблица: slide_elements
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS slide_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slide_id INT NOT NULL,
    element_order INT NOT NULL,
    type ENUM('text', 'image', 'image_text', 'image_list', 'list', 'quote') NOT NULL,
    title VARCHAR(255),
    content TEXT,
    text TEXT,
    style JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slide_id) REFERENCES slides(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Таблица: themes
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    css TEXT NOT NULL,
    user_id INT DEFAULT NULL,
    is_global BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
 