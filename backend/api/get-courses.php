<?php
// ===============================
// api/get-courses.php
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
    
    // Get courses with detailed information
    $query = "SELECT id, option_text as text, response_text, link_url as link, sort_order,
                     CASE WHEN link_url IS NOT NULL THEN 1 ELSE 0 END as has_link
              FROM chatbot_options 
              WHERE category = 'courses' AND is_active = 1 
              ORDER BY sort_order ASC, option_text ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        logError("Query failed for courses: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => $row['id'],
            'text' => $row['text'],
            'response_text' => $row['response_text'],
            'link' => $row['link'],
            'sort_order' => (int)$row['sort_order'],
            'has_link' => (bool)$row['has_link']
        ];
    }
    
    // Get course statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_courses,
                        SUM(CASE WHEN link_url IS NOT NULL THEN 1 ELSE 0 END) as courses_with_links
                    FROM chatbot_options 
                    WHERE category = 'courses' AND is_active = 1";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : ['total_courses' => 0, 'courses_with_links' => 0];
    
    $result->free();
    if ($stats_result) $stats_result->free();
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'data' => $courses,
        'statistics' => [
            'total_courses' => (int)$stats['total_courses'],
            'courses_with_links' => (int)$stats['courses_with_links'],
            'returned_count' => count($courses)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Get courses error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>