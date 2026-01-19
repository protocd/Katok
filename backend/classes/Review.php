<?php
/**
 * Класс для работы с отзывами о катках
 */

require_once __DIR__ . '/Database.php';

class Review {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получить все отзывы о катке
     * 
     * @param int $rinkId ID катка
     * @param int $limit Лимит записей
     * @param int $offset Смещение для пагинации
     * @return array Список отзывов с данными пользователей
     */
    public function getByRinkId($rinkId, $limit = 50, $offset = 0) {
        $sql = "
            SELECT 
                r.*,
                u.name as user_name,
                v.visit_date
            FROM reviews r
            LEFT JOIN visits v ON r.visit_id = v.id
            LEFT JOIN users u ON v.user_id = u.id
            WHERE v.rink_id = ?
            ORDER BY r.score DESC, r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        return $this->db->fetchAll($sql, [$rinkId, $limit, $offset]);
    }
    
    /**
     * Получить отзыв по ID
     * 
     * @param int $reviewId ID отзыва
     * @return array|null Данные отзыва или null
     */
    public function getById($reviewId) {
        $sql = "
            SELECT 
                r.*,
                u.name as user_name,
                v.visit_date,
                v.rink_id
            FROM reviews r
            LEFT JOIN visits v ON r.visit_id = v.id
            LEFT JOIN users u ON v.user_id = u.id
            WHERE r.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$reviewId]);
    }
    
    /**
     * Создать отзыв
     * 
     * @param int $visitId ID посещения
     * @param int $userId ID пользователя (для проверки)
     * @param array $data Данные отзыва (text, rating, ice_condition, crowd_level, photo_path, photo_url)
     * @return int ID созданного отзыва
     * @throws Exception Если ошибка валидации
     */
    public function create($visitId, $userId, $data) {
        // Валидация
        if (empty($data['text']) || strlen($data['text']) < 10) {
            throw new Exception("Текст отзыва должен содержать минимум 10 символов");
        }
        
        if (empty($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            throw new Exception("Рейтинг должен быть от 1 до 5");
        }
        
        // Опциональные поля (ENUM значения)
        $validIceConditions = ['excellent', 'good', 'fair', 'poor'];
        $iceCondition = isset($data['ice_condition']) && in_array($data['ice_condition'], $validIceConditions)
            ? $data['ice_condition'] : null;
        
        $validCrowdLevels = ['low', 'medium', 'high'];
        $crowdLevel = isset($data['crowd_level']) && in_array($data['crowd_level'], $validCrowdLevels)
            ? $data['crowd_level'] : null;
        
        $photoPath = $data['photo_path'] ?? null;
        $photoUrl = $data['photo_url'] ?? null;
        
        // Вставляем отзыв в базу данных
        $reviewId = $this->db->insert(
            "INSERT INTO reviews (visit_id, text, rating, ice_condition, crowd_level, photo_path, photo_url) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$visitId, $data['text'], $data['rating'], $iceCondition, $crowdLevel, $photoPath, $photoUrl]
        );
        
        return $reviewId;
    }
    
    /**
     * Получить средний рейтинг катка
     * 
     * @param int $rinkId ID катка
     * @return float Средний рейтинг (от 1 до 5) или 0 если отзывов нет
     */
    public function getAverageRating($rinkId) {
        $result = $this->db->fetchOne(
            "SELECT AVG(r.rating) as avg_rating, COUNT(*) as count 
             FROM reviews r
             LEFT JOIN visits v ON r.visit_id = v.id
             WHERE v.rink_id = ?",
            [$rinkId]
        );
        
        return $result && $result['count'] > 0 ? (float)round($result['avg_rating'], 2) : 0;
    }
    
    /**
     * Получить среднюю оценку состояния льда
     * 
     * @param int $rinkId ID катка
     * @return float Средняя оценка (от 1 до 5) или 0 если оценок нет
     */
    public function getAverageIceCondition($rinkId) {
        $result = $this->db->fetchOne(
            "SELECT AVG(ice_condition) as avg_condition, COUNT(*) as count 
             FROM reviews 
             WHERE rink_id = ? AND ice_condition IS NOT NULL",
            [$rinkId]
        );
        
        return $result && $result['count'] > 0 ? (float)round($result['avg_condition'], 2) : 0;
    }
    
    /**
     * Получить средний уровень загруженности
     * 
     * @param int $rinkId ID катка
     * @return float Средний уровень (от 1 до 5) или 0 если оценок нет
     */
    public function getAverageCrowdLevel($rinkId) {
        $result = $this->db->fetchOne(
            "SELECT AVG(crowd_level) as avg_level, COUNT(*) as count 
             FROM reviews 
             WHERE rink_id = ? AND crowd_level IS NOT NULL",
            [$rinkId]
        );
        
        return $result && $result['count'] > 0 ? (float)round($result['avg_level'], 2) : 0;
    }
    
    /**
     * Получить количество отзывов о катке
     * 
     * @param int $rinkId ID катка
     * @return int Количество отзывов
     */
    public function getCount($rinkId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM reviews WHERE rink_id = ?",
            [$rinkId]
        );
        
        return (int)$result['count'];
    }
    
    /**
     * Получить отзыв по visit_id (для проверки, есть ли уже отзыв)
     */
    public function getByVisitId($visitId) {
        $sql = "
            SELECT r.*, v.user_id, v.rink_id
            FROM reviews r
            LEFT JOIN visits v ON r.visit_id = v.id
            WHERE r.visit_id = ?
        ";
        
        return $this->db->fetchOne($sql, [$visitId]);
    }
    
    /**
     * Обновить отзыв
     * 
     * @param int $reviewId ID отзыва
     * @param int $userId ID пользователя (для проверки прав)
     * @param array $data Новые данные отзыва
     * @return bool true если обновлено
     */
    public function update($reviewId, $userId, $data) {
        // Проверяем права доступа
        $review = $this->getById($reviewId);
        
        if (!$review) {
            throw new Exception("Отзыв не найден");
        }
        
        // Получаем user_id из visit
        $visit = $this->db->fetchOne(
            "SELECT user_id FROM visits WHERE id = ?",
            [$review['visit_id']]
        );
        
        if (!$visit || $visit['user_id'] != $userId) {
            throw new Exception("Нет прав на редактирование этого отзыва");
        }
        
        // Валидация
        if (isset($data['text']) && (empty($data['text']) || strlen($data['text']) < 10)) {
            throw new Exception("Текст отзыва должен содержать минимум 10 символов");
        }
        
        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            throw new Exception("Рейтинг должен быть от 1 до 5");
        }
        
        // Формируем запрос обновления
        $updates = [];
        $params = [];
        
        if (isset($data['text'])) {
            $updates[] = "text = ?";
            $params[] = $data['text'];
        }
        
        if (isset($data['rating'])) {
            $updates[] = "rating = ?";
            $params[] = $data['rating'];
        }
        
        if (isset($data['ice_condition'])) {
            $validIceConditions = ['excellent', 'good', 'fair', 'poor'];
            if (in_array($data['ice_condition'], $validIceConditions)) {
                $updates[] = "ice_condition = ?";
                $params[] = $data['ice_condition'];
            } else {
                $updates[] = "ice_condition = NULL";
            }
        }
        
        if (isset($data['crowd_level'])) {
            $validCrowdLevels = ['low', 'medium', 'high'];
            if (in_array($data['crowd_level'], $validCrowdLevels)) {
                $updates[] = "crowd_level = ?";
                $params[] = $data['crowd_level'];
            } else {
                $updates[] = "crowd_level = NULL";
            }
        }
        
        if (isset($data['photo_path'])) {
            $updates[] = "photo_path = ?";
            $params[] = $data['photo_path'];
        }
        
        if (isset($data['photo_url'])) {
            $updates[] = "photo_url = ?";
            $params[] = $data['photo_url'];
        }
        
        if (empty($updates)) {
            throw new Exception("Нет данных для обновления");
        }
        
        $params[] = $reviewId;
        
        $sql = "UPDATE reviews SET " . implode(', ', $updates) . " WHERE id = ?";
        $this->db->query($sql, $params);
        
        return true;
    }
    
    /**
     * Удалить отзыв (только автор или администратор)
     * 
     * @param int $reviewId ID отзыва
     * @param int $userId ID пользователя (для проверки прав)
     * @param bool $isAdmin Является ли пользователь администратором
     * @return bool true если удалено, false если нет прав
     */
    public function delete($reviewId, $userId, $isAdmin = false) {
        // Проверяем права доступа
        $review = $this->getById($reviewId);
        
        if (!$review) {
            throw new Exception("Отзыв не найден");
        }
        
        // Получаем user_id из visit
        $visit = $this->db->fetchOne(
            "SELECT user_id FROM visits WHERE id = ?",
            [$review['visit_id']]
        );
        
        if (!$visit) {
            throw new Exception("Посещение не найдено");
        }
        
        if (!$isAdmin && $visit['user_id'] != $userId) {
            return false;
        }
        
        // Удаляем отзыв
        $this->db->query(
            "DELETE FROM reviews WHERE id = ?",
            [$reviewId]
        );
        
        return true;
    }
}
