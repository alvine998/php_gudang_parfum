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
    // Query produk terjual per bulan (12 bulan terakhir)
    $query = "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS bulan, COUNT(*) AS total_terjual
              FROM barcode_produk
              WHERE (status = 'terjual' OR status LIKE 'terjual%')
                AND updated_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 11 MONTH), '%Y-%m-01')
              GROUP BY bulan
              ORDER BY bulan ASC";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get-produk-terjual-bulanan.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
