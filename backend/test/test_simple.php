<?php
/**
 * Простой тест - проверка что все работает
 * Откройте в браузере: http://localhost/rinks-moscow-app/backend/test/test_simple.php
 */

echo "<h1>Тест работы проекта</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .ok { color: green; } .error { color: red; }</style>";

$errors = [];

// Тест 1: PHP версия
echo "<h2>1. Проверка PHP</h2>";
echo "Версия PHP: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4', '>=')) {
    echo "<span class='ok'>✅ PHP версия подходит</span><br>";
} else {
    echo "<span class='error'>❌ Нужна PHP 7.4 или выше</span><br>";
    $errors[] = "PHP версия";
}

// Тест 2: Расширения PHP
echo "<h2>2. Проверка расширений PHP</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✅ Расширение '$ext' установлено</span><br>";
    } else {
        echo "<span class='error'>❌ Расширение '$ext' НЕ установлено</span><br>";
        $errors[] = "Расширение $ext";
    }
}

// Тест 3: Подключение к БД
echo "<h2>3. Проверка подключения к БД</h2>";
try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../classes/Database.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<span class='ok'>✅ Подключение к БД успешно</span><br>";
    
    // Проверка таблиц
    $tables = ['users', 'rinks', 'visits', 'reviews', 'checkins', 'votes', 'events'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<span class='ok'>✅ Таблица '$table' существует ($count записей)</span><br>";
        } else {
            echo "<span class='error'>❌ Таблица '$table' НЕ существует</span><br>";
            $allTablesExist = false;
        }
    }
    
    if (!$allTablesExist) {
        $errors[] = "Таблицы БД";
        echo "<br><strong>Решение:</strong> Запустите <a href='setup_database.php'>setup_database.php</a><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка подключения: " . $e->getMessage() . "</span><br>";
    $errors[] = "Подключение к БД";
    echo "<br><strong>Решение:</strong> Проверьте настройки в config.php<br>";
}

// Тест 4: Классы
echo "<h2>4. Проверка классов</h2>";
$classes = ['Database', 'User', 'Rink', 'Visit', 'Review', 'Checkin', 'Vote', 'Event', 'Stats'];
foreach ($classes as $class) {
    $file = __DIR__ . "/../classes/$class.php";
    if (file_exists($file)) {
        echo "<span class='ok'>✅ Класс '$class' найден</span><br>";
    } else {
        echo "<span class='error'>❌ Класс '$class' НЕ найден</span><br>";
        $errors[] = "Класс $class";
    }
}

// Тест 5: API файлы
echo "<h2>5. Проверка API файлов</h2>";
$apiFiles = [
    'rinks.php',
    'reviews.php',
    'checkins.php',
    'votes.php',
    'events.php',
    'stats.php',
    'auth/register.php',
    'auth/login.php'
];
foreach ($apiFiles as $file) {
    $path = __DIR__ . "/../api/$file";
    if (file_exists($path)) {
        echo "<span class='ok'>✅ API '$file' найден</span><br>";
    } else {
        echo "<span class='error'>❌ API '$file' НЕ найден</span><br>";
        $errors[] = "API $file";
    }
}

// Итог
echo "<h2>Итог</h2>";
if (empty($errors)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: green; font-size: 18px;'>✅ Все проверки пройдены! Проект готов к работе.</strong><br>";
    echo "Можно переходить к тестированию API через Postman.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: red; font-size: 18px;'>❌ Найдены проблемы:</strong><br>";
    foreach ($errors as $error) {
        echo "- $error<br>";
    }
    echo "</div>";
}

echo "<br><hr>";
echo "<h3>Следующие шаги:</h3>";
echo "<ol>";
echo "<li><a href='setup_database.php'>Создать базу данных</a> (если еще не создана)</li>";
echo "<li>Протестировать API через <a href='test_api.php'>Postman</a></li>";
echo "<li>Добавить тестовые данные о катках</li>";
echo "</ol>";
