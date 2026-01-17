<?php
/**
 * API эндпоинт для выхода из системы
 * POST /api/auth/logout.php
 */

// Подключаем необходимые файлы
require_once __DIR__ . '/../../includes/cors.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Поддерживаем только POST запросы
if ($method !== 'POST') {
    sendError('Метод не поддерживается. Используйте POST', 405);
}

// Очищаем сессию
clearUserSession();

sendSuccess([
    'message' => 'Выход выполнен успешно'
], 'Вы вышли из системы');
