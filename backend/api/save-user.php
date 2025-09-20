<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required']);
        exit();
    }
    
    $name = trim($input['name']);
    $email = trim($input['email']);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // User exists, update name and last_active
        $update_query = "UPDATE users SET name = :name, last_active = NOW() WHERE email = :email";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':name', $name);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'User updated successfully',
            'user_id' => $check_stmt->fetch(PDO::FETCH_ASSOC)['id']
        ]);
    } else {
        // Insert new user
        $insert_query = "INSERT INTO users (name, email, created_at, last_active) VALUES (:name, :email, NOW(), NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':name', $name);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'User saved successfully',
            'user_id' => $db->lastInsertId()
        ]);
    }
    
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $exception->getMessage()]);
} catch(Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $exception->getMessage()]);
}
?>