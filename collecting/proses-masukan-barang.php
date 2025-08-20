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

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

$id_toko = $input['id_toko'] ?? 0;
$masukan_barcodes = $input['masukan_barcodes'] ?? [];

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

if (empty($masukan_barcodes)) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada barang yang dipilih']);
    exit;
}

try {
    // Mulai transaksi
    $conn->begin_transaction();

    // Get nama toko
    $toko_query = "SELECT nama_toko FROM toko WHERE id = ?";
    $toko_stmt = $conn->prepare($toko_query);
    $toko_stmt->bind_param("i", $id_toko);
    $toko_stmt->execute();
    $toko_result = $toko_stmt->get_result();

    if ($toko_result->num_rows === 0) {
        throw new Exception("Toko tidak ditemukan");
    }

    $toko = $toko_result->fetch_assoc();
    $nama_toko = $toko['nama_toko'];
    $username = $_SESSION['username'];

    // Update status barang yang dimasukan
    $placeholders = str_repeat('?,', count($masukan_barcodes) - 1) . '?';
    $status_collecting = "di_collecting - " . $username;
    $status_toko = "di_toko - " . $nama_toko;

    $update_query = "UPDATE barcode_produk 
                    SET status = ?, updated_at = NOW() 
                    WHERE status = ? 
                    AND kode_barcode IN ($placeholders)";

    $update_stmt = $conn->prepare($update_query);

    // Bind parameters
    $params = array_merge([$status_toko, $status_collecting], $masukan_barcodes);
    $types = str_repeat('s', count($params));

    $update_stmt->bind_param($types, ...$params);
    $update_stmt->execute();

    $barang_dimasukan = $update_stmt->affected_rows;

    if ($barang_dimasukan === 0) {
        throw new Exception("Tidak ada barang yang berhasil dimasukan. Pastikan status barang sesuai.");
    }

    // Update stok_toko - tambah jumlah berdasarkan barang yang dimasukan
    // Group barang yang dimasukan berdasarkan nama_barang
    $group_query = "SELECT nama_barang, COUNT(*) as jumlah_dimasukan 
                   FROM barcode_produk 
                   WHERE status = ? 
                   AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                   AND kode_barcode IN ($placeholders)
                   GROUP BY nama_barang";

    $group_stmt = $conn->prepare($group_query);
    $group_params = array_merge([$status_toko], $masukan_barcodes);
    $group_types = str_repeat('s', count($group_params));
    $group_stmt->bind_param($group_types, ...$group_params);
    $group_stmt->execute();
    $group_result = $group_stmt->get_result();

    while ($row = $group_result->fetch_assoc()) {
        $nama_barang = $row['nama_barang'];
        $jumlah_dimasukan = $row['jumlah_dimasukan'];

        // Cek apakah sudah ada record di stok_toko
        $check_query = "SELECT id FROM stok_toko WHERE id_toko = ? AND nama_barang = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("is", $id_toko, $nama_barang);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update stok yang sudah ada
            $update_stok_query = "UPDATE stok_toko 
                                 SET jumlah = jumlah + ?, 
                                     updated_by = ?,
                                     updated_at = NOW() 
                                 WHERE id_toko = ? AND nama_barang = ?";

            $update_stok_stmt = $conn->prepare($update_stok_query);
            $update_stok_stmt->bind_param("isis", $jumlah_dimasukan, $username, $id_toko, $nama_barang);
            $update_stok_stmt->execute();
        } else {
            // Insert stok baru
            $insert_stok_query = "INSERT INTO stok_toko (id_toko, nama_barang, jumlah, updated_by, updated_at) 
                                 VALUES (?, ?, ?, ?, NOW())";

            $insert_stok_stmt = $conn->prepare($insert_stok_query);
            $insert_stok_stmt->bind_param("isis", $id_toko, $nama_barang, $jumlah_dimasukan, $username);
            $insert_stok_stmt->execute();
        }
    }

    // Log aktivitas - Log setiap barang yang dimasukan secara individual
    $check_log = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log->num_rows > 0 && $barang_dimasukan > 0) {
        // Ambil detail barang yang baru saja dimasukan
        $placeholders = str_repeat('?,', count($masukan_barcodes) - 1) . '?';

        $masukan_detail_query = "SELECT nama_barang, kode_barcode 
                                FROM barcode_produk 
                                WHERE status = ? 
                                AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                                AND kode_barcode IN ($placeholders)";

        $masukan_detail_stmt = $conn->prepare($masukan_detail_query);
        $detail_params = array_merge([$status_toko], $masukan_barcodes);
        $detail_types = str_repeat('s', count($detail_params));
        $masukan_detail_stmt->bind_param($detail_types, ...$detail_params);
        $masukan_detail_stmt->execute();
        $masukan_detail_result = $masukan_detail_stmt->get_result();

        // Prepare statement untuk log
        $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES (?, ?, 'stok_toko', NOW())";
        $log_stmt = $conn->prepare($log_query);

        // Log setiap barang yang dimasukan dengan format yang sama seperti tambah stok SPG
        while ($masukan_item = $masukan_detail_result->fetch_assoc()) {
            $nama_barang = $masukan_item['nama_barang'];
            $kode_barcode = $masukan_item['kode_barcode'];

            $aksi = "Menambah stok {$nama_toko}: {$nama_barang} (Barcode: {$kode_barcode}) sebanyak 1 pcs";
            $log_stmt->bind_param("ss", $username, $aksi);
            $log_stmt->execute();
        }
    }

    // Commit transaksi
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Masukan barang berhasil',
        'data' => [
            'barang_dimasukan' => $barang_dimasukan,
            'total_dipilih' => count($masukan_barcodes)
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error proses masukan barang: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memasukkan barang: ' . $e->getMessage()
    ]);
}
