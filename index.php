<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Service URLs (will be updated with EC2 IPs later)
$services = [
    'auth'    => 'http://172.31.88.144:8001',  // Auth Service Private IP
    'student' => 'http://172.31.86.188:8002',  // Student Service Private IP
    'teacher' => 'http://172.31.88.49:8003'   // Teacher Service Private IP
];

function proxyRequest($url, $method, $headers = [], $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($httpCode);
    return $response;
}

// Route requests
if (strpos($path, '/auth/') === 0) {
    $targetPath = str_replace('/auth', '', $path);
    $url = $services['auth'] . $targetPath;

} elseif (strpos($path, '/student/') === 0) {
    $targetPath = str_replace('/student', '', $path);
    $url = $services['student'] . $targetPath;

} elseif (strpos($path, '/teacher/') === 0) {
    $targetPath = str_replace('/teacher', '', $path);
    $url = $services['teacher'] . $targetPath;

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Service not found']);
    exit;
}

// Forward headers
$headers = [];
foreach (getallheaders() as $key => $value) {
    $headers[] = "$key: $value";
}

// Get request body
$data = file_get_contents('php://input');

echo proxyRequest($url, $method, $headers, $data);
?>
