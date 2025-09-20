<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_email = $input['user_email'] ?? '';
    $interaction_type = $input['interaction_type'] ?? '';
    $option_selected = $input['option_selected'] ?? '';
    $user_message = $input['user_message'] ?? '';
    $bot_response = $input['bot_response'] ?? '';
    
    $query = "INSERT INTO chat_interactions (user_email, interaction_type, option_selected, user_message, bot_response, created_at) 
              VALUES (:user_email, :interaction_type, :option_selected, :user_message, :bot_response, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_email', $user_email);
    $stmt->bindParam(':interaction_type', $interaction_type);
    $stmt->bindParam(':option_selected', $option_selected);
    $stmt->bindParam(':user_message', $user_message);
    $stmt->bindParam(':bot_response', $bot_response);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Interaction logged successfully'
    ]);
    
} catch(PDOException $exception) {
    http_response_code(500);
     echo json_encode(['error' => 'Database error: ' . $exception->getMessage()]);
}
?>