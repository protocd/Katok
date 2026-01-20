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
    $hasWifi = isset($_GET['has_wifi']) ? $_GET['has_wifi'] : null;
    $hasAtm = isset($_GET['has_atm']) ? $_GET['has_atm'] : null;
    $hasMedpoint = isset($_GET['has_medpoint']) ? $_GET['has_medpoint'] : null;
    $isDisabledAccessible = isset($_GET['is_disabled_accessible']) ? $_GET['is_disabled_accessible'] : null;
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // ... (код до формирования фильтров) ...
    
    // Формируем фильтры
    $filters = [];
    if ($district) {
        $filters['district'] = $district;
    }
    if ($isPaid !== null && $isPaid !== '') {
        $filters['is_paid'] = ($isPaid === '1' || $isPaid === 'true');
    }
    if ($hasEquipmentRental === '1' || $hasEquipmentRental === 'true') $filters['has_equipment_rental'] = true;
    if ($hasLockerRoom === '1' || $hasLockerRoom === 'true') $filters['has_locker_room'] = true;
    if ($hasCafe === '1' || $hasCafe === 'true') $filters['has_cafe'] = true;
    if ($hasWifi === '1' || $hasWifi === 'true') $filters['has_wifi'] = true;
    if ($hasAtm === '1' || $hasAtm === 'true') $filters['has_atm'] = true;
    if ($hasMedpoint === '1' || $hasMedpoint === 'true') $filters['has_medpoint'] = true;
    if ($isDisabledAccessible === '1' || $isDisabledAccessible === 'true') $filters['is_disabled_accessible'] = true;
    
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
