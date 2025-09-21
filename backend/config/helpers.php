<?php
/**
 * Helper functions for data validation, sanitization, and utility operations
 */

/**
 * Sanitize input data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    
    // Remove whitespace from beginning and end
    $data = trim($data);
    
    // Remove backslashes
    $data = stripslashes($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    if (empty($email)) {
        return false;
    }
    
    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Additional checks
    $email_parts = explode('@', $email);
    if (count($email_parts) !== 2) {
        return false;
    }
    
    $domain = $email_parts[1];
    
    // Check if domain has MX record (optional, requires internet)
    // if (!checkdnsrr($domain, 'MX')) {
    //     return false;
    // }
    
    return true;
}

/**
 * Validate phone number
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return false;
    }
    
    // Remove all non-digit characters
    $phone_digits = preg_replace('/[^0-9]/', '', $phone);
    
    // Check length (international format: 7-15 digits)
    $length = strlen($phone_digits);
    return $length >= 7 && $length <= 15;
}

/**
 * Validate URL
 * @param string $url URL to validate
 * @return bool True if valid, false otherwise
 */
function validateUrl($url) {
    if (empty($url)) {
        return false;
    }
    
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Generate secure random string
 * @param int $length Length of the string
 * @param string $characters Characters to use
 * @return string Random string
 */
function generateRandomString($length = 10, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Log error message with context
 * @param string $message Error message
 * @param array $context Additional context
 * @param string $level Log level
 */
function logError($message, $context = [], $level = 'ERROR') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'message' => $message,
        'file' => debug_backtrace()[0]['file'] ?? 'unknown',
        'line' => debug_backtrace()[0]['line'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => getRealIpAddress()
    ];
    
    if (!empty($context)) {
        $log_entry['context'] = $context;
    }
    
    error_log(json_encode($log_entry));
}

/**
 * Get real IP address
 * @return string IP address
 */
function getRealIpAddress() {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Handle comma-separated IPs
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Format file size
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Check if string is JSON
 * @param string $string String to check
 * @return bool True if valid JSON, false otherwise
 */
function isJson($string) {
    if (!is_string($string)) {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Convert array to CSV string
 * @param array $data Array data
 * @param array $headers CSV headers
 * @return string CSV string
 */
function arrayToCsv($data, $headers = []) {
    if (empty($data)) {
        return '';
    }
    
    $output = '';
    
    // Add headers if provided
    if (!empty($headers)) {
        $output .= implode(',', array_map('trim', $headers)) . "\n";
    }
    
    // Add data rows
    foreach ($data as $row) {
        if (is_array($row)) {
            $escaped_row = array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row);
            $output .= implode(',', $escaped_row) . "\n";
        }
    }
    
    return $output;
}

/**
 * Rate limiting check
 * @param string $identifier Unique identifier (IP, user ID, etc.)
 * @param int $limit Number of requests allowed
 * @param int $window Time window in seconds
 * @param string $prefix Cache key prefix
 * @return bool True if request is allowed, false otherwise
 */
function checkRateLimit($identifier, $limit = 60, $window = 3600, $prefix = 'rate_limit') {
    // Simple file-based rate limiting (in production, use Redis or Memcached)
    $cache_dir = sys_get_temp_dir() . '/api_cache';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_key = md5($prefix . '_' . $identifier);
    $cache_file = $cache_dir . '/' . $cache_key;
    
    $current_time = time();
    $requests = [];
    
    // Load existing requests
    if (file_exists($cache_file)) {
        $content = file_get_contents($cache_file);
        if ($content) {
            $requests = json_decode($content, true) ?: [];
        }
    }
    
    // Remove old requests outside the window
    $requests = array_filter($requests, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    // Check if limit exceeded
    if (count($requests) >= $limit) {
        return false;
    }
    
    // Add current request
    $requests[] = $current_time;
    
    // Save updated requests
    file_put_contents($cache_file, json_encode($requests));
    
    return true;
}

/**
 * Clean old cache files
 * @param string $cache_dir Cache directory
 * @param int $max_age Maximum age in seconds
 */
function cleanupCache($cache_dir = null, $max_age = 86400) {
    if (!$cache_dir) {
        $cache_dir = sys_get_temp_dir() . '/api_cache';
    }
    
    if (!is_dir($cache_dir)) {
        return;
    }
    
    $files = glob($cache_dir . '/*');
    $current_time = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($current_time - filemtime($file)) > $max_age) {
            unlink($file);
        }
    }
}

/**
 * Get client information
 * @return array Client information
 */
function getClientInfo() {
    return [
        'ip_address' => getRealIpAddress(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'unknown',
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Validate and sanitize pagination parameters
 * @param array $params Input parameters
 * @return array Sanitized pagination parameters
 */
function sanitizePagination($params) {
    $page = max(1, (int)($params['page'] ?? 1));
    $limit = max(1, min(100, (int)($params['limit'] ?? 20))); // Max 100 items per page
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

// Initialize error handling
if (!function_exists('handleShutdown')) {
    function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            logError("Fatal error: " . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ], 'FATAL');
        }
    }
    
    register_shutdown_function('handleShutdown');
}

?>