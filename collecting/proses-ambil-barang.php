<?php
session_start();
require_once '../koneksi.php';

// Cek apakah user sudah login
// if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
//     http_response_code(401);
//     echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
//     exit;
// }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$kode_barcode = $_POST['kode_barcode'] ?? '';
$username = $_SESSION['username'];

if (empty($kode_barcode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}

try {
    // Mulai transaksi
    $conn->begin_transaction();

    // Cek barcode dan status barang
    $query = "SELECT bp.id, bp.status, bp.nama_barang 
              FROM barcode_produk bp 
              WHERE bp.kode_barcode = ? FOR UPDATE";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $kode_barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan'
        ]);
        exit;
    }

    $data = $result->fetch_assoc();

    // Cek status barang
    if ($data['status'] !== 'di_gudang') {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Barang tidak dapat diambil. Status saat ini: ' . $data['status']
        ]);
        exit;
    }

    // Update status barang menjadi "di_collecting - username"
    $status_baru = "di_collecting - " . $username;
    $update_query = "UPDATE barcode_produk SET 
                     status = ?, 
                     updated_at = NOW() 
                     WHERE id = ?";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $status_baru, $data['id']);

    if (!$update_stmt->execute()) {
        throw new Exception("Gagal update status barang: " . $update_stmt->error);
    }

    // Update stok gudang - kurangi 1
    $stok_query = "UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = ?";
    $stok_stmt = $conn->prepare($stok_query);
    $stok_stmt->bind_param("s", $data['nama_barang']);

    if (!$stok_stmt->execute()) {
        throw new Exception("Gagal update stok gudang: " . $stok_stmt->error);
    }

    // Cek apakah tabel log_aktivitas ada, jika tidak skip log
    $check_table = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_table->num_rows > 0) {
        // Log aktivitas - sesuaikan dengan struktur tabel yang ada
        $log_query = "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                      VALUES (?, ?, 'barcode_produk', NOW())";

        $aksi = "Mengambil barang: {$data['nama_barang']} (Barcode: {$kode_barcode}) dari gudang";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("ss", $username, $aksi);
        $log_stmt->execute();
    }

    // Commit transaksi
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Barang berhasil diambil dari gudang',
        'data' => [
            'nama_barang' => $data['nama_barang'],
            'kode_barcode' => $kode_barcode,
            'status_baru' => $status_baru
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error dalam proses-ambil-barang.php: " . $e->getMessage());
    error_log("Kode barcode: " . $kode_barcode);
    error_log("Username: " . $username);
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memproses pengambilan barang: ' . $e->getMessage()
    ]);
}

$conn->close();
