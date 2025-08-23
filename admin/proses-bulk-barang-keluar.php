<?php
session_start();
include '../koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    // Ambil raw JSON
    $raw = file_get_contents("php://input");
    $payload = json_decode($raw, true);

    if (!isset($payload['items']) || !is_array($payload['items']) || empty($payload['items'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak ada item yang dikirim',
            'data' => null
        ]);
        exit;
    }

    $items = $payload['items'];
    $results = [];
    $errors = [];

    // Start transaction
    mysqli_autocommit($conn, FALSE);

    foreach ($items as $item) {
        $kode_barcode = mysqli_real_escape_string($conn, $item['kode_barcode'] ?? '');
        $jenis_penjualan = mysqli_real_escape_string($conn, $item['jenis_penjualan'] ?? 'perorangan');

        if (!$kode_barcode) {
            $errors[] = "Kode barcode kosong";
            continue;
        }
        if (!in_array($jenis_penjualan, ['perorangan', 'shopee', 'rusak'])) {
            $jenis_penjualan = 'perorangan';
        }

        // Cek barcode
        $check_query = "SELECT bp.*, COALESCE(sg.jumlah,0) as stok_gudang 
                        FROM barcode_produk bp 
                        LEFT JOIN stok_gudang sg ON bp.nama_barang = sg.nama_barang 
                        WHERE bp.kode_barcode = '$kode_barcode'";
        $check_result = mysqli_query($conn, $check_query);

        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $errors[] = "Barcode $kode_barcode tidak ditemukan";
            continue;
        }

        $data = mysqli_fetch_assoc($check_result);

        if ($data['status'] == 'terjual') {
            $errors[] = "Barcode $kode_barcode sudah terjual";
            continue;
        } elseif ($data['status'] != 'di_gudang') {
            $errors[] = "Barcode $kode_barcode belum di gudang";
            continue;
        } elseif ($data['stok_gudang'] <= 0) {
            $errors[] = "Stok habis untuk {$data['nama_barang']}";
            continue;
        }

        // Update status
        $status_baru = ($jenis_penjualan === 'rusak') ? 'rusak' : 'terjual';
        $update_query = "UPDATE barcode_produk 
                         SET status = '$status_baru', updated_at = NOW() 
                         WHERE kode_barcode = '$kode_barcode'";
        if (!mysqli_query($conn, $update_query)) {
            $errors[] = "Gagal update status untuk $kode_barcode";
            continue;
        }

        // Kurangi stok
        $kurangiStok = "UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = '{$data['nama_barang']}'";
        if (!mysqli_query($conn, $kurangiStok)) {
            $errors[] = "Gagal kurangi stok untuk {$data['nama_barang']}";
            continue;
        }

        // Jenis text
        if ($jenis_penjualan === 'rusak') {
            $jenis_text = 'Barang Rusak';
        } elseif ($jenis_penjualan === 'shopee') {
            $jenis_text = 'Shopee';
        } else {
            $jenis_text = 'Perorangan';
        }

        // Log
        $username = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Unknown');
        $log_query = "INSERT INTO log_aktivitas (username, aksi, waktu) VALUES (
            '$username',
            'Memproses barang keluar: $kode_barcode - {$data['nama_barang']} ($jenis_text)',
            NOW()
        )";
        mysqli_query($conn, $log_query);

        $results[] = [
            'kode_barcode' => $kode_barcode,
            'nama_barang' => $data['nama_barang'],
            'status_baru' => $status_baru,
            'jenis_penjualan' => $jenis_penjualan,
            'status_display' => $status_baru . ' (' . $jenis_text . ')',
            'stok_baru' => $data['stok_gudang'] - 1
        ];
    }

    if (count($errors) > 0) {
        mysqli_rollback($conn);
        $response = [
            'status' => 'error',
            'message' => 'Beberapa item gagal diproses',
            'errors' => $errors,
            'data' => $results
        ];
    } else {
        mysqli_commit($conn);
        $response = [
            'status' => 'success',
            'message' => count($results) . ' barang berhasil diproses keluar',
            'data' => $results
        ];
    }

    mysqli_autocommit($conn, TRUE);
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'data' => null
    ];
}

ob_clean();
error_log("Proses Bulk Barang Keluar Response: " . json_encode($response));
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
