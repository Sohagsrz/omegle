<?php

class Database
{
    private $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . '/../database/chat.db');
        $this->initializeDatabase();
    }

    private function initializeDatabase()
    {
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        $this->db->exec($schema);
    }

    public function createSession($sessionId)
    {
        $stmt = $this->db->prepare('INSERT INTO sessions (session_id) VALUES (:session_id)');
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function endSession($sessionId)
    {
        $stmt = $this->db->prepare('UPDATE sessions SET ended_at = CURRENT_TIMESTAMP, status = "ended" WHERE session_id = :session_id');
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function logMessage($sessionId, $message, $senderId)
    {
        $stmt = $this->db->prepare('INSERT INTO chat_logs (session_id, message, sender_id) VALUES (:session_id, :message, :sender_id)');
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->bindValue(':sender_id', $senderId, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function findWaitingPeer($excludeSessionId)
    {
        $stmt = $this->db->prepare('
            SELECT session_id
            FROM sessions
            WHERE status = "waiting"
            AND session_id != :exclude_session_id
            ORDER BY created_at ASC
            LIMIT 1
        ');
        $stmt->bindValue(':exclude_session_id', $excludeSessionId, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function updateSessionStatus($sessionId, $status)
    {
        $stmt = $this->db->prepare('UPDATE sessions SET status = :status WHERE session_id = :session_id');
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        return $stmt->execute();
    }
}
