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

try {
    $username = $_SESSION['username'];

    // Get total barang yang ada di collecting milik user ini
    $query = "SELECT COUNT(*) as total_collecting 
              FROM barcode_produk 
              WHERE status = ?";

    $status_collecting = "di_collecting - " . $username;
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status_collecting);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_collecting = $result->fetch_assoc()['total_collecting'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Data collecting berhasil dimuat',
        'total_collecting' => intval($total_collecting)
    ]);
} catch (Exception $e) {
    error_log("Error get collecting data: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memuat data collecting'
    ]);
}
