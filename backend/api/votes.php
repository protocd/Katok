<?php
// API для голосования за отзывы
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Vote.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    requireAuth();
    
    $vote = new Vote();
    $userId = getCurrentUserId();
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Неверный формат данных', 400);
        }
        
        if (empty($input['review_id'])) {
            sendError('Не указан review_id', 422);
        }
        
        $voteType = $input['vote_type'] ?? null;
        if (empty($voteType) || !in_array($voteType, ['up', 'down'], true)) {
            sendError('vote_type должен быть "up" или "down"', 422);
        }
        
        $voteId = $vote->vote($userId, $input['review_id'], $voteType);
        
        sendSuccess(['vote_id' => $voteId], 'Голос учтен');
    }
    
    // DELETE - отменить голос
    if ($method === 'DELETE') {
        $reviewId = $_GET['review_id'] ?? null;
        
        if (!$reviewId) {
            sendError('Не указан review_id', 400);
        }
        
        $vote->removeVote($userId, $reviewId);
        
        sendSuccess([], 'Голос отменен');
    }
    
    sendError('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}
