<?php
/**
 * Добавление тестовых данных
 * Откройте: http://localhost:8080/rinks-moscow-app/backend/test/add_test_data.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тестовые данные</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>=== Добавление тестовых данных ===</h1>
        <?php
        try {
            require_once __DIR__ . '/../config/config.php';
            require_once __DIR__ . '/../classes/Database.php';
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            echo "<p class='ok'>✅ Подключение к БД успешно</p>";
            
            // Проверяем, есть ли уже данные
            $count = $conn->query("SELECT COUNT(*) FROM rinks")->fetchColumn();
            if ($count > 0) {
                echo "<p class='info'>ℹ️ В базе уже есть $count катков.</p>";
                echo "<p><a href='import_moscow_data.php'>Добавить больше катков из открытых данных</a></p>";
            } else {
                // Добавляем тестовые катки
                $rinks = [
                    [
                        'name' => 'Каток в Парке Горького',
                        'address' => 'Крымский Вал, 9',
                        'district' => 'ЦАО',
                        'latitude' => 55.7314,
                        'longitude' => 37.6031,
                        'is_paid' => false,
                        'working_hours' => '10:00 - 22:00',
                        'has_equipment_rental' => true,
                        'has_locker_room' => true,
                        'has_cafe' => true
                    ],
                    [
                        'name' => 'Каток на ВДНХ',
                        'address' => 'Проспект Мира, 119',
                        'district' => 'СВАО',
                        'latitude' => 55.8304,
                        'longitude' => 37.6250,
                        'is_paid' => true,
                        'price' => 500,
                        'working_hours' => '09:00 - 23:00',
                        'has_equipment_rental' => true,
                        'has_locker_room' => true,
                        'has_cafe' => true,
                        'has_wifi' => true
                    ],
                    [
                        'name' => 'Каток в Сокольниках',
                        'address' => 'Сокольнический Вал, 1',
                        'district' => 'ВАО',
                        'latitude' => 55.7895,
                        'longitude' => 37.6794,
                        'is_paid' => false,
                        'working_hours' => '10:00 - 21:00',
                        'has_equipment_rental' => true,
                        'has_locker_room' => true
                    ],
                    [
                        'name' => 'Каток в Измайлово',
                        'address' => 'Измайловский проспект, 73',
                        'district' => 'ВАО',
                        'latitude' => 55.7877,
                        'longitude' => 37.7822,
                        'is_paid' => false,
                        'working_hours' => '09:00 - 22:00',
                        'has_equipment_rental' => true
                    ],
                    [
                        'name' => 'Каток на Красной площади',
                        'address' => 'Красная площадь, 1',
                        'district' => 'ЦАО',
                        'latitude' => 55.7539,
                        'longitude' => 37.6208,
                        'is_paid' => true,
                        'price' => 800,
                        'working_hours' => '10:00 - 23:00',
                        'has_equipment_rental' => true,
                        'has_locker_room' => true,
                        'has_cafe' => true,
                        'has_wifi' => true,
                        'has_atm' => true
                    ]
                ];
                
                $stmt = $conn->prepare("
                    INSERT INTO rinks (name, address, district, latitude, longitude, is_paid, price, working_hours, 
                                      has_equipment_rental, has_locker_room, has_cafe, has_wifi, has_atm)
                    VALUES (:name, :address, :district, :latitude, :longitude, :is_paid, :price, :working_hours,
                            :has_equipment_rental, :has_locker_room, :has_cafe, :has_wifi, :has_atm)
                ");
                
                $added = 0;
                foreach ($rinks as $rink) {
                    try {
                        $stmt->execute([
                            ':name' => $rink['name'],
                            ':address' => $rink['address'],
                            ':district' => $rink['district'],
                            ':latitude' => $rink['latitude'],
                            ':longitude' => $rink['longitude'],
                            ':is_paid' => $rink['is_paid'] ? 1 : 0,
                            ':price' => $rink['price'] ?? null,
                            ':working_hours' => $rink['working_hours'],
                            ':has_equipment_rental' => $rink['has_equipment_rental'] ?? 0,
                            ':has_locker_room' => $rink['has_locker_room'] ?? 0,
                            ':has_cafe' => $rink['has_cafe'] ?? 0,
                            ':has_wifi' => $rink['has_wifi'] ?? 0,
                            ':has_atm' => $rink['has_atm'] ?? 0
                        ]);
                        echo "<p class='ok'>✅ Добавлен каток: {$rink['name']}</p>";
                        $added++;
                    } catch (PDOException $e) {
                        echo "<p class='error'>❌ Ошибка при добавлении '{$rink['name']}': " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                
                echo "<hr>";
                echo "<h2 class='ok'>✅ Добавлено катков: $added</h2>";
            }
            
            // Проверяем пользователей
            $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "<p>Пользователей в базе: $userCount</p>";
            echo "<p><em>Для создания пользователя используйте форму регистрации на сайте</em></p>";
            
            echo "<hr>";
            echo "<p><a href='../../frontend/index.html'>Перейти на главную страницу</a></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>
