<?php
/**
 * API эндпоинт для авторизации пользователей
 * POST /api/auth/login.php
 * 
 * Тело запроса (JSON):
 * {
 *   "email": "user@example.com",
 *   "password": "password123"
 * }
 */

// Подключаем необходимые файлы
require_once __DIR__ . '/../../includes/cors.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
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
    
    // Авторизуем пользователя
    $user = new User();
    $userData = $user->login($input['email'], $input['password']);
    
    // Устанавливаем сессию
    setUserSession(
        $userData['id'],
        $userData['email'],
        $userData['name'],
        $userData['role']
    );
    
    sendSuccess([
        'user' => $userData,
        'message' => 'Авторизация прошла успешно'
    ], 'Вход выполнен успешно');
    
} catch (Exception $e) {
    sendError($e->getMessage(), 401);
}
