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
    $username = $_SESSION['username'];

    // Cek apakah barcode ada di collecting milik user ini
    $query = "SELECT * FROM barcode_produk 
              WHERE kode_barcode = ? AND status = ?";

    $status_collecting = "di_collecting - " . $username;
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $kode_barcode, $status_collecting);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan di collecting Anda atau status tidak sesuai'
        ]);
        exit;
    }

    $barang = $result->fetch_assoc();

    // Return data barang
    echo json_encode([
        'status' => 'success',
        'message' => 'Barcode valid untuk dimasukkan ke toko',
        'data' => $barang
    ]);
} catch (Exception $e) {
    error_log("Error cek barcode masukan: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memeriksa barcode'
    ]);
}
