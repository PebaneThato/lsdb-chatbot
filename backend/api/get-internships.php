<?php
// ===============================
// api/get-internships.php
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
    
    // Get internships with detailed information
    $query = "SELECT id, option_text as text, response_text, link_url as link, sort_order,
                     CASE WHEN link_url IS NOT NULL THEN 1 ELSE 0 END as has_link
              FROM chatbot_options 
              WHERE category = 'internships' AND is_active = 1 
              ORDER BY sort_order ASC, option_text ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        logError("Query failed for internships: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $internships = [];
    while ($row = $result->fetch_assoc()) {
        $internships[] = [
            'id' => $row['id'],
            'text' => $row['text'],
            'response_text' => $row['response_text'],
            'link' => $row['link'],
            'sort_order' => (int)$row['sort_order'],
            'has_link' => (bool)$row['has_link']
        ];
    }
    
    // Get internship statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_internships,
                        SUM(CASE WHEN link_url IS NOT NULL THEN 1 ELSE 0 END) as internships_with_links
                    FROM chatbot_options 
                    WHERE category = 'internships' AND is_active = 1";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : ['total_internships' => 0, 'internships_with_links' => 0];
    
    $result->free();
    if ($stats_result) $stats_result->free();
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'data' => $internships,
        'statistics' => [
            'total_internships' => (int)$stats['total_internships'],
            'internships_with_links' => (int)$stats['internships_with_links'],
            'returned_count' => count($internships)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Get internships error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>
