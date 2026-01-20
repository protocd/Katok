<?php
/**
 * API эндпоинт для работы с катками
 * GET /api/rinks.php - список всех катков
 * GET /api/rinks.php?id={id} - один каток
 * GET /api/rinks.php?district={district} - фильтрация по району
 * GET /api/rinks.php?search={query} - поиск по названию
 */

// Подключаем необходимые файлы
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../classes/Rink.php';

// Устанавливаем CORS заголовки
require_once __DIR__ . '/../includes/cors.php';

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Поддерживаем только GET запросы
if ($method !== 'GET') {
    sendError('Метод не поддерживается', 405);
}

try {
    $rink = new Rink();
    
    // Получаем параметры запроса
    $rinkId = $_GET['id'] ?? null;
    $district = $_GET['district'] ?? null;
    $search = $_GET['search'] ?? null;
    $isPaid = isset($_GET['is_paid']) ? $_GET['is_paid'] : null;
    $hasEquipmentRental = isset($_GET['has_equipment_rental']) ? $_GET['has_equipment_rental'] : null;
    $hasLockerRoom = isset($_GET['has_locker_room']) ? $_GET['has_locker_room'] : null;
    $hasCafe = isset($_GET['has_cafe']) ? $_GET['has_cafe'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Если передан ID - возвращаем один каток
    if ($rinkId) {
        $result = $rink->getById($rinkId);
        if ($result) {
            sendSuccess($result);
        } else {
            sendError('Каток не найден', 404);
        }
    }
    
    // Если передан поисковый запрос - выполняем поиск
    if ($search) {
        $results = $rink->search($search, $limit);
        sendSuccess([
            'results' => $results,
            'count' => count($results)
        ]);
    }
    
    // Формируем фильтры
    $filters = [];
    if ($district) {
        $filters['district'] = $district;
    }
    if ($isPaid !== null && $isPaid !== '') {
        $filters['is_paid'] = $isPaid === '1' || $isPaid === 'true' || $isPaid === true;
    }
    if ($hasEquipmentRental !== null && $hasEquipmentRental !== '') {
        $filters['has_equipment_rental'] = $hasEquipmentRental === '1' || $hasEquipmentRental === 'true' || $hasEquipmentRental === true;
    }
    if ($hasLockerRoom !== null && $hasLockerRoom !== '') {
        $filters['has_locker_room'] = $hasLockerRoom === '1' || $hasLockerRoom === 'true' || $hasLockerRoom === true;
    }
    if ($hasCafe !== null && $hasCafe !== '') {
        $filters['has_cafe'] = $hasCafe === '1' || $hasCafe === 'true' || $hasCafe === true;
    }
    
    // Получаем катки с фильтрами
    $results = $rink->getAll($filters, $limit, $offset);
    $total = $rink->getCount($filters);
    
    sendSuccess([
        'results' => $results,
        'count' => count($results),
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
