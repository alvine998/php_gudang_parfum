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
$scanned_barcodes = $input['scanned_barcodes'] ?? [];

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
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

    // 1. Update barang yang TIDAK discan menjadi status 'terjual'
    if (!empty($scanned_barcodes)) {
        // Buat placeholder untuk IN clause
        $placeholders = str_repeat('?,', count($scanned_barcodes) - 1) . '?';

        $update_terjual_query = "UPDATE barcode_produk 
                                SET status = 'terjual', updated_at = NOW() 
                                WHERE status = ? 
                                AND kode_barcode NOT IN ($placeholders)";

        $update_terjual_stmt = $conn->prepare($update_terjual_query);

        // Bind parameters
        $status_toko = "di_toko - " . $nama_toko;
        $params = array_merge([$status_toko], $scanned_barcodes);
        $types = str_repeat('s', count($params));

        $update_terjual_stmt->bind_param($types, ...$params);
        $update_terjual_stmt->execute();

        $terjual_count = $update_terjual_stmt->affected_rows;
    } else {
        // Jika tidak ada yang discan, semua barang di toko menjadi terjual
        $update_all_query = "UPDATE barcode_produk 
                            SET status = 'terjual', updated_at = NOW() 
                            WHERE status = ?";

        $update_all_stmt = $conn->prepare($update_all_query);
        $status_toko = "di_toko - " . $nama_toko;
        $update_all_stmt->bind_param("s", $status_toko);
        $update_all_stmt->execute();

        $terjual_count = $update_all_stmt->affected_rows;
    }

    // 2. Update stok_toko - kurangi jumlah berdasarkan barang yang terjual
    if ($terjual_count > 0) {
        // Group barang yang terjual berdasarkan nama_barang
        $group_query = "SELECT nama_barang, COUNT(*) as jumlah_terjual 
                       FROM barcode_produk 
                       WHERE status = 'terjual' 
                       AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                       GROUP BY nama_barang";

        $group_result = $conn->query($group_query);

        while ($row = $group_result->fetch_assoc()) {
            $nama_barang = $row['nama_barang'];
            $jumlah_terjual = $row['jumlah_terjual'];

            // Update stok_toko
            $update_stok_query = "UPDATE stok_toko 
                                 SET jumlah = GREATEST(0, jumlah - ?), 
                                     updated_by = ?,
                                     updated_at = NOW() 
                                 WHERE id_toko = ? AND nama_barang = ?";

            $update_stok_stmt = $conn->prepare($update_stok_query);
            $update_stok_stmt->bind_param("isis", $jumlah_terjual, $username, $id_toko, $nama_barang);
            $update_stok_stmt->execute();
        }
    }

    // 3. Log aktivitas - Log setiap barang yang terjual secara individual
    $username = $_SESSION['username'];
    $check_log = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log->num_rows > 0 && $terjual_count > 0) {
        // Ambil detail barang yang baru saja diupdate menjadi terjual
        if (!empty($scanned_barcodes)) {
            // Query untuk barang yang TIDAK discan (yang menjadi terjual)
            $placeholders = str_repeat('?,', count($scanned_barcodes) - 1) . '?';

            $terjual_query = "SELECT nama_barang, kode_barcode 
                             FROM barcode_produk 
                             WHERE status = 'terjual' 
                             AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                             AND kode_barcode NOT IN ($placeholders)";

            $terjual_stmt = $conn->prepare($terjual_query);
            $types = str_repeat('s', count($scanned_barcodes));
            $terjual_stmt->bind_param($types, ...$scanned_barcodes);
        } else {
            // Query untuk semua barang yang menjadi terjual (jika tidak ada yang discan)
            $terjual_query = "SELECT nama_barang, kode_barcode 
                             FROM barcode_produk 
                             WHERE status = 'terjual' 
                             AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

            $terjual_stmt = $conn->prepare($terjual_query);
        }

        $terjual_stmt->execute();
        $terjual_result = $terjual_stmt->get_result();

        // Prepare statement untuk log
        $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES (?, ?, 'barcode_produk', NOW())";
        $log_stmt = $conn->prepare($log_query);

        // Log setiap barang yang terjual
        while ($terjual_item = $terjual_result->fetch_assoc()) {
            $nama_barang = $terjual_item['nama_barang'];
            $kode_barcode = $terjual_item['kode_barcode'];

            $aksi = "Barang terjual di {$nama_toko}: {$nama_barang} (Barcode: {$kode_barcode})";
            $log_stmt->bind_param("ss", $username, $aksi);
            $log_stmt->execute();

            // Insert ke laporan_stok_toko
            // Ambil id_barcode_produk
            $barcode_id = null;
            $barcode_stmt = $conn->prepare("SELECT id FROM barcode_produk WHERE kode_barcode = ? LIMIT 1");
            $barcode_stmt->bind_param("s", $kode_barcode);
            $barcode_stmt->execute();
            $barcode_stmt->bind_result($barcode_id);
            $barcode_stmt->fetch();
            $barcode_stmt->close();

            // Ambil id_stok_toko
            $stok_id = null;
            $stok_stmt = $conn->prepare("SELECT id FROM stok_toko WHERE id_toko = ? AND nama_barang = ? LIMIT 1");
            $stok_stmt->bind_param("is", $id_toko, $nama_barang);
            $stok_stmt->execute();
            $stok_stmt->bind_result($stok_id);
            $stok_stmt->fetch();
            $stok_stmt->close();

            if ($barcode_id && $stok_id) {
                $insert_laporan = $conn->prepare("INSERT INTO laporan_stok_toko (id_toko, id_barcode_produk, id_stok_toko, created_at) VALUES (?, ?, ?, NOW())");
                $insert_laporan->bind_param("iii", $id_toko, $barcode_id, $stok_id);
                $insert_laporan->execute();
            }
        }

        // Log summary untuk collecting
        $summary_aksi = "Update stok collecting {$nama_toko}: {$terjual_count} barang terjual, " . count($scanned_barcodes) . " barang masih tersedia";
        $log_stmt->bind_param("ss", $username, $summary_aksi);
        $log_stmt->execute();
    }

    // Commit transaksi
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Update stok berhasil',
        'data' => [
            'barang_terjual' => $terjual_count,
            'barang_tersisa' => count($scanned_barcodes),
            'total_diproses' => $terjual_count + count($scanned_barcodes)
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error proses update stok: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat update stok: ' . $e->getMessage()
    ]);
}
