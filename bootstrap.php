<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Logger;

// Initialize configuration
$config = Config::getInstance();

// Initialize logger
$logger = Logger::getInstance();

// Set error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
    $logger->error($errstr, [
        'errno' => $errno,
        'file'  => $errfile,
        'line'  => $errline,
    ]);
    return false;
});

// Set exception handler
set_exception_handler(function ($exception) use ($logger) {
    $logger->error($exception->getMessage(), [
        'file'  => $exception->getFile(),
        'line'  => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);
});
