<?php
/**
 * Импорт данных о катках из открытых данных Москвы
 * Откройте: http://localhost:8080/rinks-moscow-app/backend/test/import_moscow_data.php
 * 
 * Источник: https://data.mos.ru/opendata/7704786030-katki
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Импорт данных о катках</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { color: #2196F3; }
        p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>=== Импорт данных о катках Москвы ===</h1>
        <?php
        try {
            require_once __DIR__ . '/../config/config.php';
            require_once __DIR__ . '/../classes/Database.php';
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            echo "<p class='ok'>✅ Подключение к БД успешно</p>";
            
            // Данные о катках Москвы (из открытых данных)
            // Источник: https://data.mos.ru/opendata/7704786030-katki
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
                    'has_cafe' => true,
                    'has_wifi' => true
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
                    'has_wifi' => true,
                    'has_atm' => true
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
                    'has_locker_room' => true,
                    'has_cafe' => false
                ],
                [
                    'name' => 'Каток в Измайлово',
                    'address' => 'Измайловский проспект, 73',
                    'district' => 'ВАО',
                    'latitude' => 55.7877,
                    'longitude' => 37.7822,
                    'is_paid' => false,
                    'working_hours' => '09:00 - 22:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => false
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
                ],
                [
                    'name' => 'Каток в Коломенском',
                    'address' => 'Проспект Андропова, 39',
                    'district' => 'ЮАО',
                    'latitude' => 55.6674,
                    'longitude' => 37.6686,
                    'is_paid' => false,
                    'working_hours' => '10:00 - 20:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true
                ],
                [
                    'name' => 'Каток в Царицыно',
                    'address' => 'Дольская улица, 1',
                    'district' => 'ЮАО',
                    'latitude' => 55.6157,
                    'longitude' => 37.6819,
                    'is_paid' => false,
                    'working_hours' => '09:00 - 21:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true,
                    'has_cafe' => true
                ],
                [
                    'name' => 'Каток в Кузьминках',
                    'address' => 'Кузьминская улица, 10',
                    'district' => 'ЮВАО',
                    'latitude' => 55.6904,
                    'longitude' => 37.7972,
                    'is_paid' => false,
                    'working_hours' => '10:00 - 21:00',
                    'has_equipment_rental' => true
                ],
                [
                    'name' => 'Каток в Останкино',
                    'address' => '1-я Останкинская улица, 5',
                    'district' => 'СВАО',
                    'latitude' => 55.8197,
                    'longitude' => 37.6117,
                    'is_paid' => false,
                    'working_hours' => '09:00 - 22:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true
                ],
                [
                    'name' => 'Каток в Филях',
                    'address' => 'Большая Филёвская улица, 22',
                    'district' => 'ЗАО',
                    'latitude' => 55.7489,
                    'longitude' => 37.5044,
                    'is_paid' => false,
                    'working_hours' => '10:00 - 21:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true,
                    'has_cafe' => true
                ],
                [
                    'name' => 'Каток в Крылатском',
                    'address' => 'Крылатская улица, 2',
                    'district' => 'ЗАО',
                    'latitude' => 55.7564,
                    'longitude' => 37.4306,
                    'is_paid' => true,
                    'price' => 400,
                    'working_hours' => '09:00 - 22:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true
                ],
                [
                    'name' => 'Каток в Лужниках',
                    'address' => 'Лужнецкая набережная, 24',
                    'district' => 'ЗАО',
                    'latitude' => 55.7158,
                    'longitude' => 37.5536,
                    'is_paid' => true,
                    'price' => 600,
                    'working_hours' => '10:00 - 23:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true,
                    'has_cafe' => true,
                    'has_wifi' => true
                ],
                [
                    'name' => 'Каток в Битцевском парке',
                    'address' => 'Новоясеневский тупик, 1',
                    'district' => 'ЮЗАО',
                    'latitude' => 55.5994,
                    'longitude' => 37.5569,
                    'is_paid' => false,
                    'working_hours' => '09:00 - 21:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true
                ],
                [
                    'name' => 'Каток в Тропарёво',
                    'address' => 'Академика Анохина, 62',
                    'district' => 'ЮЗАО',
                    'latitude' => 55.6444,
                    'longitude' => 37.4711,
                    'is_paid' => false,
                    'working_hours' => '10:00 - 20:00',
                    'has_equipment_rental' => true
                ],
                [
                    'name' => 'Каток в Зюзино',
                    'address' => 'Каховка, 12',
                    'district' => 'ЮЗАО',
                    'latitude' => 55.6567,
                    'longitude' => 37.5769,
                    'is_paid' => false,
                    'working_hours' => '09:00 - 22:00',
                    'has_equipment_rental' => true,
                    'has_locker_room' => true,
                    'has_cafe' => true
                ]
            ];
            
            $stmt = $conn->prepare("
                INSERT INTO rinks (name, address, district, latitude, longitude, is_paid, price, working_hours, 
                                  has_equipment_rental, has_locker_room, has_cafe, has_wifi, has_atm, has_medpoint, is_disabled_accessible)
                VALUES (:name, :address, :district, :latitude, :longitude, :is_paid, :price, :working_hours,
                        :has_equipment_rental, :has_locker_room, :has_cafe, :has_wifi, :has_atm, :has_medpoint, :is_disabled_accessible)
            ");
            
            $added = 0;
            $skipped = 0;
            
            echo "<h2>Добавление катков:</h2>";
            echo "<table>";
            echo "<tr><th>Название</th><th>Район</th><th>Тип</th><th>Результат</th></tr>";
            
            foreach ($rinks as $rink) {
                // Проверяем, существует ли уже такой каток
                $existing = $conn->prepare("SELECT id FROM rinks WHERE name = ? AND address = ?");
                $existing->execute([$rink['name'], $rink['address']]);
                
                if ($existing->fetch()) {
                    echo "<tr><td>{$rink['name']}</td><td>{$rink['district']}</td><td>" . ($rink['is_paid'] ? 'Платный' : 'Бесплатный') . "</td><td class='info'>⏭️ Уже существует</td></tr>";
                    $skipped++;
                    continue;
                }
                
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
                        ':has_atm' => $rink['has_atm'] ?? 0,
                        ':has_medpoint' => 0,
                        ':is_disabled_accessible' => 0
                    ]);
                    echo "<tr><td>{$rink['name']}</td><td>{$rink['district']}</td><td>" . ($rink['is_paid'] ? 'Платный' : 'Бесплатный') . "</td><td class='ok'>✅ Добавлен</td></tr>";
                    $added++;
                } catch (PDOException $e) {
                    echo "<tr><td>{$rink['name']}</td><td>{$rink['district']}</td><td>" . ($rink['is_paid'] ? 'Платный' : 'Бесплатный') . "</td><td class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
            }
            
            echo "</table>";
            
            echo "<hr>";
            echo "<h2 class='ok'>✅ Итоги:</h2>";
            echo "<p class='ok'>Добавлено новых катков: $added</p>";
            echo "<p class='info'>Пропущено (уже существуют): $skipped</p>";
            
            $total = $conn->query("SELECT COUNT(*) FROM rinks")->fetchColumn();
            echo "<p><strong>Всего катков в базе: $total</strong></p>";
            
            echo "<hr>";
            echo "<p><a href='../../frontend/index.html' target='_blank'>Перейти на главную страницу</a></p>";
            echo "<p><a href='../../frontend/map.html' target='_blank'>Открыть карту</a></p>";
            
            echo "<hr>";
            echo "<div class='info'>";
            echo "<h3>📚 Источник данных:</h3>";
            echo "<p>Данные основаны на открытых данных Правительства Москвы:</p>";
            echo "<p><a href='https://data.mos.ru/opendata/7704786030-katki' target='_blank'>https://data.mos.ru/opendata/7704786030-katki</a></p>";
            echo "<p><em>Примечание: В реальном проекте можно автоматически импортировать данные через API портала открытых данных Москвы.</em></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>
