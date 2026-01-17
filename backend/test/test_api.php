<?php
/**
 * Простой тест API эндпоинтов
 * Запускать через браузер или curl
 */

// Устанавливаем заголовки для JSON
header('Content-Type: application/json; charset=utf-8');

echo "=== Тест API эндпоинтов ===\n\n";

// Список эндпоинтов для проверки
$endpoints = [
    'GET /api/rinks.php' => 'http://localhost/api/rinks.php',
    'GET /api/rinks.php?district=ЦАО' => 'http://localhost/api/rinks.php?district=ЦАО',
];

echo "Для тестирования API используйте:\n";
echo "1. Postman или Insomnia\n";
echo "2. curl в командной строке\n";
echo "3. JavaScript fetch в браузере\n\n";

echo "Примеры curl команд:\n\n";

echo "# Получить список катков:\n";
echo "curl http://localhost/api/rinks.php\n\n";

echo "# Регистрация:\n";
echo "curl -X POST http://localhost/api/auth/register.php \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"email\":\"test@test.com\",\"password\":\"test123\",\"name\":\"Тест\"}'\n\n";

echo "# Авторизация:\n";
echo "curl -X POST http://localhost/api/auth/login.php \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"email\":\"test@test.com\",\"password\":\"test123\"}'\n\n";

echo "=== Инструкция ===\n";
echo "1. Убедитесь, что Apache/PHP запущен\n";
echo "2. Убедитесь, что база данных создана\n";
echo "3. Используйте Postman для удобного тестирования\n";
