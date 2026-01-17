<?php
/**
 * Класс для работы с голосами за отзывы
 */

require_once __DIR__ . '/Database.php';

class Vote {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Проголосовать за отзыв
     * Если уже голосовал - обновляет голос
     * Если голосовал так же - удаляет голос (отмена)
     */
    public function vote($userId, $reviewId, $voteType) {
        // Проверяем, есть ли уже голос
        $existing = $this->db->fetchOne(
            "SELECT id, vote_type FROM votes WHERE user_id = ? AND review_id = ?",
            [$userId, $reviewId]
        );
        
        if ($existing) {
            // Если голос такой же - удаляем (отмена)
            if ($existing['vote_type'] === $voteType) {
                $this->removeVote($userId, $reviewId);
                return null;
            }
            
            // Если другой голос - обновляем
            $this->db->query(
                "UPDATE votes SET vote_type = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$voteType, $existing['id']]
            );
            
            $this->updateCounters($reviewId);
            return $existing['id'];
        }
        
        // Создаем новый голос
        $voteId = $this->db->insert(
            "INSERT INTO votes (user_id, review_id, vote_type) VALUES (?, ?, ?)",
            [$userId, $reviewId, $voteType]
        );
        
        $this->updateCounters($reviewId);
        return $voteId;
    }
    
    /**
     * Отменить голос
     */
    public function removeVote($userId, $reviewId) {
        $this->db->query(
            "DELETE FROM votes WHERE user_id = ? AND review_id = ?",
            [$userId, $reviewId]
        );
        
        $this->updateCounters($reviewId);
    }
    
    /**
     * Обновить счетчики в таблице reviews
     */
    private function updateCounters($reviewId) {
        // Подсчитываем голоса
        $upvotes = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM votes WHERE review_id = ? AND vote_type = 'up'",
            [$reviewId]
        );
        
        $downvotes = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM votes WHERE review_id = ? AND vote_type = 'down'",
            [$reviewId]
        );
        
        $upvotesCount = (int)$upvotes['count'];
        $downvotesCount = (int)$downvotes['count'];
        $score = $upvotesCount - $downvotesCount;
        
        // Обновляем счетчики в reviews
        $this->db->query(
            "UPDATE reviews SET upvotes_count = ?, downvotes_count = ?, score = ? WHERE id = ?",
            [$upvotesCount, $downvotesCount, $score, $reviewId]
        );
    }
    
    /**
     * Получить голос пользователя за отзыв
     */
    public function getUserVote($userId, $reviewId) {
        return $this->db->fetchOne(
            "SELECT vote_type FROM votes WHERE user_id = ? AND review_id = ?",
            [$userId, $reviewId]
        );
    }
}
