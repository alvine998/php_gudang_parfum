<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$barcodes = json_decode($_POST['kode_barcode'], true) ?? [];
$username = $_SESSION['username'] ?? 'unknown';

if (!is_array($barcodes) || empty($barcodes)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode harus berupa array']);
    exit;
}

try {
    $conn->begin_transaction();
    $results = [];

    foreach ($barcodes as $kode_barcode) {
        $kode_barcode = trim($kode_barcode);
        if ($kode_barcode === '') continue;

        // Lock barcode
        $query = "SELECT bp.id, bp.status, bp.nama_barang 
                  FROM barcode_produk bp 
                  WHERE bp.kode_barcode = ? FOR UPDATE";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $kode_barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $results[] = [
                'barcode' => $kode_barcode,
                'status' => 'error',
                'message' => 'Barcode tidak ditemukan'
            ];
            continue;
        }

        $data = $result->fetch_assoc();

        if ($data['status'] !== 'di_gudang') {
            $results[] = [
                'barcode' => $kode_barcode,
                'status' => 'error',
                'message' => 'Barang tidak bisa diambil. Status saat ini: ' . $data['status']
            ];
            continue;
        }

        // Update status → di_collecting
        $status_baru = "di_collecting - " . $username;
        $update_query = "UPDATE barcode_produk 
                         SET status = ?, updated_at = NOW() 
                         WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $status_baru, $data['id']);
        if (!$update_stmt->execute()) {
            throw new Exception("Gagal update status: " . $update_stmt->error);
        }

        // Kurangi stok gudang
        $stok_query = "UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = ?";
        $stok_stmt = $conn->prepare($stok_query);
        $stok_stmt->bind_param("s", $data['nama_barang']);
        if (!$stok_stmt->execute()) {
            throw new Exception("Gagal update stok gudang: " . $stok_stmt->error);
        }

        // Log aktivitas
        $check_table = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
        if ($check_table && $check_table->num_rows > 0) {
            $aksi = "Mengambil barang: {$data['nama_barang']} (Barcode: {$kode_barcode}) dari gudang";
            $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                          VALUES (?, ?, 'barcode_produk', NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("ss", $username, $aksi);
            $log_stmt->execute();
        }

        $results[] = [
            'barcode' => $kode_barcode,
            'status' => 'success',
            'nama_barang' => $data['nama_barang'],
            'status_baru' => $status_baru
        ];
    }

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'results' => $results
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error BULK ambil barang: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}

$conn->close();
