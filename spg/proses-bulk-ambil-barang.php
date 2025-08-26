<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil kode barcode, bisa berupa JSON array atau string tunggal
$kode_barcode = $_POST['kode_barcode'] ?? '';

$username = $_SESSION['username'] ?? 'guest';

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

// Jika dikirim string JSON, ubah menjadi array
if (!is_array($kode_barcode)) {
    $decoded = json_decode($kode_barcode, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $kode_barcode = $decoded;
    } else {
        $kode_barcode = [$kode_barcode]; // jadikan array 1 item
    }
}

try {
    $conn->begin_transaction();
    $hasil = [];

    foreach ($kode_barcode as $barcode) {
        // Lock record
        $query = "SELECT bp.id, bp.status, bp.nama_barang 
                  FROM barcode_produk bp 
                  WHERE bp.kode_barcode = ? FOR UPDATE";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $hasil[] = [
                'kode_barcode' => $barcode,
                'status' => 'error',
                'message' => 'Barcode tidak ditemukan'
            ];
            continue;
        }

        $data = $result->fetch_assoc();

        if ($data['status'] !== 'di_gudang') {
            $hasil[] = [
                'kode_barcode' => $barcode,
                'status' => 'error',
                'message' => 'Barang tidak dapat diambil. Status saat ini: ' . $data['status']
            ];
            continue;
        }

        // Update status barang
        $status_baru = "di_spg - " . $username;
        $update_query = "UPDATE barcode_produk SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $status_baru, $data['id']);
        if (!$update_stmt->execute()) {
            throw new Exception("Gagal update status barang: " . $update_stmt->error);
        }

        // Kurangi stok
        $stok_query = "UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = ?";
        $stok_stmt = $conn->prepare($stok_query);
        $stok_stmt->bind_param("s", $data['nama_barang']);
        if (!$stok_stmt->execute()) {
            throw new Exception("Gagal update stok gudang: " . $stok_stmt->error);
        }

        // Log aktivitas
        $check_table = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
        if ($check_table->num_rows > 0) {
            $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                          VALUES (?, ?, 'barcode_produk', NOW())";
            $aksi = "Mengambil barang: {$data['nama_barang']} (Barcode: {$barcode}) dari gudang";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("ss", $username, $aksi);
            $log_stmt->execute();
        }

        $hasil[] = [
            'kode_barcode' => $barcode,
            'status' => 'success',
            'data' => [
                'nama_barang' => $data['nama_barang'],
                'status_baru' => $status_baru
            ]
        ];
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Proses pengambilan selesai',
        'data' => $hasil
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error dalam proses-ambil-barang.php (bulk): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memproses pengambilan barang: ' . $e->getMessage()
    ]);
}

$conn->close();
