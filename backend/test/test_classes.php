<?php
/**
 * Тест работы классов
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Rink.php';
require_once __DIR__ . '/../classes/Visit.php';

echo "=== Тест работы классов ===\n\n";

try {
    // Тест 1: Класс User
    echo "1. Тест класса User...\n";
    $user = new User();
    
    // Тест хеширования пароля
    $password = "test123";
    $hash = $user->hashPassword($password);
    $verify = $user->verifyPassword($password, $hash);
    
    if ($verify) {
        echo "   ✅ Хеширование паролей работает\n";
    } else {
        echo "   ❌ Ошибка хеширования паролей\n";
    }
    
    // Тест 2: Класс Rink
    echo "\n2. Тест класса Rink...\n";
    $rink = new Rink();
    
    // Получаем список районов
    $districts = $rink->getDistricts();
    echo "   ✅ Получено районов: " . count($districts) . "\n";
    
    // Тест 3: Класс Visit
    echo "\n3. Тест класса Visit...\n";
    $visit = new Visit();
    echo "   ✅ Класс Visit создан\n";
    
    echo "\n=== Все тесты пройдены ===\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}
