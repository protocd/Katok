<?php
/**
 * Настройка CORS (Cross-Origin Resource Sharing)
 * Позволяет frontend делать запросы к API
 */

// Получаем источник запроса
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Проверяем, разрешен ли этот источник
$allowedOrigins = defined('ALLOWED_ORIGINS') ? ALLOWED_ORIGINS : ['http://localhost', 'http://localhost:8080'];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Если источник не в списке, разрешаем только локальные запросы
    header("Access-Control-Allow-Origin: http://localhost");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 3600");

// Обработка preflight запросов (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
