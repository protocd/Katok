<?php
/**
 * API для статистики
 * GET /api/stats.php?rink_id={id}&type={type} - получить статистику катка
 */

require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../classes/Stats.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    sendError('Метод не поддерживается. Используйте GET', 405);
}

try {
    $rinkId = $_GET['rink_id'] ?? null;
    $type = $_GET['type'] ?? 'general';
    
    if (!$rinkId) {
        sendError('Не указан rink_id', 400);
    }
    
    $stats = new Stats();
    
    switch ($type) {
        case 'time':
            // Статистика по времени суток
            $data = $stats->getAttendanceByTime($rinkId);
            sendSuccess($data);
            break;
            
        case 'day':
            // Статистика по дням недели
            $data = $stats->getAttendanceByDay($rinkId);
            sendSuccess($data);
            break;
            
        case 'indicators':
            // Статистические показатели
            $data = $stats->getStatisticalIndicators($rinkId);
            sendSuccess($data);
            break;
            
        case 'histogram':
            // Гистограмма распределения
            $data = $stats->getLoadHistogram($rinkId);
            sendSuccess($data);
            break;
            
        case 'heatmap':
            // Данные для тепловой карты
            $data = $stats->getPopularityHeatmap();
            sendSuccess($data);
            break;
            
        default:
            // Общая статистика
            $data = [
                'by_time' => $stats->getAttendanceByTime($rinkId),
                'by_day' => $stats->getAttendanceByDay($rinkId),
                'indicators' => $stats->getStatisticalIndicators($rinkId),
                'histogram' => $stats->getLoadHistogram($rinkId)
            ];
            sendSuccess($data);
    }
    
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
