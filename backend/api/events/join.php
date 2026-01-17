<?php
/**
 * API для присоединения к мероприятию
 * POST /api/events/join.php - присоединиться к мероприятию
 */

require_once __DIR__ . '/../../includes/cors.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Event.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    sendError('Метод не поддерживается. Используйте POST', 405);
}

try {
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['event_id'])) {
        sendError('Не указан event_id', 400);
    }
    
    $event = new Event();
    $userId = getCurrentUserId();
    
    // Получаем каток мероприятия
    $eventData = $event->getById($input['event_id']);
    if (!$eventData) {
        sendError('Мероприятие не найдено', 404);
    }
    
    $rinkId = $eventData['rink_id'];
    
    // Присоединяемся
    $event->join($input['event_id'], $userId, $rinkId);
    
    sendSuccess([], 'Вы присоединились к мероприятию');
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
