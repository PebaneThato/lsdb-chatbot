<?php
/**
 * CORS (Cross-Origin Resource Sharing) configuration
 * Handles preflight requests and sets appropriate headers
 */

/**
 * Set CORS headers for API requests
 * @param array $allowed_origins List of allowed origins
 * @param array $allowed_methods List of allowed HTTP methods
 * @param array $allowed_headers List of allowed headers
 */
function setCorsHeaders(
    $allowed_origins = ['http://localhost:4200', 'http://127.0.0.1:4200'],
    $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    $allowed_headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin']
) {
    // Handle origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];
        
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Log unauthorized origin attempts
            error_log("CORS: Unauthorized origin attempted: $origin");
        }
    } else {
        // For development, allow localhost
        header("Access-Control-Allow-Origin: http://localhost:4200");
    }
    
    // Set other CORS headers
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowed_methods));
    header('Access-Control-Allow-Headers: ' . implode(', ', $allowed_headers));
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // Additional headers for preflight
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            $requested_method = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
            if (in_array($requested_method, $allowed_methods)) {
                header("Access-Control-Allow-Methods: $requested_method");
            }
        }
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $requested_headers = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
            // Validate requested headers
            $requested_headers_array = array_map('trim', explode(',', $requested_headers));
            $valid_headers = array_intersect($requested_headers_array, $allowed_headers);
            
            if (!empty($valid_headers)) {
                header("Access-Control-Allow-Headers: " . implode(', ', $valid_headers));
            }
        }
        
        // Send success response for preflight
        http_response_code(200);
        exit(0);
    }
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' http://localhost:4200");
}

/**
 * Check if request is from allowed origin
 * @param array $allowed_origins
 * @return bool
 */
function isOriginAllowed($allowed_origins = ['http://localhost:4200', 'http://127.0.0.1:4200']) {
    if (!isset($_SERVER['HTTP_ORIGIN'])) {
        return false;
    }
    
    return in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins);
}

/**
 * Log CORS violations
 * @param string $violation_type
 * @param string $details
 */
function logCorsViolation($violation_type, $details = '') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $violation_type,
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log("CORS Violation: " . json_encode($log_entry));
}
?>