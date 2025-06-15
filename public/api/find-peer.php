<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database;

header('Content-Type: application/json');

try {
    $input     = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['sessionId'] ?? null;

    if (! $sessionId) {
        throw new Exception('Session ID is required');
    }

    $db = new Database();

    // Create or update current session
    $db->createSession($sessionId);
    $db->updateSessionStatus($sessionId, 'waiting');

    // Find a waiting peer
    $peer = $db->findWaitingPeer($sessionId);

    if ($peer) {
        // Update both sessions to 'chatting'
        $db->updateSessionStatus($sessionId, 'chatting');
        $db->updateSessionStatus($peer['session_id'], 'chatting');

        echo json_encode(['peerId' => $peer['session_id']]);
    } else {
        echo json_encode(['peerId' => null]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
