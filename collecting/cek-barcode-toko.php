<?php
session_start();
require_once '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'collecting') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$kode_barcode = $_POST['kode_barcode'] ?? '';

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

try {
    // Cek barcode di database
    $query = "SELECT t.*, 
             (SELECT COALESCE(SUM(st.jumlah), 0) FROM stok_toko st WHERE st.id_toko = t.id) as total_stok 
             FROM toko t 
             WHERE t.nama_toko = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $kode_barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Toko tidak ditemukan dengan nama tersebut'
        ]);
        exit;
    }

    $toko = $result->fetch_assoc();

    // Return data toko
    echo json_encode([
        'status' => 'success',
        'message' => 'Toko ditemukan',
        'data' => $toko
    ]);
} catch (Exception $e) {
    error_log("Error cek barcode toko: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memeriksa barcode'
    ]);
}
