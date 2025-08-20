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
$tarik_barcodes = $input['tarik_barcodes'] ?? [];

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

if (empty($tarik_barcodes)) {
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

    // Update status barang yang ditarik
    $placeholders = str_repeat('?,', count($tarik_barcodes) - 1) . '?';
    $status_toko = "di_toko - " . $nama_toko;
    $status_collecting = "di_collecting - " . $username;

    $update_query = "UPDATE barcode_produk 
                    SET status = ?, updated_at = NOW() 
                    WHERE status = ? 
                    AND kode_barcode IN ($placeholders)";

    $update_stmt = $conn->prepare($update_query);

    // Bind parameters
    $params = array_merge([$status_collecting, $status_toko], $tarik_barcodes);
    $types = str_repeat('s', count($params));

    $update_stmt->bind_param($types, ...$params);
    $update_stmt->execute();

    $barang_ditarik = $update_stmt->affected_rows;

    if ($barang_ditarik === 0) {
        throw new Exception("Tidak ada barang yang berhasil ditarik. Pastikan status barang sesuai.");
    }

    // Update stok_toko - kurangi jumlah berdasarkan barang yang ditarik
    // Group barang yang ditarik berdasarkan nama_barang
    $group_query = "SELECT nama_barang, COUNT(*) as jumlah_ditarik 
                   FROM barcode_produk 
                   WHERE status = ? 
                   AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                   AND kode_barcode IN ($placeholders)
                   GROUP BY nama_barang";

    $group_stmt = $conn->prepare($group_query);
    $group_params = array_merge([$status_collecting], $tarik_barcodes);
    $group_types = str_repeat('s', count($group_params));
    $group_stmt->bind_param($group_types, ...$group_params);
    $group_stmt->execute();
    $group_result = $group_stmt->get_result();

    while ($row = $group_result->fetch_assoc()) {
        $nama_barang = $row['nama_barang'];
        $jumlah_ditarik = $row['jumlah_ditarik'];

        // Update stok_toko
        $update_stok_query = "UPDATE stok_toko 
                             SET jumlah = GREATEST(0, jumlah - ?), 
                                 updated_by = ?,
                                 updated_at = NOW() 
                             WHERE id_toko = ? AND nama_barang = ?";

        $update_stok_stmt = $conn->prepare($update_stok_query);
        $update_stok_stmt->bind_param("isis", $jumlah_ditarik, $username, $id_toko, $nama_barang);
        $update_stok_stmt->execute();
    }

    // Log aktivitas - individual logging untuk setiap barang yang ditarik
    $check_log = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log->num_rows > 0) {
        // Get detail barang yang ditarik untuk logging individual
        $detail_query = "SELECT nama_barang, kode_barcode 
                        FROM barcode_produk 
                        WHERE status = ? 
                        AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                        AND kode_barcode IN ($placeholders)";

        $detail_stmt = $conn->prepare($detail_query);
        $detail_params = array_merge([$status_collecting], $tarik_barcodes);
        $detail_types = str_repeat('s', count($detail_params));
        $detail_stmt->bind_param($detail_types, ...$detail_params);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();

        $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES (?, ?, 'stok_toko', NOW())";
        $log_stmt = $conn->prepare($log_query);

        // Log setiap barang individual
        while ($detail = $detail_result->fetch_assoc()) {
            $aksi = "Menarik stok Toko {$nama_toko}: {$detail['nama_barang']} (Barcode: {$detail['kode_barcode']}) sebanyak 1 pcs";
            $log_stmt->bind_param("ss", $username, $aksi);
            $log_stmt->execute();
        }
    }

    // Commit transaksi
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Tarik barang berhasil',
        'data' => [
            'barang_ditarik' => $barang_ditarik,
            'total_dipilih' => count($tarik_barcodes)
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error proses tarik barang: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat menarik barang: ' . $e->getMessage()
    ]);
}
