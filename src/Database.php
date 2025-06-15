<?php
namespace App;

use PDO;

class Database
{
    private $pdo;
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
        $config       = Config::getInstance();

        $dbPath = $config->get('db.path');
        $dbPath = realpath(__DIR__ . '/../' . $dbPath);

        if (! $dbPath) {
            throw new \Exception("Database file not found at: " . __DIR__ . '/../' . $config->get('db.path'));
        }

        $this->pdo = new PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->initializeDatabase();

        $this->logger->info('Database initialized', ['path' => $dbPath]);
    }

    private function initializeDatabase()
    {
        try {
            $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
            $this->pdo->exec($schema);
            $this->logger->info('Database schema initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize database schema', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function query($sql)
    {
        return $this->pdo->query($sql);
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function logUserActivity($userId, $action, $details = null)
    {
        $stmt = $this->prepare("
            INSERT INTO user_activity (user_id, action, details, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ");
        return $stmt->execute([$userId, $action, $details]);
    }

    public function updateUserLastActive($userId)
    {
        $stmt = $this->prepare("
            UPDATE users
            SET last_active = datetime('now')
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function saveChatMessage($callId, $senderId, $message)
    {
        $stmt = $this->prepare("
            INSERT INTO chat_messages (call_id, sender_id, message, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ");
        return $stmt->execute([$callId, $senderId, $message]);
    }

    public function getChatHistory($callId)
    {
        $stmt = $this->prepare("
            SELECT cm.*, u.name as sender_name
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.call_id = ?
            ORDER BY cm.created_at ASC
        ");
        $stmt->execute([$callId]);
        return $stmt->fetchAll();
    }

    public function createSession($sessionId)
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO sessions (session_id) VALUES (:session_id)');
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $result = $stmt->execute();
            $this->logger->info('Session created', ['session_id' => $sessionId]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create session', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function endSession($sessionId)
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE sessions SET ended_at = CURRENT_TIMESTAMP, status = "ended" WHERE session_id = :session_id');
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $result = $stmt->execute();
            $this->logger->info('Session ended', ['session_id' => $sessionId]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to end session', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function logMessage($sessionId, $message, $senderId)
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO chat_logs (session_id, message, sender_id) VALUES (:session_id, :message, :sender_id)');
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':sender_id', $senderId, PDO::PARAM_STR);
            $result = $stmt->execute();
            $this->logger->debug('Message logged', [
                'session_id' => $sessionId,
                'sender_id'  => $senderId,
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to log message', [
                'session_id' => $sessionId,
                'sender_id'  => $senderId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function findWaitingPeer($excludeSessionId)
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT session_id
                FROM sessions
                WHERE status = "waiting"
                AND session_id != :exclude_session_id
                ORDER BY created_at ASC
                LIMIT 1
            ');
            $stmt->bindValue(':exclude_session_id', $excludeSessionId, PDO::PARAM_STR);
            $result = $stmt->execute();
            $peer   = $result->fetch(PDO::FETCH_ASSOC);

            if ($peer) {
                $this->logger->info('Found waiting peer', [
                    'session_id' => $excludeSessionId,
                    'peer_id'    => $peer['session_id'],
                ]);
            } else {
                $this->logger->debug('No waiting peers found', ['session_id' => $excludeSessionId]);
            }

            return $peer;
        } catch (\Exception $e) {
            $this->logger->error('Failed to find waiting peer', [
                'session_id' => $excludeSessionId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateSessionStatus($sessionId, $status)
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE sessions SET status = :status WHERE session_id = :session_id');
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $result = $stmt->execute();
            $this->logger->info('Session status updated', [
                'session_id' => $sessionId,
                'status'     => $status,
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update session status', [
                'session_id' => $sessionId,
                'status'     => $status,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
