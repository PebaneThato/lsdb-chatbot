<?php
// ===============================
// api/save-user.php
// ===============================
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/response.php';
require_once '../config/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['email'])) {
        sendErrorResponse('Name and email are required', 400);
    }
    
    $name = sanitizeInput($input['name']);
    $email = sanitizeInput($input['email']);
    
    if (!validateEmail($email)) {
        sendErrorResponse('Invalid email format', 400);
    }
    
    // Check if user already exists using MySQLi prepared statement
    $check_query = "SELECT id FROM users WHERE email = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    
    if (!$check_stmt) {
        logError("Prepare failed for user check: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $check_stmt->bind_param("s", $email);
    
    if (!$check_stmt->execute()) {
        logError("Execute failed for user check: " . $check_stmt->error);
        $check_stmt->close();
        sendErrorResponse('Database error occurred', 500);
    }
    
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists - update name and last_active
        $user_row = $result->fetch_assoc();
        $user_id = $user_row['id'];
        
        $update_query = "UPDATE users SET name = ?, last_active = NOW(), total_interactions = total_interactions + 1 WHERE email = ?";
        $update_stmt = $conn->prepare($update_query);
        
        if (!$update_stmt) {
            logError("Prepare failed for user update: " . $conn->error);
            $check_stmt->close();
            sendErrorResponse('Database error occurred', 500);
        }
        
        $update_stmt->bind_param("ss", $name, $email);
        
        if (!$update_stmt->execute()) {
            logError("Execute failed for user update: " . $update_stmt->error);
            $update_stmt->close();
            $check_stmt->close();
            sendErrorResponse('Database error occurred', 500);
        }
        
        $affected_rows = $conn->affected_rows;
        $update_stmt->close();
        
        logError("User updated: $email (ID: $user_id)");
        
    } else {
        // Insert new user
        $insert_query = "INSERT INTO users (name, email, created_at, last_active, total_interactions) VALUES (?, ?, NOW(), NOW(), 1)";
        $insert_stmt = $conn->prepare($insert_query);
        
        if (!$insert_stmt) {
            logError("Prepare failed for user insert: " . $conn->error);
            $check_stmt->close();
            sendErrorResponse('Database error occurred', 500);
        }
        
        $insert_stmt->bind_param("ss", $name, $email);
        
        if (!$insert_stmt->execute()) {
            logError("Execute failed for user insert: " . $insert_stmt->error);
            $insert_stmt->close();
            $check_stmt->close();
            sendErrorResponse('Database error occurred', 500);
        }
        
        $user_id = $conn->insert_id;
        $affected_rows = $conn->affected_rows;
        $insert_stmt->close();
        
        logError("New user created: $email (ID: $user_id)");
    }
    
    $check_stmt->close();
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'message' => 'User saved successfully',
        'user_id' => (int)$user_id,
        'affected_rows' => $affected_rows,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Save user error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>