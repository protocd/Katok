<?php
/**
 * API для загрузки фото
 */
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Метод не поддерживается', 405);
}

// Проверка авторизации
requireAuth();

// Проверка наличия файла
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    sendError('Файл не загружен или произошла ошибка', 400);
}

$file = $_FILES['photo'];

// Проверка типа файла
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    sendError('Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WEBP', 400);
}

// Проверка размера (максимум 5 МБ)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    sendError('Файл слишком большой. Максимум 5 МБ', 400);
}

// Генерация уникального имени
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('photo_') . '_' . time() . '.' . $extension;

// Путь для сохранения
$uploadDir = __DIR__ . '/../uploads/';
$filepath = $uploadDir . $filename;

// Сохраняем файл
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Возвращаем полный URL для доступа к фото
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $photoUrl = $protocol . '://' . $host . '/rinks-moscow-app/backend/uploads/' . $filename;
    
    sendSuccess([
        'filename' => $filename,
        'url' => $photoUrl,
        'size' => $file['size']
    ], 'Фото успешно загружено');
} else {
    sendError('Не удалось сохранить файл', 500);
}
