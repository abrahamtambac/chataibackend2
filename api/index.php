<?php
// Mengatur header untuk JSON dan CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Menangani permintaan OPTIONS (untuk CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Memuat konfigurasi database
try {
    include_once '../config/db.php';
    if ($conn->connect_error) {
        throw new Exception('Gagal terhubung ke database: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Kesalahan server: ' . $e->getMessage(),
        'data' => null,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Mendapatkan metode HTTP
$method = $_SERVER['REQUEST_METHOD'];
error_log("HTTP Method: $method");

// Mendapatkan segmen URL dari REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'];
error_log("Full REQUEST_URI: $requestUri");
$basePath = '/chatai_back/api/index.php';
$pos = strpos($requestUri, $basePath);
if ($pos === false) {
    $basePath = '/api/index.php';
    $pos = strpos($requestUri, $basePath);
}
if ($pos === false) {
    sendResponse(400, null, 'Base path tidak ditemukan di REQUEST_URI: ' . $requestUri);
}
$path = substr($requestUri, $pos + strlen($basePath));
$path = trim($path, '/');
$request = $path ? explode('/', $path) : [];
$version = array_shift($request) ?? null;
$controllerName = null;
$id = null;

// Menangani controller
if ($version === 'v2' && !empty($request) && $request[0] === 'auth' && !empty($request[1]) && $request[1] === 'login') {
    $controllerName = 'auth';
} elseif (!empty($request)) {
    $controllerName = array_shift($request);
    $id = array_shift($request) ?? null;
} else {
    $controllerName = array_shift($request) ?? null;
}

// Debugging: Log parsed values
error_log("Parsed - Version: $version, Controller: $controllerName, ID: " . ($id ?? 'null'));

// Fungsi untuk mengirim respons JSON
function sendResponse($status, $data = null, $message = '') {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi versi API
$supportedVersions = ['v1', 'v2'];
if (!$version || !in_array($version, $supportedVersions)) {
    sendResponse(404, null, 'Versi API tidak ditemukan. Versi yang didukung: ' . implode(', ', $supportedVersions));
}

// Validasi controller
if (!$controllerName) {
    sendResponse(400, null, 'Controller tidak ditentukan');
}

// Normalisasi controllerName
$controllerMap = [
    'v1' => [
        'users' => 'User'
    ],
    'v2' => [
        'users' => 'User',
        'auth' => 'Auth'
    ]
];
if (!isset($controllerMap[$version][strtolower($controllerName)])) {
    sendResponse(404, null, "Controller '$controllerName' tidak didukung untuk versi $version");
}
$controllerName = $controllerMap[$version][strtolower($controllerName)];

// Memuat controller
$controllerFile = __DIR__ . "/$version/{$controllerName}Controller.php";
error_log("Trying to load controller: $controllerFile");
if (!file_exists($controllerFile)) {
    sendResponse(404, null, "Controller '{$controllerName}Controller' tidak ditemukan");
}

require_once $controllerFile;

// Membuat instance controller
$controllerClass = ucfirst($controllerName) . 'Controller';
if (!class_exists($controllerClass)) {
    sendResponse(500, null, "Kelas controller '{$controllerClass}' tidak valid");
}

try {
    $controller = new $controllerClass($conn);
} catch (Exception $e) {
    sendResponse(500, null, 'Gagal membuat instance controller: ' . $e->getMessage());
}

// Mendapatkan data untuk POST atau PUT
$data = null;
if (in_array($method, ['POST', 'PUT'])) {
    $headers = getallheaders();
    $contentType = $headers['Content-Type'] ?? 'unknown';
    error_log("Content-Type: $contentType");

    if (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        error_log("Raw input (JSON): $rawInput");
        if (empty($rawInput)) {
            sendResponse(400, null, 'Body permintaan kosong');
        }
        $data = json_decode($rawInput, true);
        if ($data === null && $rawInput !== '') {
            $jsonError = json_last_error_msg();
            error_log("JSON decode error: $jsonError");
            sendResponse(400, null, "Data JSON tidak valid: $jsonError, Raw input: $rawInput");
        }
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false || strpos($contentType, 'multipart/form-data') !== false) {
        $rawInput = file_get_contents('php://input');
        error_log("Raw input (Form): $rawInput");
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawInput, $data);
        } else {
            $data = $_POST;
        }
        error_log("Parsed form data: " . print_r($data, true));
        if (empty($data)) {
            sendResponse(400, null, 'Data form kosong');
        }
    } else {
        sendResponse(400, null, 'Content-Type tidak didukung: ' . $contentType);
    }
}

// Validasi metode HTTP yang didukung
$supportedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
if (!in_array($method, $supportedMethods)) {
    sendResponse(405, null, 'Metode HTTP tidak diizinkan. Metode yang didukung: ' . implode(', ', $supportedMethods));
}

// Menangani permintaan
try {
    $controller->handleRequest($method, $id, $data);
} catch (Exception $e) {
    sendResponse(500, null, 'Kesalahan saat memproses permintaan: ' . $e->getMessage());
}
?>