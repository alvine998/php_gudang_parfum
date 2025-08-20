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
$id_toko = $_POST['id_toko'] ?? 0;
$scanned_barcodes_json = $_POST['scanned_barcodes'] ?? '[]';

// Parse JSON string ke array
$scanned_barcodes = json_decode($scanned_barcodes_json, true);
if (!is_array($scanned_barcodes)) {
    $scanned_barcodes = [];
}

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

// Validasi barcode duplikasi
if (in_array($kode_barcode, $scanned_barcodes)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Barcode sudah pernah discan sebelumnya'
    ]);
    exit;
}

try {
    // Cek apakah barcode ada di toko dan memiliki status di_toko
    $query = "SELECT bp.*, t.nama_toko 
              FROM barcode_produk bp 
              JOIN toko t ON bp.status LIKE CONCAT('di_toko - ', t.nama_toko)
              WHERE bp.kode_barcode = ? AND t.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $kode_barcode, $id_toko);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan di toko ini atau status tidak sesuai'
        ]);
        exit;
    }

    $barang = $result->fetch_assoc();

    // Return data barang
    echo json_encode([
        'status' => 'success',
        'message' => 'Barcode valid',
        'data' => $barang
    ]);
} catch (Exception $e) {
    error_log("Error cek barcode update: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memeriksa barcode'
    ]);
}
