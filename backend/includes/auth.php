<?php
/**
 * Функции для работы с авторизацией
 */

// Запускаем сессию, если еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверить, авторизован ли пользователь
 * 
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Получить ID текущего пользователя
 * 
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Получить данные текущего пользователя
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

/**
 * Требовать авторизацию (для API-эндпоинтов)
 * Если пользователь не авторизован, возвращает ошибку 401
 */
function requireAuth() {
    if (!isAuthenticated()) {
        require_once __DIR__ . '/response.php';
        sendError('Требуется авторизация', 401);
    }
}

/**
 * Установить данные пользователя в сессию
 * 
 * @param int $userId ID пользователя
 * @param string $email Email пользователя
 * @param string $name Имя пользователя
 * @param string $role Роль пользователя
 */
function setUserSession($userId, $email, $name, $role = 'user') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
}

/**
 * Очистить сессию пользователя
 */
function clearUserSession() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Проверить, является ли пользователь администратором
 * 
 * @return bool
 */
function isAdmin() {
    return isAuthenticated() && ($_SESSION['user_role'] ?? 'user') === 'admin';
}
