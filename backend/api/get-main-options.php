<?php
// ===============================
// api/get-main-options.php
// ===============================
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/response.php';
require_once '../config/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    
     $query = "SELECT id, option_text as text, response_text 
              FROM chatbot_options 
              WHERE category = 'main' AND is_active = 1 
              ORDER BY sort_order ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        logError("Query failed for main options: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = [
            'id' => $row['id'],
            'text' => $row['text'],
            'response_text' => $row['response_text']
        ];
    }
    
    $result->free();
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'data' => $options,
        'count' => count($options),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Get main options error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>