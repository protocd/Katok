-- ============================================
-- База данных для приложения "Катки Москвы"
-- ============================================

-- Создание базы данных (раскомментировать при необходимости)
-- CREATE DATABASE rinks_moscow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE rinks_moscow;

-- ============================================
-- Таблица пользователей (users)
-- Назначение: Хранение данных зарегистрированных пользователей
-- Связи: 1:N с reviews, checkins, schedules
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор пользователя',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email пользователя (для авторизации)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Хеш пароля (bcrypt, 60 символов)',
    name VARCHAR(100) NOT NULL COMMENT 'Имя пользователя',
    role ENUM('user', 'admin') DEFAULT 'user' COMMENT 'Роль: обычный пользователь или администратор',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время регистрации',
    last_login TIMESTAMP NULL COMMENT 'Дата и время последнего входа',
    ip_address VARCHAR(45) COMMENT 'IP-адрес пользователя (для отслеживания)',
    INDEX idx_email (email) COMMENT 'Индекс для быстрого поиска при авторизации'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица пользователей системы';

-- ============================================
-- Таблица катков (rinks)
-- Назначение: Хранение информации о катках Москвы из открытых данных
-- Связи: 1:N с reviews, checkins, schedules
-- Источник данных: Портал открытых данных Правительства Москвы
-- ============================================
CREATE TABLE IF NOT EXISTS rinks (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор катка',
    name VARCHAR(255) NOT NULL COMMENT 'Название катка',
    address TEXT COMMENT 'Адрес катка',
    district VARCHAR(100) COMMENT 'Административный район Москвы',
    latitude DECIMAL(10, 8) COMMENT 'Широта (для отображения на карте, точность до 1.1 мм)',
    longitude DECIMAL(11, 8) COMMENT 'Долгота (для отображения на карте, точность до 1.1 мм)',
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    working_hours TEXT,
    is_paid BOOLEAN DEFAULT FALSE,
    price DECIMAL(10, 2),
    has_equipment_rental BOOLEAN DEFAULT FALSE,
    has_locker_room BOOLEAN DEFAULT FALSE,
    has_cafe BOOLEAN DEFAULT FALSE,
    has_wifi BOOLEAN DEFAULT FALSE,
    has_atm BOOLEAN DEFAULT FALSE,
    has_medpoint BOOLEAN DEFAULT FALSE,
    is_disabled_accessible BOOLEAN DEFAULT FALSE,
    capacity INT,
    lighting VARCHAR(50),
    coverage VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name) COMMENT 'Индекс для поиска по названию (живой поиск)',
    INDEX idx_district (district) COMMENT 'Индекс для фильтрации по району',
    INDEX idx_location (latitude, longitude) COMMENT 'Составной индекс для геопоиска (формула гаверсинуса)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица катков Москвы (открытые данные)';

-- ============================================
-- Таблица отзывов (reviews)
-- Назначение: Хранение отзывов пользователей о катках
-- Связи: N:1 с rinks, N:1 с users
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор отзыва',
    rink_id INT NOT NULL COMMENT 'ID катка (FK -> rinks.id)',
    user_id INT NOT NULL COMMENT 'ID пользователя (FK -> users.id)',
    text TEXT COMMENT 'Текст отзыва',
    rating TINYINT CHECK (rating >= 1 AND rating <= 5) COMMENT 'Общий рейтинг катка (1-5)',
    ice_condition TINYINT CHECK (ice_condition >= 1 AND ice_condition <= 5) COMMENT 'Оценка состояния льда (1-5)',
    crowd_level TINYINT CHECK (crowd_level >= 1 AND crowd_level <= 5) COMMENT 'Оценка загруженности катка (1-5)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rink_id) REFERENCES rinks(id) ON DELETE CASCADE COMMENT 'Связь с катком (каскадное удаление)',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE COMMENT 'Связь с пользователем (каскадное удаление)',
    INDEX idx_rink_id (rink_id) COMMENT 'Индекс для получения отзывов катка',
    INDEX idx_user_id (user_id) COMMENT 'Индекс для получения отзывов пользователя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица отзывов о катках';

-- ============================================
-- Таблица отметок присутствия (checkins)
-- Назначение: Хранение отметок присутствия пользователей на катках
-- Используется для: статистики посещаемости, защиты от накруток
-- Связи: N:1 с rinks, N:1 с users
-- Защита от накруток: ограничение частоты (1 час), проверка геолокации (500 м), отслеживание IP
-- ============================================
CREATE TABLE IF NOT EXISTS checkins (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор отметки',
    rink_id INT NOT NULL COMMENT 'ID катка (FK -> rinks.id)',
    user_id INT NOT NULL COMMENT 'ID пользователя (FK -> users.id)',
    latitude DECIMAL(10, 8) COMMENT 'Широта пользователя при отметке (для проверки геолокации)',
    longitude DECIMAL(11, 8) COMMENT 'Долгота пользователя при отметке (для проверки геолокации)',
    distance DECIMAL(10, 2) COMMENT 'Расстояние от пользователя до катка в метрах (формула гаверсинуса)',
    ip_address VARCHAR(45) COMMENT 'IP-адрес пользователя (для защиты от накруток)',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Время отметки (для статистики и ограничения частоты)',
    FOREIGN KEY (rink_id) REFERENCES rinks(id) ON DELETE CASCADE COMMENT 'Связь с катком (каскадное удаление)',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE COMMENT 'Связь с пользователем (каскадное удаление)',
    INDEX idx_rink_id (rink_id) COMMENT 'Индекс для получения отметок катка',
    INDEX idx_user_id (user_id) COMMENT 'Индекс для получения отметок пользователя',
    INDEX idx_timestamp (timestamp) COMMENT 'Индекс для статистики по времени (группировка по часам, дням)',
    INDEX idx_ip (ip_address) COMMENT 'Индекс для защиты от накруток (проверка подозрительной активности)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица отметок присутствия (для статистики и защиты от накруток)';

-- ============================================
-- Таблица расписания
-- ============================================
CREATE TABLE IF NOT EXISTS schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rink_id INT NOT NULL,
    day_of_week TINYINT CHECK (day_of_week >= 0 AND day_of_week <= 6),
    start_time TIME,
    end_time TIME,
    type ENUM('working', 'section') DEFAULT 'working',
    description TEXT,
    created_by INT,
    FOREIGN KEY (rink_id) REFERENCES rinks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rink_id (rink_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Таблица подозрительной активности
-- ============================================
CREATE TABLE IF NOT EXISTS suspicious_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    ip_address VARCHAR(45),
    activity_type VARCHAR(50),
    details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ip (ip_address),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
