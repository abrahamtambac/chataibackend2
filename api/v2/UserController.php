<?php
class UserController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function sendResponse($status, $data = null, $message = '') {
        http_response_code($status);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function verifyJWT() {
        $headers = getallheaders();
        error_log("Headers: " . print_r($headers, true));
        if (!isset($headers['Authorization'])) {
            error_log("Missing Authorization header");
            $this->sendResponse(401, null, 'Token Bearer diperlukan');
        }
        if (!preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
            error_log("Invalid Authorization header format: " . $headers['Authorization']);
            $this->sendResponse(401, null, 'Token Bearer diperlukan');
        }

        $token = $matches[1];
        error_log("Token: $token");
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log("Invalid token structure: " . count($parts) . " parts");
            $this->sendResponse(401, null, 'Token tidak valid');
        }

        list($header, $payload, $signature) = $parts;
        $secret = getenv('JWT_SECRET');
        if (!$secret) {
            error_log("Missing JWT_SECRET");
            $this->sendResponse(500, null, 'Konfigurasi server salah: JWT_SECRET hilang');
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$payload", $secret, true));
        if ($signature !== $expectedSignature) {
            error_log("Invalid signature. Expected: $expectedSignature, Received: $signature");
            $this->sendResponse(401, null, 'Tanda tangan token tidak valid');
        }

        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        if (!$payloadData || !isset($payloadData['sub'], $payloadData['exp'])) {
            error_log("Invalid payload: " . print_r($payloadData, true));
            $this->sendResponse(401, null, 'Payload token tidak valid');
        }

        if ($payloadData['exp'] < time()) {
            error_log("Token expired at: " . $payloadData['exp']);
            $this->sendResponse(401, null, 'Token telah kadaluarsa');
        }

        // Verifikasi token dengan kolom token di tb_users
        $userId = $this->conn->real_escape_string($payloadData['sub']);
        $tokenEscaped = $this->conn->real_escape_string($token);
        $result = $this->conn->query("SELECT id FROM tb_users WHERE id = '$userId' AND token = '$tokenEscaped'");
        if (!$result || $result->num_rows == 0) {
            error_log("Token tidak ditemukan di database untuk user ID: $userId");
            $this->sendResponse(401, null, 'Token tidak valid atau tidak ditemukan di database');
        }

        error_log("JWT verified successfully for user: " . $payloadData['sub']);
        return $payloadData;
    }

    public function handleRequest($method, $id = null, $data = null) {
        try {
            // Verifikasi token untuk semua metode
            $payload = $this->verifyJWT();
            $userId = $payload['sub'];
            error_log("UserController - Method: $method, ID: " . ($id ?? 'null') . ", UserID: $userId");

            switch ($method) {
                case 'GET':
                    if ($id) {
                        $id = $this->conn->real_escape_string($id);
                        $result = $this->conn->query("SELECT id, name, email, role_id, profile_image, created_at, updated_at, last_seen, is_verified FROM tb_users WHERE id = '$id'");
                        if ($result && $result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $this->sendResponse(200, $user, 'Pengguna ditemukan');
                        } else {
                            $this->sendResponse(404, null, 'Pengguna tidak ditemukan');
                        }
                    } else {
                        $result = $this->conn->query("SELECT id, name, email, role_id, profile_image, created_at, updated_at, last_seen, is_verified FROM tb_users");
                        $users = [];
                        while ($row = $result->fetch_assoc()) {
                            $users[] = $row;
                        }
                        $this->sendResponse(200, $users, 'Daftar pengguna');
                    }
                    break;

                case 'POST':
                    if (!isset($data['name'], $data['email'], $data['password'], $data['role_id'])) {
                        $this->sendResponse(400, null, 'Data tidak lengkap');
                    }

                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $this->sendResponse(400, null, 'Email tidak valid');
                    }

                    $email = $this->conn->real_escape_string($data['email']);
                    $result = $this->conn->query("SELECT id FROM tb_users WHERE email = '$email'");
                    if ($result && $result->num_rows > 0) {
                        $this->sendResponse(409, null, 'Email sudah terdaftar');
                    }

                    $password = password_hash($data['password'], PASSWORD_BCRYPT);
                    $name = $this->conn->real_escape_string($data['name']);
                    $role_id = (int)$data['role_id'];
                    $profile_image = isset($data['profile_image']) ? $this->conn->real_escape_string($data['profile_image']) : null;
                    $is_verified = isset($data['is_verified']) ? (int)$data['is_verified'] : 0;

                    $query = "INSERT INTO tb_users (name, email, password, role_id, profile_image, is_verified, created_at, updated_at) 
                              VALUES ('$name', '$email', '$password', $role_id, " . ($profile_image ? "'$profile_image'" : "NULL") . ", $is_verified, NOW(), NOW())";
                    if ($this->conn->query($query)) {
                        $newId = $this->conn->insert_id;
                        $this->sendResponse(201, ['id' => $newId], 'Pengguna berhasil dibuat');
                    } else {
                        $this->sendResponse(500, null, 'Gagal membuat pengguna: ' . $this->conn->error);
                    }
                    break;

                case 'PUT':
                    if (!$id) {
                        $this->sendResponse(400, null, 'ID pengguna diperlukan');
                    }

                    if (empty($data)) {
                        $this->sendResponse(400, null, 'Data tidak boleh kosong');
                    }

                    $id = $this->conn->real_escape_string($id);
                    $result = $this->conn->query("SELECT id FROM tb_users WHERE id = '$id'");
                    if (!$result || $result->num_rows == 0) {
                        $this->sendResponse(404, null, 'Pengguna tidak ditemukan');
                    }

                    $fields = [];
                    if (isset($data['name'])) {
                        $fields[] = "name = '" . $this->conn->real_escape_string($data['name']) . "'";
                    }
                    if (isset($data['email'])) {
                        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $this->sendResponse(400, null, 'Email tidak valid');
                        }
                        $fields[] = "email = '" . $this->conn->real_escape_string($data['email']) . "'";
                    }
                    if (isset($data['password'])) {
                        $fields[] = "password = '" . password_hash($data['password'], PASSWORD_BCRYPT) . "'";
                    }
                    if (isset($data['role_id'])) {
                        $fields[] = "role_id = " . (int)$data['role_id'];
                    }
                    if (isset($data['profile_image'])) {
                        $profile_image = $this->conn->real_escape_string($data['profile_image']);
                        $fields[] = "profile_image = '$profile_image'";
                    }
                    if (isset($data['is_verified'])) {
                        $fields[] = "is_verified = " . (int)$data['is_verified'];
                    }
                    $fields[] = "updated_at = NOW()";

                    if (empty($fields)) {
                        $this->sendResponse(400, null, 'Tidak ada data untuk diperbarui');
                    }

                    $query = "UPDATE tb_users SET " . implode(', ', $fields) . " WHERE id = '$id'";
                    if ($this->conn->query($query)) {
                        $this->sendResponse(200, null, 'Pengguna berhasil diperbarui');
                    } else {
                        $this->sendResponse(500, null, 'Gagal memperbarui pengguna: ' . $this->conn->error);
                    }
                    break;

                case 'DELETE':
                    if (!$id) {
                        $this->sendResponse(400, null, 'ID pengguna diperlukan');
                    }

                    $id = $this->conn->real_escape_string($id);
                    $result = $this->conn->query("SELECT id FROM tb_users WHERE id = '$id'");
                    if (!$result || $result->num_rows == 0) {
                        $this->sendResponse(404, null, 'Pengguna tidak ditemukan');
                    }

                    $query = "DELETE FROM tb_users WHERE id = '$id'";
                    if ($this->conn->query($query)) {
                        $this->sendResponse(200, null, 'Pengguna berhasil dihapus');
                    } else {
                        $this->sendResponse(500, null, 'Gagal menghapus pengguna: ' . $this->conn->error);
                    }
                    break;

                default:
                    $this->sendResponse(405, null, 'Metode tidak diizinkan');
                    break;
            }
        } catch (Exception $e) {
            error_log("Error in UserController: " . $e->getMessage());
            $this->sendResponse(500, null, 'Kesalahan server: ' . $e->getMessage());
        }
    }
}
?>