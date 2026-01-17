<?php
/**
 * Файл конфигурации приложения
 * 
 * ВАЖНО: Скопируйте этот файл в config.php и заполните реальными значениями
 * config.php добавлен в .gitignore и не будет закоммичен в репозиторий
 */

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'rinks_moscow');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Настройки приложения
define('APP_NAME', 'Катки Москвы');
define('APP_URL', 'http://localhost');
define('FRONTEND_URL', 'http://localhost/frontend');

// Настройки безопасности
define('SESSION_NAME', 'rinks_session');
define('SESSION_LIFETIME', 3600); // 1 час

// Настройки CORS
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080'
]);

// Настройки защиты от накруток
define('CHECKIN_COOLDOWN', 3600); // 1 час между отметками (в секундах)
define('CHECKIN_MAX_DISTANCE', 500); // Максимальное расстояние от катка (в метрах)
define('SUSPICIOUS_CHECKINS_PER_IP', 10); // Максимальное количество отметок с одного IP в день

// Настройки кэширования
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 час (в секундах)

// Режим отладки (отключить в продакшене!)
define('DEBUG_MODE', true);
define('ERROR_REPORTING', E_ALL);
