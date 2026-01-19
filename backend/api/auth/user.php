<?php
/**
 * API для получения информации о текущем пользователе
 * GET /api/auth/user.php - получить данные текущего пользователя
 */

require_once __DIR__ . '/../../includes/cors.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/User.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    sendError('Метод не поддерживается. Используйте GET', 405);
}

try {
    if (!isAuthenticated()) {
        sendError('Требуется авторизация', 401);
    }
    
    $user = new User();
    $userData = $user->getUserById(getCurrentUserId());
    
    if (!$userData) {
        sendError('Пользователь не найден', 404);
    }
    
    // Получаем статистику пользователя
    require_once __DIR__ . '/../../classes/Visit.php';
    require_once __DIR__ . '/../../classes/Review.php';
    require_once __DIR__ . '/../../classes/Event.php';
    
    $visit = new Visit();
    $review = new Review();
    $event = new Event();
    
    $userId = getCurrentUserId();
    
    // Количество посещений (только с успешными чек-инами)
    // Считаем только те visits, у которых есть хотя бы один успешный checkin
    $visitsResult = $this->db->fetchOne(
        "SELECT COUNT(DISTINCT v.id) as count 
         FROM visits v
         INNER JOIN checkins c ON v.id = c.visit_id
         WHERE v.user_id = ?",
        [$userId]
    );
    $visitsCount = (int)($visitsResult['count'] ?? 0);
    
    // Количество отзывов
    $reviewsCount = $review->getCountByUserId($userId);
    
    // Количество созданных событий
    $eventsCount = $event->getCountByUserId($userId);
    
    // Получаем отзывы пользователя
    $userReviews = $review->getByUserId($userId);
    
    // Формируем ответ
    $response = [
        'id' => $userData['id'],
        'name' => $userData['name'],
        'email' => $userData['email'],
        'created_at' => $userData['created_at'],
        'stats' => [
            'visits' => $visitsCount,
            'reviews' => $reviewsCount,
            'events' => $eventsCount
        ],
        'reviews' => $userReviews
    ];
    
    sendSuccess($response);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
