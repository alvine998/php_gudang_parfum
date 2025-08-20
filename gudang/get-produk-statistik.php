<?php
// Suppress notices and warnings that could break JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

session_start();
include '../koneksi.php';

// Cek akses admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'gudang') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Clear any output buffer and set headers
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Get count untuk setiap status
    $statistikQuery = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = '' OR status IS NULL THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'di_gudang' THEN 1 ELSE 0 END) as di_gudang,
                        SUM(CASE WHEN status LIKE 'di_spg%' THEN 1 ELSE 0 END) as di_spg,
                        SUM(CASE WHEN status LIKE 'di_toko%' THEN 1 ELSE 0 END) as di_toko,
                        SUM(CASE WHEN status LIKE 'di_collecting%' THEN 1 ELSE 0 END) as di_collecting,
                        SUM(CASE WHEN status = 'terjual' OR status LIKE 'terjual%' THEN 1 ELSE 0 END) as terjual
                       FROM barcode_produk";

    $result = mysqli_query($conn, $statistikQuery);

    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }

    $statistik = mysqli_fetch_assoc($result);

    echo json_encode([
        'status' => 'success',
        'data' => $statistik
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get-produk-statistik.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat mengambil statistik: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
