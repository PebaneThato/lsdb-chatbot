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
    
    $query = "SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('contact_phone', 'contact_email')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $contact = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] == 'contact_phone') {
            $contact['phone'] = $row['setting_value'];
        } elseif ($row['setting_key'] == 'contact_email') {
            $contact['email'] = $row['setting_value'];
        }
    }
    
    // Default values if not found in database
    if (!isset($contact['phone'])) {
        $contact['phone'] = '+44 20 7123 4567';
    }
    if (!isset($contact['email'])) {
        $contact['email'] = 'info@lsdb.edu';
    }
    
    echo json_encode($contact);
    
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $exception->getMessage()]);
}
?>