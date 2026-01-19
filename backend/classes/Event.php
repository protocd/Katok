<?php
/**
 * Класс для работы с мероприятиями
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Visit.php';

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Создать мероприятие
     */
    public function create($rinkId, $userId, $data) {
        $eventId = $this->db->insert(
            "INSERT INTO events (rink_id, created_by, title, description, event_date, event_time, max_participants) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $rinkId,
                $userId,
                $data['title'],
                $data['description'] ?? null,
                $data['event_date'],
                $data['event_time'] ?? null,
                $data['max_participants'] ?? null
            ]
        );
        
        return $eventId;
    }
    
    /**
     * Получить мероприятие по ID с участниками
     */
    public function getById($eventId) {
        $event = $this->db->fetchOne(
            "SELECT e.*, r.name as rink_name, u.name as creator_name
             FROM events e
             LEFT JOIN rinks r ON e.rink_id = r.id
             LEFT JOIN users u ON e.created_by = u.id
             WHERE e.id = ?",
            [$eventId]
        );
        
        if ($event) {
            // Получаем участников
            $event['participants'] = $this->getParticipants($eventId);
            $event['participants_count'] = count($event['participants']);
        }
        
        return $event;
    }
    
    /**
     * Получить мероприятия катка
     */
    public function getByRinkId($rinkId) {
        return $this->db->fetchAll(
            "SELECT e.*, u.name as creator_name,
             (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'confirmed') as participants_count
             FROM events e
             LEFT JOIN users u ON e.created_by = u.id
             WHERE e.rink_id = ? AND e.status = 'active' AND e.event_date >= CURDATE()
             ORDER BY e.event_date ASC, e.event_time ASC",
            [$rinkId]
        );
    }
    
    /**
     * Получить участников мероприятия
     */
    public function getParticipants($eventId) {
        return $this->db->fetchAll(
            "SELECT ep.*, u.name as user_name
             FROM event_participants ep
             LEFT JOIN users u ON ep.user_id = u.id
             WHERE ep.event_id = ? AND ep.status = 'confirmed'
             ORDER BY ep.created_at ASC",
            [$eventId]
        );
    }
    
    /**
     * Присоединиться к мероприятию
     */
    public function join($eventId, $userId, $rinkId) {
        // Проверка: был ли пользователь на катке
        $visit = new Visit();
        if (!$visit->hasVisited($userId, $rinkId)) {
            throw new Exception("Вы должны сначала посетить этот каток, чтобы присоединиться к мероприятию");
        }
        
        // Проверка лимита участников
        $event = $this->getById($eventId);
        if ($event['max_participants']) {
            $currentCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM event_participants WHERE event_id = ? AND status = 'confirmed'",
                [$eventId]
            );
            
            if ($currentCount['count'] >= $event['max_participants']) {
                throw new Exception("Мероприятие уже заполнено");
            }
        }
        
        // Присоединяемся
        $this->db->insert(
            "INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'confirmed')
             ON DUPLICATE KEY UPDATE status = 'confirmed'",
            [$eventId, $userId]
        );
        
        return true;
    }
    
    /**
     * Покинуть мероприятие
     */
    public function leave($eventId, $userId) {
        $this->db->query(
            "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?",
            [$eventId, $userId]
        );
        
        return true;
    }
    
    /**
     * Получить количество созданных пользователем событий
     * 
     * @param int $userId ID пользователя
     * @return int Количество событий
     */
    public function getCountByUserId($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM events WHERE created_by = ?",
            [$userId]
        );
        
        return (int)$result['count'];
    }
}
