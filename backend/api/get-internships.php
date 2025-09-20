<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, option_text as text, link_url as link FROM chatbot_options WHERE category = 'internships' AND is_active = 1 ORDER BY sort_order";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $internships = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $internships[] = [
            'id' => $row['id'],
            'text' => $row['text'],
            'link' => $row['link']
        ];
    }
    
    echo json_encode($internships);
    
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $exception->getMessage()]);
}
?>