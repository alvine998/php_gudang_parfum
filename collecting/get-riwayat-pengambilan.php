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

$username = $_SESSION['username'];

try {
    // Ambil riwayat pengambilan hari ini 
    $query = "SELECT 
                bp.kode_barcode,
                bp.nama_barang,
                bp.status,
                bp.updated_at as waktu
              FROM barcode_produk bp 
              WHERE bp.status LIKE ? 
              AND DATE(bp.updated_at) = CURDATE()
              ORDER BY bp.updated_at DESC 
              LIMIT 20";

    $status_pattern = "di_collecting - " . $username;
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status_pattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $riwayat = [];
    while ($row = $result->fetch_assoc()) {
        $riwayat[] = [
            'kode_barcode' => $row['kode_barcode'],
            'nama_barang' => $row['nama_barang'],
            'status' => $row['status'],
            'waktu' => date('H:i:s', strtotime($row['waktu']))
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $riwayat
    ]);
} catch (Exception $e) {
    error_log("Error dalam get-riwayat-pengambilan.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat mengambil riwayat'
    ]);
}

$conn->close();
