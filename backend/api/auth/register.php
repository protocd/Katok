<?php
/**
 * API эндпоинт для регистрации пользователей
 * POST /api/auth/register.php
 * 
 * Тело запроса (JSON):
 * {
 *   "email": "user@example.com",
 *   "password": "password123",
 *   "name": "Иван Иванов"
 * }
 */

// Подключаем необходимые файлы
require_once __DIR__ . '/../../includes/cors.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../classes/User.php';

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Поддерживаем только POST запросы
if ($method !== 'POST') {
    sendError('Метод не поддерживается. Используйте POST', 405);
}

// Получаем данные из тела запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Неверный формат данных. Ожидается JSON', 400);
}

try {
    // Валидация обязательных полей
    if (empty($input['email'])) {
        sendError('Email обязателен для заполнения', 422);
    }
    
    if (empty($input['password'])) {
        sendError('Пароль обязателен для заполнения', 422);
    }
    
    if (empty($input['name'])) {
        sendError('Имя обязательно для заполнения', 422);
    }
    
    // Регистрируем пользователя
    $user = new User();
    $userId = $user->register($input['email'], $input['password'], $input['name']);
    
    // Получаем данные созданного пользователя
    $userData = $user->getUserById($userId);
    
    sendSuccess([
        'user' => $userData,
        'message' => 'Пользователь успешно зарегистрирован'
    ], 'Регистрация прошла успешно', 201);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
