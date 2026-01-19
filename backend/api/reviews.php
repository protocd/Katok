<?php
/**
 * API для работы с отзывами
 * GET /api/reviews.php?rink_id={id} - получить отзывы катка
 * POST /api/reviews.php - создать отзыв (требуется авторизация)
 */

require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/Visit.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $review = new Review();
    
    // GET - получить отзывы
    if ($method === 'GET') {
        $rinkId = $_GET['rink_id'] ?? null;
        
        if (!$rinkId) {
            sendError('Не указан rink_id', 400);
        }
        
        $reviews = $review->getByRinkId($rinkId);
        sendSuccess($reviews);
    }
    
    // POST - создать отзыв
    if ($method === 'POST') {
        requireAuth(); // Требуется авторизация
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Неверный формат данных', 400);
        }
        
        // Валидация
        if (empty($input['text'])) {
            sendError('Текст отзыва обязателен', 422);
        }
        
        if (empty($input['rating']) || $input['rating'] < 1 || $input['rating'] > 5) {
            sendError('Рейтинг должен быть от 1 до 5', 422);
        }
        
        $userId = getCurrentUserId();
        $visit = new Visit();
        
        // Если visit_id не указан, но указан rink_id - создаем visit автоматически
        if (empty($input['visit_id']) && !empty($input['rink_id'])) {
            $visitId = $visit->create($userId, $input['rink_id']);
        } else if (!empty($input['visit_id'])) {
            $visitId = $input['visit_id'];
            // Проверяем, что visit принадлежит текущему пользователю
            $visitData = $visit->getById($visitId);
            if (!$visitData || $visitData['user_id'] != $userId) {
                sendError('Посещение не найдено или не принадлежит вам', 403);
            }
        } else {
            sendError('Не указан visit_id или rink_id', 422);
        }
        
        // Создаем отзыв
        $reviewId = $review->create($visitId, $userId, [
            'text' => $input['text'],
            'rating' => $input['rating'],
            'ice_condition' => $input['ice_condition'] ?? null,
            'crowd_level' => $input['crowd_level'] ?? null,
            'photo_path' => $input['photo_path'] ?? null,
            'photo_url' => $input['photo_url'] ?? null
        ]);
        
        $reviewData = $review->getById($reviewId);
        sendSuccess($reviewData, 'Отзыв создан', 201);
    }
    
    sendError('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
