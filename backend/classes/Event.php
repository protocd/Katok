<?php
// Работа с мероприятиями
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Visit.php';

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
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
            $event['participants'] = $this->getParticipants($eventId);
            $event['participants_count'] = count($event['participants']);
        }
        
        return $event;
    }
    
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
    
    public function join($eventId, $userId, $rinkId) {
        $visit = new Visit();
        if (!$visit->hasVisited($userId, $rinkId)) {
            throw new Exception("Вы должны сначала посетить этот каток, чтобы присоединиться к мероприятию");
        }
        
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
        
        $this->db->insert(
            "INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'confirmed')
             ON DUPLICATE KEY UPDATE status = 'confirmed'",
            [$eventId, $userId]
        );
        
        return true;
    }
    
    public function leave($eventId, $userId) {
        $this->db->query(
            "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?",
            [$eventId, $userId]
        );
        
        return true;
    }
    
    public function getCountByUserId($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM events WHERE created_by = ?",
            [$userId]
        );
        
        return (int)$result['count'];
    }
    
    public function delete($eventId) {
        $this->db->query("DELETE FROM event_participants WHERE event_id = ?", [$eventId]);
        $this->db->query("DELETE FROM events WHERE id = ?", [$eventId]);
        return true;
    }
    
    public function isParticipant($eventId, $userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM event_participants WHERE event_id = ? AND user_id = ? AND status = 'confirmed'",
            [$eventId, $userId]
        );
        return (int)$result['count'] > 0;
    }
}
