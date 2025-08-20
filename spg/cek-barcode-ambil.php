<?php
session_start();
require_once '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
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
    $query = "SELECT bp.nama_barang, bp.kode_barcode, bp.status, bp.lokasi, bp.id 
              FROM barcode_produk bp 
              WHERE bp.kode_barcode = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $kode_barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan dalam database'
        ]);
        exit;
    }

    $data = $result->fetch_assoc();

    // Cek status barang
    if ($data['status'] !== 'di_gudang') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Barang tidak dapat diambil. Status saat ini: ' . $data['status'],
            'data' => $data
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Barcode ditemukan dan dapat diambil',
        'data' => $data
    ]);
} catch (Exception $e) {
    error_log("Error dalam cek-barcode-ambil.php: " . $e->getMessage());
    error_log("Kode barcode: " . $kode_barcode);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}

$conn->close();
