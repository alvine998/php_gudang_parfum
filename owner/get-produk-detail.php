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
    $kodeBarcode = $_GET['kode_barcode'] ?? '';

    if (empty($kodeBarcode)) {
        echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
        exit;
    }

    // Get produk detail
    $produkQuery = "SELECT * FROM barcode_produk WHERE kode_barcode = ?";
    $produkStmt = mysqli_prepare($conn, $produkQuery);

    if (!$produkStmt) {
        throw new Exception("Prepare statement failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($produkStmt, 's', $kodeBarcode);

    if (!mysqli_stmt_execute($produkStmt)) {
        throw new Exception("Execute statement failed: " . mysqli_stmt_error($produkStmt));
    }

    $produkResult = mysqli_stmt_get_result($produkStmt);

    if (mysqli_num_rows($produkResult) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    $produk = mysqli_fetch_assoc($produkResult);

    // Get history dari log_aktivitas
    $historyQuery = "SELECT username, aksi, waktu 
                     FROM log_aktivitas 
                     WHERE aksi LIKE ?
                     ORDER BY waktu DESC 
                     LIMIT 50";

    $searchBarcode = "%$kodeBarcode%";

    $historyStmt = mysqli_prepare($conn, $historyQuery);

    if (!$historyStmt) {
        throw new Exception("Prepare history statement failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($historyStmt, 's', $searchBarcode);

    if (!mysqli_stmt_execute($historyStmt)) {
        throw new Exception("Execute history statement failed: " . mysqli_stmt_error($historyStmt));
    }

    $historyResult = mysqli_stmt_get_result($historyStmt);

    $history = [];
    while ($row = mysqli_fetch_assoc($historyResult)) {
        // Filter hanya log yang benar-benar terkait dengan kode barcode ini
        if (strpos($row['aksi'], $kodeBarcode) !== false) {
            $history[] = [
                'username' => $row['username'],
                'aksi' => $row['aksi'],
                'waktu' => $row['waktu']
            ];
        }
    }

    // Statistik sederhana
    $statistik = [
        'total_aktivitas' => count($history)
    ];

    $response = [
        'status' => 'success',
        'data' => [
            'produk' => $produk,
            'history' => $history,
            'statistik' => $statistik
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get-produk-detail.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat mengambil detail produk: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
