<?php
/**
 * QR-NFC Redirect Service
 * Handles QR code resolution and redirects
 */

// Configuration
define('API_URL', getenv('API_URL') ?: 'https://qr-nfc-api.onrender.com');
define('LOG_SCANS', getenv('LOG_SCANS') ?: 'true');

// Enable error reporting in development
if (getenv('NODE_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Get QR code from API
 */
function resolveQRCode($code) {
    $url = API_URL . '/api/qr-nfc/resolve/' . urlencode($code);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Log scan event to API
 */
function logScan($code, $userAgent, $ip) {
    if (LOG_SCANS !== 'true') {
        return;
    }
    
    $url = API_URL . '/api/qr-nfc/resolve/scan/log';
    
    $data = [
        'code' => $code,
        'user_agent' => $userAgent,
        'ip_address' => $ip,
        'timestamp' => date('c')
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Get client IP
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

// Main logic
$code = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($code)) {
    // No code provided, redirect to review page
    header('Location: https://review.mistydev.id.vn');
    exit;
}

// Resolve QR code
$qrData = resolveQRCode($code);

if ($qrData && !empty($qrData['redirect_url'])) {
    // Log the scan
    logScan($code, $_SERVER['HTTP_USER_AGENT'], getClientIP());
    
    // Redirect to destination
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $qrData['redirect_url']);
    exit;
} else if ($qrData && empty($qrData['redirect_url'])) {
    // Valid QR code but not configured yet
    header('Location: https://review.mistydev.id.vn?code=' . urlencode($code) . '&status=not_configured&venue=' . urlencode($qrData['venue_name'] ?? ''));
    exit;
} else {
    // Invalid QR code
    header('Location: https://review.mistydev.id.vn?error=invalid_code');
    exit;
}
