<?php
/**
 * API для работы с отметками присутствия
 * GET /api/checkins.php?rink_id={id} - получить отметки катка
 * POST /api/checkins.php - создать отметку (требуется авторизация)
 */

require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Checkin.php';
require_once __DIR__ . '/../classes/Visit.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $checkin = new Checkin();
    
    // GET - получить отметки
    if ($method === 'GET') {
        $rinkId = $_GET['rink_id'] ?? null;
        
        if (!$rinkId) {
            sendError('Не указан rink_id', 400);
        }
        
        $hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
        $checkins = $checkin->getByRinkId($rinkId, $hours);
        
        // Получаем текущее количество людей
        $currentCount = $checkin->getCurrentCount($rinkId);
        
        sendSuccess([
            'checkins' => $checkins,
            'current_count' => $currentCount
        ]);
    }
    
    // POST - создать отметку
    if ($method === 'POST') {
        requireAuth(); // Требуется авторизация
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Неверный формат данных', 400);
        }
        
        // Валидация
        if (empty($input['rink_id'])) {
            sendError('Не указан rink_id', 422);
        }
        
        if (empty($input['latitude']) || empty($input['longitude'])) {
            sendError('Не указаны координаты', 422);
        }
        
        $userId = getCurrentUserId();
        $rinkId = $input['rink_id'];
        $latitude = (float)$input['latitude'];
        $longitude = (float)$input['longitude'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Сначала проверяем геолокацию БЕЗ создания visit
        // Получаем координаты катка
        require_once __DIR__ . '/../classes/Rink.php';
        $rink = new Rink();
        $rinkData = $rink->getById($rinkId);
        
        if (!$rinkData) {
            sendError('Каток не найден', 404);
        }
        
        if (!$rinkData['latitude'] || !$rinkData['longitude']) {
            sendError('Координаты катка не указаны', 400);
        }
        
        // Проверяем расстояние до катка
        $distance = $checkin->calculateDistance(
            $latitude,
            $longitude,
            $rinkData['latitude'],
            $rinkData['longitude']
        );
        
        $maxDistance = defined('CHECKIN_MAX_DISTANCE') ? CHECKIN_MAX_DISTANCE : 1000;
        if ($distance > $maxDistance) {
            // Форматируем расстояние для отображения
            if ($distance >= 1000) {
                $distanceFormatted = round($distance / 1000, 1) . " км";
            } else {
                $distanceFormatted = round($distance) . " м";
            }
            
            $maxDistanceFormatted = $maxDistance >= 1000 
                ? round($maxDistance / 1000, 1) . " км" 
                : $maxDistance . " м";
            
            sendError("Вы находитесь слишком далеко от катка. Расстояние: " . 
                      $distanceFormatted . " (максимум " . $maxDistanceFormatted . "). " .
                      "Убедитесь, что GPS включен и вы находитесь на катке.", 400);
        }
        
        // Проверяем cooldown
        if (!$checkin->canCheckin($userId, $rinkId)) {
            sendError("Вы уже отметили присутствие на этом катке недавно. Подождите час.", 400);
        }
        
        // Только если все проверки пройдены - создаем visit и checkin
        $visit = new Visit();
        $visitId = $visit->create($userId, $rinkId);
        
        // Создаем отметку
        $checkinId = $checkin->create($visitId, $latitude, $longitude, $ipAddress);
        
        sendSuccess(['checkin_id' => $checkinId], 'Отметка создана', 201);
    }
    
    sendError('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
