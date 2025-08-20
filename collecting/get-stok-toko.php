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

$id_toko = isset($_GET['id_toko']) ? intval($_GET['id_toko']) : 0;

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

    // Get stok toko dari tabel stok_toko
    $query = "SELECT st.*, DATE_FORMAT(st.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at 
              FROM stok_toko st 
              WHERE st.id_toko = ? 
              ORDER BY st.nama_barang ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_toko);
    $stmt->execute();
    $result = $stmt->get_result();

    $stok_list = [];
    $total_jumlah_stok_toko = 0;
    while ($row = $result->fetch_assoc()) {
        $stok_list[] = $row;
        $total_jumlah_stok_toko += intval($row['jumlah']);
    }

    // Get total barang yang ada di toko berdasarkan barcode (untuk update stok)
    $barcode_query = "SELECT COUNT(*) as total_barang 
                     FROM barcode_produk 
                     WHERE status = ?";
    $status_toko = "di_toko - " . $nama_toko;
    $barcode_stmt = $conn->prepare($barcode_query);
    $barcode_stmt->bind_param("s", $status_toko);
    $barcode_stmt->execute();
    $barcode_result = $barcode_stmt->get_result();
    $total_barang_aktual = $barcode_result->fetch_assoc()['total_barang'];

    // Log kunjungan - hanya sekali per hari per toko per user
    $username = $_SESSION['username'];
    $check_log = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log->num_rows > 0) {
        $aksi_check = "Melihat stok toko: " . $nama_toko;
        $tanggal_hari_ini = date('Y-m-d');

        // Cek apakah sudah ada log untuk hari ini
        $check_existing = "SELECT id FROM log_aktivitas 
                          WHERE username = ? 
                            AND aksi = ? 
                            AND DATE(waktu) = ? 
                          LIMIT 1";

        $check_stmt = $conn->prepare($check_existing);
        $check_stmt->bind_param("sss", $username, $aksi_check, $tanggal_hari_ini);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();

        // Hanya insert jika belum ada log untuk hari ini
        if ($existing_result->num_rows === 0) {
            $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                          VALUES (?, ?, 'toko', NOW())";

            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("ss", $username, $aksi_check);
            $log_stmt->execute();
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Data stok berhasil dimuat',
        'data' => $stok_list,
        'total_jumlah_stok_toko' => $total_jumlah_stok_toko,
        'total_barang_aktual' => intval($total_barang_aktual),
        'nama_toko' => $nama_toko
    ]);
} catch (Exception $e) {
    error_log("Error get stok toko: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memuat data stok'
    ]);
}
