<?php
class AuthController {
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

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function generateJWT($userId, $email, $roleId) {
        $secret = getenv('JWT_SECRET');
        if (!$secret) {
            throw new Exception('JWT_SECRET tidak ditemukan di .env');
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $userId,
            'email' => $email,
            'role_id' => $roleId,
            'iat' => time(),
            'exp' => time() + 3600 // 1 jam
        ]));

        $signature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$payload", $secret, true));
        return "$header.$payload.$signature";
    }

    public function handleRequest($method, $id = null, $data = null) {
        try {
            error_log("AuthController - Method: $method, ID: " . ($id ?? 'null'));
            if ($method !== 'POST') {
                $this->sendResponse(405, null, 'Metode harus POST');
            }
            if ($id !== null) {
                $this->sendResponse(400, null, 'Endpoint tidak menerima ID');
            }

            if (!isset($data['email'], $data['password'])) {
                $this->sendResponse(400, null, 'Email dan password wajib diisi');
            }

            $email = $this->conn->real_escape_string($data['email']);
            $result = $this->conn->query("SELECT id, name, email, password, role_id, profile_image, created_at, updated_at, last_seen, is_verified FROM tb_users WHERE email = '$email'");
            if (!$result || $result->num_rows == 0) {
                $this->sendResponse(401, null, 'Email atau password salah');
            }

            $user = $result->fetch_assoc();
            if (!password_verify($data['password'], $user['password'])) {
                $this->sendResponse(401, null, 'Email atau password salah');
            }

            // Buat token JWT
            $token = $this->generateJWT($user['id'], $user['email'], $user['role_id']);
            
            // Simpan token ke kolom token di tb_users
            $userId = $this->conn->real_escape_string($user['id']);
            $tokenEscaped = $this->conn->real_escape_string($token);
            $query = "UPDATE tb_users SET token = '$tokenEscaped' WHERE id = '$userId'";
            if (!$this->conn->query($query)) {
                error_log("Gagal menyimpan token: " . $this->conn->error);
                $this->sendResponse(500, null, 'Gagal menyimpan token: ' . $this->conn->error);
            }

            // Siapkan data pengguna untuk respons (tanpa password)
            $userData = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'profile_image' => $user['profile_image'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
                'last_seen' => $user['last_seen'],
                'is_verified' => $user['is_verified'],
                'token' => $token
            ];

            error_log("Login berhasil untuk user ID: $userId, Token: $token");
            $this->sendResponse(200, $userData, 'Login berhasil');

        } catch (Exception $e) {
            error_log("Error in AuthController: " . $e->getMessage());
            $this->sendResponse(500, null, 'Kesalahan server: ' . $e->getMessage());
        }
    }
}
?>