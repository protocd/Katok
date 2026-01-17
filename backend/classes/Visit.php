<?php
/**
 * Класс для работы с посещениями
 */

require_once __DIR__ . '/Database.php';

class Visit {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Создать посещение
     */
    public function create($userId, $rinkId, $visitDate = null) {
        if (!$visitDate) {
            $visitDate = date('Y-m-d');
        }
        
        // Проверяем, нет ли уже посещения на эту дату
        $existing = $this->db->fetchOne(
            "SELECT id FROM visits WHERE user_id = ? AND rink_id = ? AND visit_date = ?",
            [$userId, $rinkId, $visitDate]
        );
        
        if ($existing) {
            return $existing['id']; // Возвращаем существующее
        }
        
        // Создаем новое посещение
        $visitId = $this->db->insert(
            "INSERT INTO visits (user_id, rink_id, visit_date) VALUES (?, ?, ?)",
            [$userId, $rinkId, $visitDate]
        );
        
        return $visitId;
    }
    
    /**
     * Получить посещение по ID
     */
    public function getById($visitId) {
        return $this->db->fetchOne(
            "SELECT * FROM visits WHERE id = ?",
            [$visitId]
        );
    }
    
    /**
     * Получить посещения пользователя
     */
    public function getByUserId($userId) {
        return $this->db->fetchAll(
            "SELECT v.*, r.name as rink_name, r.address as rink_address 
             FROM visits v
             LEFT JOIN rinks r ON v.rink_id = r.id
             WHERE v.user_id = ?
             ORDER BY v.visit_date DESC",
            [$userId]
        );
    }
    
    /**
     * Получить посещения катка
     */
    public function getByRinkId($rinkId) {
        return $this->db->fetchAll(
            "SELECT v.*, u.name as user_name 
             FROM visits v
             LEFT JOIN users u ON v.user_id = u.id
             WHERE v.rink_id = ?
             ORDER BY v.visit_date DESC",
            [$rinkId]
        );
    }
    
    /**
     * Проверить, был ли пользователь на катке
     */
    public function hasVisited($userId, $rinkId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
            [$userId, $rinkId]
        );
        
        return (int)$result['count'] > 0;
    }
    
    /**
     * Получить количество посещений пользователя на катке
     */
    public function getVisitCount($userId, $rinkId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM visits WHERE user_id = ? AND rink_id = ?",
            [$userId, $rinkId]
        );
        
        return (int)$result['count'];
    }
}
