-- ============================================
-- Финальная база данных для приложения "Катки Москвы"
-- Соответствует BCNF, включает систему голосования
-- ============================================

-- Создание базы данных (раскомментировать при необходимости)
-- CREATE DATABASE rinks_moscow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE rinks_moscow;

-- ============================================
-- Таблица пользователей
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор пользователя',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email пользователя (уникальный, для авторизации)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Хеш пароля (bcrypt, 60 символов)',
    name VARCHAR(100) NOT NULL COMMENT 'Имя пользователя',
    role ENUM('user', 'admin') DEFAULT 'user' COMMENT 'Роль: обычный пользователь или администратор',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время регистрации',
    last_login TIMESTAMP NULL COMMENT 'Дата и время последнего входа',
    ip_address VARCHAR(45) COMMENT 'IP-адрес пользователя (для отслеживания)',
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица пользователей системы';

-- ============================================
-- Таблица катков
-- ============================================
CREATE TABLE IF NOT EXISTS rinks (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор катка',
    name VARCHAR(255) NOT NULL COMMENT 'Название катка',
    address TEXT COMMENT 'Адрес катка',
    district VARCHAR(100) COMMENT 'Административный район Москвы',
    latitude DECIMAL(10, 8) COMMENT 'Широта (для отображения на карте, точность до 1.1 мм)',
    longitude DECIMAL(11, 8) COMMENT 'Долгота (для отображения на карте, точность до 1.1 мм)',
    phone VARCHAR(50) COMMENT 'Телефон',
    email VARCHAR(255) COMMENT 'Email',
    website VARCHAR(255) COMMENT 'Сайт',
    working_hours TEXT COMMENT 'График работы (текстовое описание)',
    is_paid BOOLEAN DEFAULT FALSE COMMENT 'Платность (true/false)',
    price DECIMAL(10, 2) COMMENT 'Стоимость посещения',
    has_equipment_rental BOOLEAN DEFAULT FALSE COMMENT 'Наличие проката оборудования',
    has_locker_room BOOLEAN DEFAULT FALSE COMMENT 'Наличие раздевалки',
    has_cafe BOOLEAN DEFAULT FALSE COMMENT 'Наличие кафе',
    has_wifi BOOLEAN DEFAULT FALSE COMMENT 'Наличие Wi-Fi',
    has_atm BOOLEAN DEFAULT FALSE COMMENT 'Наличие банкомата',
    has_medpoint BOOLEAN DEFAULT FALSE COMMENT 'Наличие медпункта',
    is_disabled_accessible BOOLEAN DEFAULT FALSE COMMENT 'Приспособленность для инвалидов',
    capacity INT UNSIGNED COMMENT 'Вместимость катка',
    lighting VARCHAR(50) COMMENT 'Тип освещения',
    coverage VARCHAR(50) COMMENT 'Тип покрытия',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата добавления в базу',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
    INDEX idx_name (name),
    INDEX idx_district (district),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица катков Москвы (открытые данные Правительства Москвы)';

-- ============================================
-- Таблица посещений (VISITS) - КЛЮЧЕВАЯ СУЩНОСТЬ
-- Связывает пользователя и каток
-- ============================================
CREATE TABLE IF NOT EXISTS visits (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор посещения',
    user_id INT UNSIGNED NOT NULL COMMENT 'ID пользователя (FK -> users.id)',
    rink_id INT UNSIGNED NOT NULL COMMENT 'ID катка (FK -> rinks.id)',
    visit_date DATE NOT NULL COMMENT 'Дата посещения',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания записи о посещении',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rink_id) REFERENCES rinks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_rink_date (user_id, rink_id, visit_date),
    INDEX idx_user_id (user_id),
    INDEX idx_rink_id (rink_id),
    INDEX idx_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица посещений (связь пользователь ↔ каток)';

-- ============================================
-- Таблица отзывов
-- Привязана к посещению (visit_id)
-- Можно прикреплять фотографию
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор отзыва',
    visit_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'ID посещения (FK -> visits.id, один отзыв на одно посещение)',
    text TEXT NOT NULL COMMENT 'Текст отзыва',
    rating TINYINT UNSIGNED NOT NULL COMMENT 'Общий рейтинг катка (1-5)',
    ice_condition ENUM('excellent', 'good', 'fair', 'poor') NULL COMMENT 'Состояние льда',
    crowd_level ENUM('low', 'medium', 'high') NULL COMMENT 'Загруженность катка',
    photo_path VARCHAR(500) NULL COMMENT 'Путь к файлу фотографии на сервере (опционально)',
    photo_url VARCHAR(500) NULL COMMENT 'URL фотографии (если хранится на CDN или внешнем хранилище, опционально)',
    score INT DEFAULT 0 COMMENT 'Рейтинг голосования (upvotes - downvotes, может быть отрицательным)',
    upvotes_count INT UNSIGNED DEFAULT 0 COMMENT 'Количество upvotes (для быстрого доступа)',
    downvotes_count INT UNSIGNED DEFAULT 0 COMMENT 'Количество downvotes (для быстрого доступа)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания отзыва',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_visit_id (visit_id),
    INDEX idx_score (score),
    INDEX idx_created_at (created_at),
    CHECK (rating >= 1 AND rating <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица отзывов о катках (привязаны к посещениям, можно прикрепить одно фото)';

-- ============================================
-- Таблица отметок присутствия
-- Привязана к посещению (visit_id)
-- Проверка геолокации с учетом неточности GPS (радиус увеличен до 1 км)
-- ============================================
CREATE TABLE IF NOT EXISTS checkins (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор отметки',
    visit_id INT UNSIGNED NOT NULL COMMENT 'ID посещения (FK -> visits.id)',
    latitude DECIMAL(10, 8) NOT NULL COMMENT 'Широта пользователя при отметке (для проверки геолокации)',
    longitude DECIMAL(11, 8) NOT NULL COMMENT 'Долгота пользователя при отметке (для проверки геолокации)',
    distance DECIMAL(10, 2) COMMENT 'Расстояние от пользователя до катка в метрах (формула гаверсинуса)',
    ip_address VARCHAR(45) COMMENT 'IP-адрес пользователя (для защиты от накруток)',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Время отметки (для статистики и ограничения частоты)',
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_visit_id (visit_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица отметок присутствия (привязаны к посещениям, проверка геолокации с радиусом 1 км из-за неточности GPS)';

-- ============================================
-- Таблица голосов (VOTES) - система голосования
-- Пользователи голосуют за отзывы (upvote/downvote)
-- Полезные отзывы с информацией о расписании поднимаются вверх
-- ============================================
CREATE TABLE IF NOT EXISTS votes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор голоса',
    user_id INT UNSIGNED NOT NULL COMMENT 'ID пользователя, проголосовавшего (FK -> users.id)',
    review_id INT UNSIGNED NOT NULL COMMENT 'ID отзыва (FK -> reviews.id)',
    vote_type ENUM('up', 'down') NOT NULL COMMENT 'Тип голоса: upvote или downvote',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Время голосования',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Время изменения голоса (если пользователь изменил голос)',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, review_id),
    INDEX idx_review_id (review_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица голосов за отзывы (система upvote/downvote, Reddit-style)';

-- ============================================
-- Таблица мероприятий (EVENTS)
-- Пользователи создают мероприятия для катков (например, "догонялки")
-- Создавать может только тот, кто отметился на катке больше 5 раз (более 5 visits)
-- Присоединяться может любой, кто хотя бы раз отметился на катке (есть visit)
-- ============================================
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор мероприятия',
    rink_id INT UNSIGNED NOT NULL COMMENT 'ID катка (FK -> rinks.id)',
    created_by INT UNSIGNED NOT NULL COMMENT 'ID пользователя, создавшего мероприятие (FK -> users.id, должен иметь visit для этого катка)',
    title VARCHAR(255) NOT NULL COMMENT 'Название мероприятия (например, "Догонялки")',
    description TEXT COMMENT 'Описание мероприятия',
    event_date DATE NOT NULL COMMENT 'Дата проведения мероприятия',
    event_time TIME COMMENT 'Время проведения мероприятия',
    max_participants INT UNSIGNED NULL COMMENT 'Максимальное количество участников (NULL = без ограничений)',
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active' COMMENT 'Статус мероприятия',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания мероприятия',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
    FOREIGN KEY (rink_id) REFERENCES rinks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_rink_id (rink_id),
    INDEX idx_created_by (created_by),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица мероприятий на катках (создают пользователи, которые уже были на катке)';

-- ============================================
-- Таблица участников мероприятий (EVENT_PARTICIPANTS)
-- Пользователи присоединяются к мероприятиям (кнопка "приду")
-- Присоединиться может только тот, кто уже отмечался на этом катке (есть visit)
-- ============================================
CREATE TABLE IF NOT EXISTS event_participants (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор участия',
    event_id INT UNSIGNED NOT NULL COMMENT 'ID мероприятия (FK -> events.id)',
    user_id INT UNSIGNED NOT NULL COMMENT 'ID пользователя (FK -> users.id, должен иметь visit для катка мероприятия)',
    status ENUM('confirmed', 'maybe') DEFAULT 'confirmed' COMMENT 'Статус участия: подтвержден или возможно придет',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата присоединения к мероприятию',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица участников мероприятий (присоединяются пользователи, которые уже были на катке)';

-- ============================================
-- Таблица подозрительной активности
-- Логирование для защиты от накруток
-- ============================================
CREATE TABLE IF NOT EXISTS suspicious_activity (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный идентификатор записи',
    user_id INT UNSIGNED NULL COMMENT 'ID пользователя (FK -> users.id, может быть NULL для анонимной активности)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP-адрес',
    activity_type ENUM('checkin', 'review', 'visit', 'vote', 'event', 'event_participant') NOT NULL COMMENT 'Тип активности',
    details TEXT COMMENT 'Детали активности (JSON или текстовое описание)',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Время события',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_ip (ip_address),
    INDEX idx_timestamp (timestamp),
    INDEX idx_activity_type (activity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Таблица логирования подозрительной активности (для защиты от накруток)';

-- ============================================
-- Конец создания таблиц
-- ============================================
