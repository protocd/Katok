<?php
/**
 * API для работы с мероприятиями
 * GET /api/events.php?rink_id={id} - получить мероприятия катка
 * POST /api/events.php - создать мероприятие (требуется 5+ посещений)
 */

require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Event.php';
require_once __DIR__ . '/../classes/Visit.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $event = new Event();
    
    // GET - получить мероприятия
    if ($method === 'GET') {
        $rinkId = $_GET['rink_id'] ?? null;
        $eventId = $_GET['id'] ?? null;
        
        if ($eventId) {
            // Получить одно мероприятие с участниками
            $eventData = $event->getById($eventId);
            if ($eventData) {
                sendSuccess($eventData);
            } else {
                sendError('Мероприятие не найдено', 404);
            }
        } elseif ($rinkId) {
            // Получить мероприятия катка
            $events = $event->getByRinkId($rinkId);
            sendSuccess($events);
        } else {
            sendError('Не указан rink_id или id', 400);
        }
    }
    
    // POST - создать мероприятие
    if ($method === 'POST') {
        requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Неверный формат данных', 400);
        }
        
        if (empty($input['rink_id'])) {
            sendError('Не указан rink_id', 422);
        }
        
        if (empty($input['title'])) {
            sendError('Название мероприятия обязательно', 422);
        }
        
        if (empty($input['event_date'])) {
            sendError('Дата мероприятия обязательна', 422);
        }
        
        $userId = getCurrentUserId();
        $rinkId = $input['rink_id'];
        
        // Проверка: было ли 5+ посещений
        $visit = new Visit();
        $visitCount = $visit->getVisitCount($userId, $rinkId);
        
        if ($visitCount < 5) {
            $remaining = 5 - $visitCount;
            sendError("Для создания мероприятия нужно отметитьсь на катке минимум 5 раз. Осталось: {$remaining}", 403);
        }
        
        // Создаем мероприятие
        $eventId = $event->create($rinkId, $userId, [
            'title' => $input['title'],
            'description' => $input['description'] ?? null,
            'event_date' => $input['event_date'],
            'event_time' => $input['event_time'] ?? null,
            'max_participants' => $input['max_participants'] ?? null
        ]);
        
        $eventData = $event->getById($eventId);
        sendSuccess($eventData, 'Мероприятие создано', 201);
    }
    
    sendError('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
