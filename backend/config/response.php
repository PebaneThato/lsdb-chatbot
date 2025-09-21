<?php
/**
 * Response handling utilities
 * Standardizes API responses and HTTP status codes
 */

/**
 * Send JSON response with proper headers
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 * @param array $headers Additional headers
 */
function sendJsonResponse($data, $statusCode = 200, $headers = []) {
    // Set HTTP status code
    http_response_code($statusCode);
    
    // Set content type
    header('Content-Type: application/json; charset=utf-8');
    
    // Set additional headers
    foreach ($headers as $header => $value) {
        header("$header: $value");
    }
    
    // Add timestamp to response if not present
    if (is_array($data) && !isset($data['timestamp'])) {
        $data['timestamp'] = date('Y-m-d H:i:s');
    }
    
    // Add request ID for tracking
    if (is_array($data) && !isset($data['request_id'])) {
        $data['request_id'] = uniqid('req_', true);
    }
    
    // Encode and send response
    $json_response = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json_response === false) {
        // Handle JSON encoding error
        http_response_code(500);
        $error_response = [
            'status' => 'error',
            'message' => 'Failed to encode response',
            'error_code' => 'JSON_ENCODE_ERROR',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        echo json_encode($error_response);
    } else {
        echo $json_response;
    }
    
    exit();
}

/**
 * Send error response
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @param string $errorCode Custom error code
 * @param array $details Additional error details
 */
function sendErrorResponse($message, $statusCode = 500, $errorCode = null, $details = []) {
    $error_response = [
        'status' => 'error',
        'message' => $message,
        'error_code' => $errorCode ?: 'GENERAL_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add details if provided
    if (!empty($details)) {
        $error_response['details'] = $details;
    }
    
    // Add debug info in development
    if (isDevelopmentMode()) {
        $error_response['debug'] = [
            'file' => debug_backtrace()[0]['file'] ?? 'unknown',
            'line' => debug_backtrace()[0]['line'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
    }
    
    sendJsonResponse($error_response, $statusCode);
}

/**
 * Send success response
 * @param mixed $data Response data
 * @param string $message Success message
 * @param int $statusCode HTTP status code
 */
function sendSuccessResponse($data = [], $message = 'Operation completed successfully', $statusCode = 200) {
    $response = [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
    
    sendJsonResponse($response, $statusCode);
}

/**
 * Send paginated response
 * @param array $data Response data
 * @param int $total Total number of items
 * @param int $page Current page
 * @param int $limit Items per page
 * @param string $message Success message
 */
function sendPaginatedResponse($data, $total, $page, $limit, $message = 'Data retrieved successfully') {
    $response = [
        'status' => 'success',
        'message' => $message,
        'data' => $data,
        'pagination' => [
            'total' => (int)$total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'pages' => (int)ceil($total / $limit),
            'has_next' => ($page * $limit) < $total,
            'has_prev' => $page > 1
        ]
    ];
    
    sendJsonResponse($response);
}

/**
 * Check if running in development mode
 * @return bool
 */
function isDevelopmentMode() {
    return (
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ||
        (defined('APP_ENV') === 'development') ||
        (getenv('APP_ENV') === 'development')
    );
}

/**
 * Validate request method
 * @param string|array $allowedMethods
 * @param bool $sendError Whether to send error response or return false
 * @return bool
 */
function validateRequestMethod($allowedMethods, $sendError = true) {
    if (is_string($allowedMethods)) {
        $allowedMethods = [$allowedMethods];
    }
    
    $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (!in_array($currentMethod, $allowedMethods)) {
        if ($sendError) {
            sendErrorResponse(
                'Method not allowed',
                405,
                'METHOD_NOT_ALLOWED',
                ['allowed_methods' => $allowedMethods, 'current_method' => $currentMethod]
            );
        }
        return false;
    }
    
    return true;
}

?>