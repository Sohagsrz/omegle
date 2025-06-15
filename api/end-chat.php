<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Database;
use App\Logger;

session_start();
header('Content-Type: application/json');

$logger = Logger::getInstance();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (! isset($input['session_id']) || ! isset($input['user_id'])) {
        throw new Exception('Missing required fields');
    }

    // Validate session
    if (session_id() !== $input['session_id']) {
        throw new Exception('Invalid session');
    }

    $db = new Database();

    // Find active chat for the user
    $stmt = $db->prepare("
        SELECT * FROM chats
        WHERE (user1_id = ? OR user2_id = ?)
        AND status = 'active'
        AND ended_at IS NULL
    ");
    $stmt->execute([$input['user_id'], $input['user_id']]);
    $chat = $stmt->fetch();

    if ($chat) {
        // End the chat
        $stmt = $db->prepare("
            UPDATE chats
            SET status = 'ended',
                ended_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$chat['id']]);

        // Log the chat end
        $logger->info('Chat ended', [
            'chat_id'  => $chat['id'],
            'user1_id' => $chat['user1_id'],
            'user2_id' => $chat['user2_id'],
            'ended_by' => $input['user_id'],
        ]);
    } else {
        // No active chat found, but that's okay
        $logger->info('No active chat to end', [
            'user_id' => $input['user_id'],
        ]);
    }

    // Clean up any waiting status
    $stmt = $db->prepare("
        DELETE FROM active_calls
        WHERE user_id = ?
        AND status = 'waiting'
    ");
    $stmt->execute([$input['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Chat ended successfully',
    ]);

} catch (Exception $e) {
    $logger->error('End chat failed', [
        'error' => $e->getMessage(),
        'input' => $input ?? null,
    ]);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
