<?php
// ===============================
// api/get-contact.php
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

    // Get contact information from app_settings
    $query = "SELECT setting_key, setting_value, description 
              FROM app_settings 
              WHERE setting_key IN ('contact_phone', 'contact_email', 'contact_address', 'contact_hours') 
              AND is_active = 1";
    
    $result = $conn->query($query);
    
    if (!$result) {
        logError("Query failed for contact info: " . $conn->error);
        sendErrorResponse('Database error occurred', 500);
    }
    
    $contact = [
        'phone' => '+44 20 7123 4567',
        'email' => 'info@lsdb.edu',
        'address' => 'London, UK',
        'hours' => 'Mon-Fri 9:00-17:00'
    ];
    
    $settings_found = [];
    while ($row = $result->fetch_assoc()) {
        $key = str_replace('contact_', '', $row['setting_key']);
        $contact[$key] = $row['setting_value'];
        $settings_found[] = $row['setting_key'];
    }
    
    // Get additional contact methods if available
    $additional_query = "SELECT setting_key, setting_value 
                        FROM app_settings 
                        WHERE setting_key LIKE 'contact_%' 
                        AND setting_key NOT IN ('contact_phone', 'contact_email', 'contact_address', 'contact_hours')
                        AND is_active = 1";
    
    $additional_result = $conn->query($additional_query);
    $additional_contacts = [];
    
    if ($additional_result) {
        while ($row = $additional_result->fetch_assoc()) {
            $key = str_replace('contact_', '', $row['setting_key']);
            $additional_contacts[$key] = $row['setting_value'];
        }
        $additional_result->free();
    }
    
    
    $result->free();
    $conn->close();
    
    sendJsonResponse([
        'status' => 'success',
        'data' => [
            'primary_contact' => $contact,
            'additional_contact' => $additional_contacts,
            'settings_loaded' => $settings_found
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $exception) {
    logError("Get contact error: " . $exception->getMessage());
    sendErrorResponse('Server error occurred', 500);
}
?>