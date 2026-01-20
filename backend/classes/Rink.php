<?php
/**
 * Класс для работы с катками
 */

require_once __DIR__ . '/Database.php';

class Rink {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получить все катки с фильтрацией
     * 
     * @param array $filters Массив фильтров (district, is_paid, has_equipment_rental, и т.д.)
     * @param int $limit Лимит записей
     * @param int $offset Смещение для пагинации
     * @return array Список катков
     */
    public function getAll($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM rinks WHERE 1=1";
        $params = [];
        
        // Фильтр по району
        if (!empty($filters['district'])) {
            $sql .= " AND district = ?";
            $params[] = $filters['district'];
        }
        
        // Фильтр по платности
        if (isset($filters['is_paid'])) {
            $sql .= " AND is_paid = ?";
            $params[] = $filters['is_paid'] ? 1 : 0;
        }
        
        // Фильтр по прокату оборудования
        if (isset($filters['has_equipment_rental'])) {
            $sql .= " AND has_equipment_rental = ?";
            $params[] = $filters['has_equipment_rental'] ? 1 : 0;
        }
        
        // Фильтр по раздевалке
        if (isset($filters['has_locker_room'])) {
            $sql .= " AND has_locker_room = ?";
            $params[] = $filters['has_locker_room'] ? 1 : 0;
        }
        
        // Фильтр по кафе
        if (isset($filters['has_cafe'])) {
            $sql .= " AND has_cafe = ?";
            $params[] = $filters['has_cafe'] ? 1 : 0;
        }
        
        // Фильтр по Wi-Fi
        if (isset($filters['has_wifi'])) {
            $sql .= " AND has_wifi = ?";
            $params[] = $filters['has_wifi'] ? 1 : 0;
        }
        
        // Фильтр по приспособленности для инвалидов
        if (isset($filters['is_disabled_accessible'])) {
            $sql .= " AND is_disabled_accessible = ?";
            $params[] = $filters['is_disabled_accessible'] ? 1 : 0;
        }
        
        // Фильтр по банкомату
        if (isset($filters['has_atm'])) {
            $sql .= " AND has_atm = ?";
            $params[] = $filters['has_atm'] ? 1 : 0;
        }
        
        // Фильтр по медпункту
        if (isset($filters['has_medpoint'])) {
            $sql .= " AND has_medpoint = ?";
            $params[] = $filters['has_medpoint'] ? 1 : 0;
        }
        
        // Сортировка по названию
        $sql .= " ORDER BY name ASC";
        
        // Лимит и смещение для пагинации
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Получить каток по ID
     * 
     * @param int $rinkId ID катка
     * @return array|null Данные катка или null
     */
    public function getById($rinkId) {
        return $this->db->fetchOne(
            "SELECT * FROM rinks WHERE id = ?",
            [$rinkId]
        );
    }
    
    /**
     * Поиск катков по названию
     * 
     * @param string $query Поисковый запрос
     * @param int $limit Лимит результатов
     * @return array Список найденных катков
     */
    public function search($query, $limit = 20) {
        if (empty($query)) {
            return [];
        }
        
        $searchQuery = "%{$query}%";
        
        return $this->db->fetchAll(
            "SELECT id, name, address, district FROM rinks 
             WHERE name LIKE ? OR address LIKE ? 
             ORDER BY name ASC 
             LIMIT ?",
            [$searchQuery, $searchQuery, $limit]
        );
    }
    
    /**
     * Найти ближайшие катки к указанным координатам
     * Использует формулу гаверсинуса для вычисления расстояния
     * 
     * @param float $latitude Широта
     * @param float $longitude Долгота
     * @param float $radius Радиус поиска в метрах (по умолчанию 5000 = 5 км)
     * @param int $limit Лимит результатов
     * @return array Список катков с расстоянием
     */
    public function getNearby($latitude, $longitude, $radius = 5000, $limit = 20) {
        // Формула гаверсинуса для вычисления расстояния
        // Расстояние в метрах
        $sql = "
            SELECT 
                *,
                (
                    6371000 * acos(
                        cos(radians(?)) * 
                        cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude)))
                ) AS distance
            FROM rinks
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            HAVING distance <= ?
            ORDER BY distance ASC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$latitude, $longitude, $latitude, $radius, $limit]);
    }
    
    /**
     * Найти оптимальный каток для группы людей
     * Вычисляет центр группы и находит ближайшие катки
     * 
     * @param array $coordinates Массив координат участников группы
     *                           Каждый элемент: ['latitude' => float, 'longitude' => float]
     * @param int $limit Лимит результатов
     * @return array Список катков с расстоянием до центра группы
     */
    public function findOptimalForGroup($coordinates, $limit = 10) {
        if (empty($coordinates)) {
            return [];
        }
        
        // Вычисляем центр группы (среднее арифметическое координат)
        $sumLat = 0;
        $sumLon = 0;
        $count = count($coordinates);
        
        foreach ($coordinates as $coord) {
            $sumLat += $coord['latitude'];
            $sumLon += $coord['longitude'];
        }
        
        $centerLat = $sumLat / $count;
        $centerLon = $sumLon / $count;
        
        // Ищем ближайшие катки к центру группы
        // Используем больший радиус для группы (10 км)
        return $this->getNearby($centerLat, $centerLon, 10000, $limit);
    }
    
    /**
     * Получить список всех районов
     * 
     * @return array Список уникальных районов
     */
    public function getDistricts() {
        $districts = $this->db->fetchAll(
            "SELECT DISTINCT district FROM rinks WHERE district IS NOT NULL AND district != '' ORDER BY district ASC"
        );
        
        return array_column($districts, 'district');
    }
    
    /**
     * Получить количество катков
     * 
     * @param array $filters Фильтры (такие же, как в getAll)
     * @return int Количество катков
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM rinks WHERE 1=1";
        $params = [];
        
        // Применяем те же фильтры, что и в getAll
        if (!empty($filters['district'])) {
            $sql .= " AND district = ?";
            $params[] = $filters['district'];
        }
        
        if (isset($filters['is_paid'])) {
            $sql .= " AND is_paid = ?";
            $params[] = $filters['is_paid'] ? 1 : 0;
        }
        
        if (isset($filters['has_equipment_rental'])) {
            $sql .= " AND has_equipment_rental = ?";
            $params[] = $filters['has_equipment_rental'] ? 1 : 0;
        }
        
        if (isset($filters['has_locker_room'])) {
            $sql .= " AND has_locker_room = ?";
            $params[] = $filters['has_locker_room'] ? 1 : 0;
        }
        
        if (isset($filters['has_cafe'])) {
            $sql .= " AND has_cafe = ?";
            $params[] = $filters['has_cafe'] ? 1 : 0;
        }
        
        if (isset($filters['has_wifi'])) {
            $sql .= " AND has_wifi = ?";
            $params[] = $filters['has_wifi'] ? 1 : 0;
        }
        
        if (isset($filters['has_atm'])) {
            $sql .= " AND has_atm = ?";
            $params[] = $filters['has_atm'] ? 1 : 0;
        }
        
        if (isset($filters['has_medpoint'])) {
            $sql .= " AND has_medpoint = ?";
            $params[] = $filters['has_medpoint'] ? 1 : 0;
        }
        
        if (isset($filters['is_disabled_accessible'])) {
            $sql .= " AND is_disabled_accessible = ?";
            $params[] = $filters['is_disabled_accessible'] ? 1 : 0;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int)$result['count'];
    }
}
