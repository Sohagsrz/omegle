<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Database;
use App\Logger;

header('Content-Type: application/json');

$logger = Logger::getInstance();

try {
    // Get JSON input
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        throw new Exception('No input data received');
    }

    $logger->debug('Registration attempt', ['input' => $jsonInput]);

    $input = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    $logger->debug('Parsed input', ['data' => $input]);

    if (! isset($input['name']) || ! isset($input['dob']) || ! isset($input['gender'])) {
        throw new Exception('Missing required fields');
    }

    // Validate input
    if (empty($input['name']) || empty($input['dob']) || empty($input['gender'])) {
        throw new Exception('All fields are required');
    }

    // Validate name (alphanumeric and spaces only)
    if (! preg_match('/^[a-zA-Z0-9\s]{2,50}$/', $input['name'])) {
        throw new Exception('Name must be 2-50 characters long and contain only letters, numbers, and spaces');
    }

    // Validate date of birth
    $dob = new DateTime($input['dob']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    if ($age < 13) {
        throw new Exception('You must be at least 13 years old');
    }

    if ($age > 100) {
        throw new Exception('Invalid date of birth');
    }

    // Validate gender
    $validGenders = ['male', 'female', 'other'];
    if (! in_array($input['gender'], $validGenders)) {
        throw new Exception('Invalid gender');
    }

    // Create user in database
    $db = new Database();

    // Check if user already exists (by name and DOB)
    $stmt = $db->prepare("SELECT id FROM users WHERE name = ? AND dob = ?");
    $stmt->execute([$input['name'], $input['dob']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // User exists, log them in
        session_start();
        $_SESSION['user_id'] = $existingUser['id'];

        // Update last active timestamp
        $db->updateUserLastActive($existingUser['id']);

        // Log the login
        $logger->info('Existing user logged in', [
            'user_id' => $existingUser['id'],
            'name'    => $input['name'],
        ]);

        $response = [
            'success' => true,
            'user_id' => $existingUser['id'],
            'message' => 'Welcome back!',
        ];
        $logger->debug('Login response', $response);
        echo json_encode($response);
        exit;
    }

    // Create new user
    $stmt = $db->prepare("INSERT INTO users (name, dob, gender, created_at) VALUES (?, ?, ?, datetime('now'))");
    $stmt->execute([$input['name'], $input['dob'], $input['gender']]);

    $userId = $db->lastInsertId();

    // Start session and store user ID
    session_start();
    $_SESSION['user_id'] = $userId;

    // Log the registration
    $logger->info('New user registered', [
        'user_id' => $userId,
        'name'    => $input['name'],
        'dob'     => $input['dob'],
        'gender'  => $input['gender'],
    ]);

    $response = [
        'success' => true,
        'user_id' => $userId,
        'message' => 'Registration successful!',
    ];
    $logger->debug('Registration response', $response);
    echo json_encode($response);

} catch (Exception $e) {
    $logger->error('Registration failed', [
        'error' => $e->getMessage(),
        'input' => $input ?? null,
    ]);

    http_response_code(400);
    $response = [
        'success' => false,
        'error'   => $e->getMessage(),
    ];
    $logger->debug('Error response', $response);
    echo json_encode($response);
}
