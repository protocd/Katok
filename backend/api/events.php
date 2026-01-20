<?php
// API для работы с мероприятиями
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Event.php';
require_once __DIR__ . '/../classes/Visit.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $event = new Event();
    
    if ($method === 'GET') {
        $rinkId = $_GET['rink_id'] ?? null;
        $eventId = $_GET['id'] ?? null;
        
        if ($eventId) {
            $eventData = $event->getById($eventId);
            if ($eventData) {
                $userId = getCurrentUserId();
                if ($userId) {
                    $eventData['is_participant'] = $event->isParticipant($eventId, $userId);
                    $eventData['is_creator'] = ($eventData['created_by'] == $userId);
                }
                sendSuccess($eventData);
            } else {
                sendError('Мероприятие не найдено', 404);
            }
        } elseif ($rinkId) {
            $events = $event->getByRinkId($rinkId);
            
            $userId = getCurrentUserId();
            if ($userId) {
                foreach ($events as &$ev) {
                    $ev['is_participant'] = $event->isParticipant($ev['id'], $userId);
                    $ev['is_creator'] = ($ev['created_by'] == $userId);
                }
            }
            
            sendSuccess($events);
        } else {
            sendError('Не указан rink_id или id', 400);
        }
    }
    
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
        
        require_once __DIR__ . '/../classes/Rink.php';
        $rink = new Rink();
        $rinkData = $rink->getById($rinkId);
        
        $isTestRink = $rinkData && (
            (isset($rinkData['name']) && strpos($rinkData['name'], 'Дворовая территория') !== false) ||
            (isset($rinkData['address']) && strpos($rinkData['address'], 'Рогово') !== false)
        );
        
        if (!$isTestRink) {
            $visit = new Visit();
            $visitCount = $visit->getVisitCount($userId, $rinkId);
            
            if ($visitCount < 5) {
                $remaining = 5 - $visitCount;
                sendError("Для создания мероприятия нужно отметитьсь на катке минимум 5 раз. Осталось: {$remaining}", 403);
            }
        }
        
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
    
    if ($method === 'DELETE') {
        requireAuth();
        
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            sendError('Не указан id мероприятия', 400);
        }
        
        $userId = getCurrentUserId();
        $eventData = $event->getById($eventId);
        
        if (!$eventData) {
            sendError('Мероприятие не найдено', 404);
        }
        
        if ($eventData['created_by'] != $userId) {
            sendError('Вы не можете удалить это мероприятие', 403);
        }
        
        $event->delete($eventId);
        sendSuccess([], 'Мероприятие удалено');
    }
    
    sendError('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
