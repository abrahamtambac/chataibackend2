<?php
class UserController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Fungsi untuk mengirim respons JSON
    private function sendResponse($status, $data, $message = '') {
        http_response_code($status);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Menangani permintaan berdasarkan metode HTTP
    public function handleRequest($method, $id = null, $data = null) {
        try {
            switch ($method) {
                case 'GET':
                    if ($id) {
                        // Mengambil satu pengguna berdasarkan ID
                        $id = $this->conn->real_escape_string($id);
                        $result = $this->conn->query("SELECT id, name, email, role_id, profile_image, created_at, updated_at, last_seen, is_verified FROM tb_users WHERE id = '$id'");
                        if ($result && $result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $this->sendResponse(200, $user, 'Pengguna ditemukan');
                        } else {
                            $this->sendResponse(404, null, 'Pengguna tidak ditemukan');
                        }
                    } else {
                        // Mengambil semua pengguna
                        $result = $this->conn->query("SELECT id, name, email, role_id, profile_image, created_at, updated_at, last_seen, is_verified FROM tb_users");
                        $users = [];
                        while ($row = $result->fetch_assoc()) {
                            $users[] = $row;
                        }
                        $this->sendResponse(200, $users, 'Daftar pengguna');
                    }
                    break;

                case 'POST':
                    // Membuat pengguna baru
                    if (!isset($data['name'], $data['email'], $data['password'], $data['role_id'])) {
                        $this->sendResponse(400, null, 'Data tidak lengkap');
                    }

                    // Validasi email
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $this->sendResponse(400, null, 'Email tidak valid');
                    }

                    // Cek apakah email sudah ada
                    $email = $this->conn->real_escape_string($data['email']);
                    $result = $this->conn->query("SELECT id FROM tb_users WHERE email = '$email'");
                    if ($result && $result->num_rows > 0) {
                        $this->sendResponse(409, null, 'Email sudah terdaftar');
                    }

                    // Hash password
                    $password = password_hash($data['password'], PASSWORD_BCRYPT);
                    $name = $this->conn->real_escape_string($data['name']);
                    $role_id = (int)$data['role_id'];
                    $profile_image = isset($data['profile_image']) ? $this->conn->real_escape_string($data['profile_image']) : null;
                    $is_verified = isset($data['is_verified']) ? (int)$data['is_verified'] : 0;

                    // Insert pengguna baru
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
                    // Memperbarui pengguna berdasarkan ID
                    if (!$id) {
                        $this->sendResponse(400, null, 'ID pengguna diperlukan');
                    }

                    if (empty($data)) {
                        $this->sendResponse(400, null, 'Data tidak boleh kosong');
                    }

                    // Cek apakah pengguna ada
                    $id = $this->conn->real_escape_string($id);
                    $result = $this->conn->query("SELECT id FROM tb_users WHERE id = '$id'");
                    if (!$result || $result->num_rows == 0) {
                        $this->sendResponse(404, null, 'Pengguna tidak ditemukan');
                    }

                    // Bangun query dinamis untuk update
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
                    // Menghapus pengguna berdasarkan ID
                    if (!$id) {
                        $this->sendResponse(400, null, 'ID pengguna diperlukan');
                    }

                    // Cek apakah pengguna ada
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
            $this->sendResponse(500, null, 'Kesalahan server: ' . $e->getMessage());
        }
    }
}
?>