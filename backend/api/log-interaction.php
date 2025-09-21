<?php
// ===============================
// api/log-interaction.php
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
    
    // Sanitize all inputs
    $user_email = sanitizeInput($input['user_email'] ?? '');
    $interaction_type = sanitizeInput($input['interaction_type'] ?? 'general');
    $option_selected = sanitizeInput($input['option_selected'] ?? '');
    $user_message = sanitizeInput($input['user_message'] ?? '');
    $bot_response = sanitizeInput($input['bot_response'] ?? '');
    $session_id = sanitizeInput($input['session_id'] ?? session_id());
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Validate interaction type
    $valid_types = ['start_chat', 'option_select', 'course_inquiry', 'internship_inquiry', 'contact_request', 'restart', 'general'];
    if (!in_array($interaction_type, $valid_types)) {
        $interaction_type = 'general';
    }
    
    // Insert interaction with full details
    $query = "INSERT INTO chat_interactions 
              (user_email, interaction_type, option_selected, user_message, bot_response, 
               session_id, ip_address, user_agent, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        logError("Prepare failed for log interaction: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $stmt->bind_param("ssssssss", 
        $user_email, 
        $interaction_type, 
        $option_selected, 
        $user_message, 
        $bot_response, 
        $session_id, 
        $ip_address, 
        $user_agent
    );
    
    if (!$stmt->execute()) {
        logError("Execute failed for log interaction: " . $stmt->error);
        $stmt->close();
        sendErrorResponse('Database error occurred', 500);
    }
    
    $interaction_id = $conn->insert_id;
    $stmt->close();
    
    // Update user interaction count if user email provided
    if (!empty($user_email) && validateEmail($user_email)) {
        $update_query = "UPDATE users SET total_interactions = total_interactions + 1, last_active = NOW() WHERE email = ?";
        $update_stmt = $conn->prepare($update_query);
        
        if ($update_stmt) {
            $update_stmt->bind_param("s", $user_email);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'message' => 'Interaction logged successfully',
        'interaction_id' => (int)$interaction_id,
        'interaction_type' => $interaction_type,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Log interaction error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>