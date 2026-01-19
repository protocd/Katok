<?php
/**
 * Класс для работы с отметками присутствия на катках
 * Включает защиту от накруток: проверка времени, геолокации, IP-адресов
 */

require_once __DIR__ . '/Database.php';

class Checkin {
    private $db;
    
    // Константы для защиты от накруток
    const COOLDOWN_SECONDS = 3600; // 1 час между отметками
    const MAX_DISTANCE_METERS = 1000; // Максимальное расстояние от катка (1 км, учитывая неточность GPS)
    const EARTH_RADIUS_METERS = 6371000; // Радиус Земли в метрах
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Создать отметку присутствия
     * Включает все проверки защиты от накруток
     * 
     * @param int $visitId ID посещения
     * @param float $latitude Широта пользователя
     * @param float $longitude Долгота пользователя
     * @param string|null $ipAddress IP-адрес пользователя
     * @return int ID созданной отметки
     * @throws Exception Если проверки не пройдены
     */
    public function create($visitId, $latitude, $longitude, $ipAddress = null) {
        // Получаем данные посещения и катка
        $visit = $this->db->fetchOne(
            "SELECT v.*, r.latitude as rink_latitude, r.longitude as rink_longitude 
             FROM visits v
             LEFT JOIN rinks r ON v.rink_id = r.id
             WHERE v.id = ?",
            [$visitId]
        );
        
        if (!$visit) {
            throw new Exception("Посещение не найдено");
        }
        
        if (!$visit['rink_latitude'] || !$visit['rink_longitude']) {
            throw new Exception("Координаты катка не указаны");
        }
        
        // Проверка 1: Можно ли делать отметку (время между отметками)
        if (!$this->canCheckin($visit['user_id'], $visit['rink_id'])) {
            throw new Exception("Вы уже отметили присутствие на этом катке недавно. Подождите " . 
                              (self::COOLDOWN_SECONDS / 60) . " минут");
        }
        
        // Проверка 2: Расстояние до катка
        $distance = $this->calculateDistance(
            $latitude, 
            $longitude, 
            $visit['rink_latitude'], 
            $visit['rink_longitude']
        );
        
        if ($distance > self::MAX_DISTANCE_METERS) {
            // Форматируем расстояние для отображения
            if ($distance >= 1000) {
                $distanceFormatted = round($distance / 1000, 1) . " км";
            } else {
                $distanceFormatted = round($distance) . " м";
            }
            
            $maxDistanceFormatted = self::MAX_DISTANCE_METERS >= 1000 
                ? round(self::MAX_DISTANCE_METERS / 1000, 1) . " км" 
                : self::MAX_DISTANCE_METERS . " м";
            
            throw new Exception("Вы находитесь слишком далеко от катка. Расстояние: " . 
                              $distanceFormatted . " (максимум " . $maxDistanceFormatted . "). " .
                              "Убедитесь, что GPS включен и вы находитесь на катке.");
        }
        
        // Проверка 3: Подозрительная активность по IP
        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        if ($ipAddress && !$this->checkSuspiciousActivity($ipAddress)) {
            // Логируем подозрительную активность, но не блокируем жестко
            $this->logSuspiciousActivity($visit['user_id'], $ipAddress, 'checkin', 
                "Множественные отметки с одного IP");
        }
        
        // Создаем отметку
        $checkinId = $this->db->insert(
            "INSERT INTO checkins (visit_id, latitude, longitude, distance, ip_address) 
             VALUES (?, ?, ?, ?, ?)",
            [$visitId, $latitude, $longitude, round($distance, 2), $ipAddress]
        );
        
        return $checkinId;
    }
    
    /**
     * Проверить, можно ли делать отметку
     * Проверяет время с последней отметки (cooldown)
     * 
     * @param int $userId ID пользователя
     * @param int $rinkId ID катка
     * @return bool true если можно, false если нет
     */
    public function canCheckin($userId, $rinkId) {
        // Проверяем, есть ли отметка за последний час
        $lastCheckin = $this->db->fetchOne(
            "SELECT timestamp FROM checkins 
             WHERE user_id = ? AND rink_id = ? 
             AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)
             ORDER BY timestamp DESC 
             LIMIT 1",
            [$userId, $rinkId, self::COOLDOWN_SECONDS]
        );
        
        return $lastCheckin === false;
    }
    
    /**
     * Вычислить расстояние между двумя точками по формуле гаверсинуса
     * 
     * @param float $lat1 Широта первой точки
     * @param float $lon1 Долгота первой точки
     * @param float $lat2 Широта второй точки
     * @param float $lon2 Долгота второй точки
     * @return float Расстояние в метрах
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Преобразуем градусы в радианы
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        // Разницы
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;
        
        // Формула гаверсинуса
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Расстояние в метрах
        $distance = self::EARTH_RADIUS_METERS * $c;
        
        return $distance;
    }
    
    /**
     * Проверить расстояние от пользователя до катка
     * 
     * @param float $userLat Широта пользователя
     * @param float $userLon Долгота пользователя
     * @param float $rinkLat Широта катка
     * @param float $rinkLon Долгота катка
     * @return bool true если расстояние допустимо, false если нет
     */
    public function checkDistance($userLat, $userLon, $rinkLat, $rinkLon) {
        $distance = $this->calculateDistance($userLat, $userLon, $rinkLat, $rinkLon);
        return $distance <= self::MAX_DISTANCE_METERS;
    }
    
    /**
     * Проверить подозрительную активность по IP-адресу
     * 
     * @param string $ipAddress IP-адрес
     * @return bool true если активность нормальная, false если подозрительная
     */
    public function checkSuspiciousActivity($ipAddress) {
        // Проверяем количество отметок с этого IP за последние 24 часа
        $checkinsToday = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM checkins 
             WHERE ip_address = ? 
             AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$ipAddress]
        );
        
        // Если больше 10 отметок с одного IP за день - подозрительно
        $maxCheckinsPerIp = defined('SUSPICIOUS_CHECKINS_PER_IP') 
            ? SUSPICIOUS_CHECKINS_PER_IP 
            : 10;
        
        return (int)$checkinsToday['count'] < $maxCheckinsPerIp;
    }
    
    /**
     * Записать подозрительную активность в лог
     * 
     * @param int|null $userId ID пользователя (может быть null)
     * @param string $ipAddress IP-адрес
     * @param string $activityType Тип активности
     * @param string $details Детали активности
     */
    public function logSuspiciousActivity($userId, $ipAddress, $activityType, $details) {
        $this->db->insert(
            "INSERT INTO suspicious_activity (user_id, ip_address, activity_type, details) 
             VALUES (?, ?, ?, ?)",
            [$userId, $ipAddress, $activityType, $details]
        );
    }
    
    /**
     * Получить отметки присутствия для катка
     * 
     * @param int $rinkId ID катка
     * @param int $hours За сколько часов получать отметки (по умолчанию 24)
     * @return array Список отметок
     */
    public function getByRinkId($rinkId, $hours = 24) {
        return $this->db->fetchAll(
            "SELECT 
                c.*,
                u.name as user_name
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             LEFT JOIN users u ON v.user_id = u.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY c.timestamp DESC",
            [$rinkId, $hours]
        );
    }
    
    /**
     * Получить количество людей на катке сейчас
     * Считаются отметки за последний час
     * 
     * @param int $rinkId ID катка
     * @return int Количество людей
     */
    public function getCurrentCount($rinkId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT v.user_id) as count 
             FROM checkins c
             LEFT JOIN visits v ON c.visit_id = v.id
             WHERE v.rink_id = ? 
             AND c.timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$rinkId]
        );
        
        return (int)$result['count'];
    }
    
    /**
     * Получить историю отметок пользователя
     * 
     * @param int $userId ID пользователя
     * @param int $limit Лимит записей
     * @return array Список отметок с данными о катках
     */
    public function getByUserId($userId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT 
                c.*,
                r.name as rink_name,
                r.address as rink_address
             FROM checkins c
             LEFT JOIN rinks r ON c.rink_id = r.id
             WHERE c.user_id = ?
             ORDER BY c.timestamp DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
