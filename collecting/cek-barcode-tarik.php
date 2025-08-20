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

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

try {
    // Get nama toko
    $toko_query = "SELECT nama_toko FROM toko WHERE id = ?";
    $toko_stmt = $conn->prepare($toko_query);
    $toko_stmt->bind_param("i", $id_toko);
    $toko_stmt->execute();
    $toko_result = $toko_stmt->get_result();

    if ($toko_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Toko tidak ditemukan']);
        exit;
    }

    $toko = $toko_result->fetch_assoc();
    $nama_toko = $toko['nama_toko'];

    // Cek apakah barcode ada di toko dan memiliki status di_toko
    $query = "SELECT * FROM barcode_produk 
              WHERE kode_barcode = ? AND status = ?";

    $status_toko = "di_toko - " . $nama_toko;
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $kode_barcode, $status_toko);
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
        'message' => 'Barcode valid untuk ditarik',
        'data' => $barang
    ]);
} catch (Exception $e) {
    error_log("Error cek barcode tarik: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memeriksa barcode'
    ]);
}
