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

    if (! isset($input['session_id']) || ! isset($input['user_id']) || ! isset($input['peer_id'])) {
        throw new Exception('Missing required fields');
    }

    // Validate session
    if (session_id() !== $input['session_id']) {
        throw new Exception('Invalid session');
    }

    $db = new Database();

    // Check if user is already in an active chat
    $stmt = $db->prepare("
        SELECT * FROM chats
        WHERE (user1_id = ? OR user2_id = ?)
        AND status = 'active'
        AND ended_at IS NULL
    ");
    $stmt->execute([$input['user_id'], $input['user_id']]);
    $activeChat = $stmt->fetch();

    if ($activeChat) {
        // If there's an active chat, end it first
        $stmt = $db->prepare("
            UPDATE chats
            SET status = 'ended',
                ended_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$activeChat['id']]);

        // Log the chat end
        $logger->info('Previous chat ended before starting new one', [
            'chat_id' => $activeChat['id'],
            'user_id' => $input['user_id'],
        ]);
    }

    // Find available peer who is waiting
    $stmt = $db->prepare("
        SELECT u.*, ac.peer_id
        FROM users u
        JOIN active_calls ac ON u.id = ac.user_id
        WHERE ac.status = 'waiting'
        AND u.id != ?
        AND NOT EXISTS (
            SELECT 1 FROM chats c
            WHERE (c.user1_id = u.id OR c.user2_id = u.id)
            AND c.status = 'active'
            AND c.ended_at IS NULL
        )
        LIMIT 1
    ");
    $stmt->execute([$input['user_id']]);
    $peer = $stmt->fetch();

    if ($peer) {
        // Match found - create new chat and update waiting status
        $db->beginTransaction();

        try {
            // Create new chat
            $stmt = $db->prepare("
                INSERT INTO chats (
                    user1_id,
                    user2_id,
                    user1_peer_id,
                    user2_peer_id,
                    status,
                    started_at
                ) VALUES (?, ?, ?, ?, 'active', datetime('now'))
            ");
            $stmt->execute([
                $input['user_id'],
                $peer['id'],
                $input['peer_id'],
                $peer['peer_id'],
            ]);

            // Clean up waiting statuses for both users
            $stmt = $db->prepare("
                DELETE FROM active_calls
                WHERE user_id IN (?, ?)
                AND status = 'waiting'
            ");
            $stmt->execute([$input['user_id'], $peer['id']]);

            $db->commit();

            // Log the match
            $logger->info('Users matched', [
                'user1_id' => $input['user_id'],
                'user2_id' => $peer['id'],
                'peer1_id' => $input['peer_id'],
                'peer2_id' => $peer['peer_id'],
            ]);

            echo json_encode([
                'success' => true,
                'peer'    => [
                    'id'      => $peer['id'],
                    'peer_id' => $peer['peer_id'],
                ],
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        // No match found - create waiting status
        // First, clean up any existing waiting status
        $stmt = $db->prepare("
            DELETE FROM active_calls
            WHERE user_id = ?
            AND status = 'waiting'
        ");
        $stmt->execute([$input['user_id']]);

        // Create new waiting status
        $stmt = $db->prepare("
            INSERT INTO active_calls (
                user_id,
                peer_id,
                status,
                created_at
            ) VALUES (?, ?, 'waiting', datetime('now'))
        ");
        $stmt->execute([$input['user_id'], $input['peer_id']]);

        // Log waiting status
        $logger->info('User waiting for match', [
            'user_id' => $input['user_id'],
            'peer_id' => $input['peer_id'],
        ]);

        echo json_encode([
            'success' => true,
            'status'  => 'waiting',
        ]);
    }

} catch (Exception $e) {
    $logger->error('Find peer failed', [
        'error' => $e->getMessage(),
        'input' => $input ?? null,
    ]);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
