<?php
/**
 * API эндпоинт для работы с катками
 */

require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../classes/Rink.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET' && $method !== 'POST') {
    sendError('Метод не поддерживается', 405);
}

try {
    $rink = new Rink();
    
    // 1. Получение конкретного катка по ID
    $rinkId = $_GET['id'] ?? null;
    if ($rinkId) {
        $result = $rink->getById($rinkId);
        if ($result) sendSuccess($result);
        else sendError('Каток не найден', 404);
        exit;
    }

    // 2. Подбор катка для группы (POST action=group)
    if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'group') {
        $input = json_decode(file_get_contents('php://input'), true);
        $coords = $input['coordinates'] ?? [];
        if (empty($coords)) {
            sendError('Координаты группы не указаны', 400);
        }
        $results = $rink->findOptimalForGroup($coords, 5);
        sendSuccess(['results' => $results]);
        exit; // Важно выйти здесь
    }

    // 3. Обычный список катков с фильтрами (GET)
    $search = $_GET['search'] ?? null;
    $district = $_GET['district'] ?? null;
    $isPaid = isset($_GET['is_paid']) ? $_GET['is_paid'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 2000;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $filters = [];
    if ($search) $filters['search'] = $search;
    if ($district) $filters['district'] = $district;
    if ($isPaid !== null && $isPaid !== '') $filters['is_paid'] = ($isPaid === '1' || $isPaid === 'true');
    
    $params = ['has_equipment_rental', 'has_locker_room', 'has_cafe', 'has_wifi', 'has_atm', 'has_medpoint', 'is_disabled_accessible'];
    foreach ($params as $p) {
        if (isset($_GET[$p]) && ($_GET[$p] === '1' || $_GET[$p] === 'true')) {
            $filters[$p] = true;
        }
    }

    $results = $rink->getAll($filters, $limit, $offset);
    $total = $rink->getCount($filters);
    
    sendSuccess([
        'results' => $results,
        'count' => count($results),
        'total' => $total
    ]);

} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
