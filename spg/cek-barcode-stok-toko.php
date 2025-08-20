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
$id_toko = $_POST['id_toko'] ?? 0;

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

if ($id_toko == 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

try {
    // Cek barcode di database
    $query = "SELECT bp.nama_barang, bp.kode_barcode, bp.status, bp.id 
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

    // Ambil username dari session untuk validasi
    $current_username = $_SESSION['username'];

    // Cek stok toko saat ini
    $stok_query = "SELECT jumlah FROM stok_toko WHERE id_toko = ? AND nama_barang = ?";
    $stok_stmt = $conn->prepare($stok_query);
    $stok_stmt->bind_param("is", $id_toko, $data['nama_barang']);
    $stok_stmt->execute();
    $stok_result = $stok_stmt->get_result();

    $stok_toko = 0;
    if ($stok_result->num_rows > 0) {
        $stok_row = $stok_result->fetch_assoc();
        $stok_toko = $stok_row['jumlah'];
    }

    // Tambahkan informasi stok toko dan username ke data
    $data['stok_toko'] = $stok_toko;
    $data['current_username'] = $current_username;

    echo json_encode([
        'status' => 'success',
        'message' => 'Barcode ditemukan',
        'data' => $data
    ]);
} catch (Exception $e) {
    error_log("Error dalam cek-barcode-stok-toko.php: " . $e->getMessage());
    error_log("Kode barcode: " . $kode_barcode);
    error_log("ID Toko: " . $id_toko);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}

$conn->close();
