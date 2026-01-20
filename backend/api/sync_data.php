<?php
/**
 * API для ПОЛНОЙ синхронизации с data.mos.ru (датасет 1231 — Катки)
 */
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../classes/DataSync.php';

try {
    $sync = new DataSync();
    
    // 1. Узнаем сколько всего записей
    $totalCount = $sync->getCount();
    
    if ($totalCount === 0) {
        throw new Exception("Датасет пуст или недоступен. Проверьте API ключ и ID датасета.");
    }
    
    $processed = 0;
    $batchSize = 500; // Меньший размер для стабильности
    
    // 2. Цикл пагинации
    for ($skip = 0; $skip < $totalCount; $skip += $batchSize) {
        $batchProcessed = $sync->syncBatch($batchSize, $skip);
        $processed += $batchProcessed;
    }

    sendSuccess([
        'total_in_api' => $totalCount,
        'processed_in_db' => $processed,
        'dataset_id' => 1231,
        'dataset_name' => 'Открытые ледовые катки'
    ], "Синхронизация завершена! Загружено {$processed} катков из {$totalCount} доступных.");

} catch (Exception $e) {
    sendError("Ошибка синхронизации: " . $e->getMessage());
}
