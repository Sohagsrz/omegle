<?php
namespace App;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    private static $instance = null;
    private $logger;

    private function __construct()
    {
        $config   = Config::getInstance();
        $logPath  = $config->get('logging.path');
        $logLevel = $config->get('logging.level', 'debug');

        // Create log directory if it doesn't exist
        $logDir = dirname($logPath);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $this->logger = new MonologLogger('app');

        // Create a handler
        $handler = new StreamHandler($logPath, $this->getLogLevel($logLevel));

        // Create a formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        );
        $handler->setFormatter($formatter);

        // Add the handler to the logger
        $this->logger->pushHandler($handler);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getLogLevel($level)
    {
        $levels = [
            'debug'     => MonologLogger::DEBUG,
            'info'      => MonologLogger::INFO,
            'notice'    => MonologLogger::NOTICE,
            'warning'   => MonologLogger::WARNING,
            'error'     => MonologLogger::ERROR,
            'critical'  => MonologLogger::CRITICAL,
            'alert'     => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
        ];

        return $levels[strtolower($level)] ?? MonologLogger::DEBUG;
    }

    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->logger->warning($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->logger->critical($message, $context);
    }
}
