<?php
namespace App;

use Dotenv\Dotenv;

class Config
{
    private static $instance = null;
    private $config          = [];

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $this->config = [
            'db'       => [
                'path' => $_ENV['DB_PATH'] ?? 'database/chat.db',
            ],
            'app'      => [
                'env'   => $_ENV['APP_ENV'] ?? 'development',
                'debug' => $_ENV['APP_DEBUG'] ?? true,
                'url'   => $_ENV['APP_URL'] ?? 'http://localhost',
            ],
            'security' => [
                'jwt_secret'     => $_ENV['JWT_SECRET'] ?? 'your-secret-key-here',
                'jwt_expiration' => $_ENV['JWT_EXPIRATION'] ?? 3600,
            ],
            'logging'  => [
                'path'  => $_ENV['LOG_PATH'] ?? 'logs/app.log',
                'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
            ],
            'peerjs'   => [
                'host' => $_ENV['PEERJS_HOST'] ?? '0.peerjs.com',
                'port' => $_ENV['PEERJS_PORT'] ?? 443,
                'path' => $_ENV['PEERJS_PATH'] ?? '/',
            ],
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key, $default = null)
    {
        $keys  = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (! isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
