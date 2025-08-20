<?php
// Suppress notices and warnings that could break JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

session_start();
include '../koneksi.php';

// Cek akses admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Clear any output buffer and set headers
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Filter tanggal jika ada
    $where = [];
    $params = [];
    if (!empty($_GET['start'])) {
        $where[] = "DATE(updated_at) >= ?";
        $params[] = $_GET['start'];
    }
    if (!empty($_GET['end'])) {
        $where[] = "DATE(updated_at) <= ?";
        $params[] = $_GET['end'];
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $statistikQuery = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = '' OR status IS NULL THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'di_gudang' THEN 1 ELSE 0 END) as di_gudang,
                        SUM(CASE WHEN status LIKE 'di_spg%' THEN 1 ELSE 0 END) as di_spg,
                        SUM(CASE WHEN status LIKE 'di_toko%' THEN 1 ELSE 0 END) as di_toko,
                        SUM(CASE WHEN status LIKE 'di_collecting%' THEN 1 ELSE 0 END) as di_collecting,
                        SUM(CASE WHEN status = 'terjual' OR status LIKE 'terjual%' THEN 1 ELSE 0 END) as terjual
                       FROM barcode_produk $whereSql";

    if ($params) {
        $stmt = mysqli_prepare($conn, $statistikQuery);
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $statistikQuery);
    }

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
