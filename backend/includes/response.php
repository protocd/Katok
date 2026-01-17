<?php
/**
 * Функции для стандартизированных ответов API
 */

/**
 * Отправить успешный ответ
 * 
 * @param mixed $data Данные для отправки
 * @param string $message Сообщение (опционально)
 * @param int $code HTTP код ответа
 */
function sendSuccess($data, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => true,
        'data' => $data
    ];
    
    if (!empty($message)) {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Отправить ответ с ошибкой
 * 
 * @param string $error Сообщение об ошибке
 * @param int $code HTTP код ответа
 * @param mixed $details Дополнительные детали ошибки (опционально)
 */
function sendError($error, $code = 400, $details = null) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => false,
        'error' => $error
    ];
    
    if ($details !== null) {
        $response['details'] = $details;
    }
    
    // В режиме отладки добавляем информацию об ошибке
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'file' => debug_backtrace()[0]['file'] ?? null,
            'line' => debug_backtrace()[0]['line'] ?? null
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Отправить ответ с ошибкой валидации
 * 
 * @param array $errors Массив ошибок валидации
 */
function sendValidationError($errors) {
    sendError('Ошибка валидации данных', 422, ['validation_errors' => $errors]);
}
