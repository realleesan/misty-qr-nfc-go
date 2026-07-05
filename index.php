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
    logScan($code, $_SERVER['HTTP_USER_AGENT'], getClientIP());
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $qrData['redirect_url']);
    exit;
}

if ($qrData && empty($qrData['redirect_url'])) {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $venue = htmlspecialchars($qrData['venue_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $codeEscaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR chưa được cấu hình</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, sans-serif; background: #f6f7fb; color: #1f2937; }
    .card { max-width: 480px; margin: 80px auto; padding: 24px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    h1 { font-size: 20px; margin-bottom: 8px; }
    p { color: #4b5563; line-height: 1.6; margin-bottom: 16px; }
    .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #fff7ed; color: #b45309; font-size: 13px; font-weight: 600; margin-bottom: 12px; }
    .code { background: #f3f4f6; padding: 8px 12px; border-radius: 8px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; display: inline-block; margin-bottom: 12px; }
    .note { color: #6b7280; font-size: 13px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="badge">Chưa cấu hình</div>
    <h1>QR này chưa được cấu hình địa chỉ</h1>
    <p>Mã QR <span class="code">{$codeEscaped}</span> đang ở trạng thái trống.<br>Vui lòng liên hệ chủ cửa hàng để được hướng dẫn.</p>
    <p class="note">Nếu bạn là quản lý, hãy mở trang quản trị và nhập đường dẫn mục tiêu cho QR này.</p>
  </div>
</body>
</html>
HTML;
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QR không hợp lệ</title></head><body><h1>Mã QR không hợp lệ</h1><p>Mã QR không tồn tại trong hệ thống.</p></body></html>';
exit;
