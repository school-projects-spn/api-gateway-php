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

// Service URLs (updated to use public IPs of each EC2 instance)
$services = [
    'auth'    => 'http://54.145.160.225:8001',  // AuthService Public IP
    'student' => 'http://18.212.20.27:8002',    // StudentService Public IP
    'teacher' => 'http://3.95.21.60:8003'       // TeacherService Public IP
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
