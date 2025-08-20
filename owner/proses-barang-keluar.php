<?php
session_start();
include '../koneksi.php';

// Tangkap semua error dan jangan tampilkan di output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Buffer output untuk mencegah output yang tidak diinginkan
ob_start();

try {
    if (isset($_POST['kode_barcode']) && !empty($_POST['kode_barcode'])) {
        $kode_barcode = mysqli_real_escape_string($conn, $_POST['kode_barcode']);
        $jenis_penjualan = mysqli_real_escape_string($conn, $_POST['jenis_penjualan'] ?? 'perorangan');

        // Validasi jenis penjualan
        if (!in_array($jenis_penjualan, ['perorangan', 'shopee'])) {
            $jenis_penjualan = 'perorangan'; // Default fallback
        }

        // Cek apakah barcode ada dan statusnya di_gudang
        $check_query = "SELECT bp.*, 
                               COALESCE(sg.jumlah, 0) as stok_gudang 
                        FROM barcode_produk bp 
                        LEFT JOIN stok_gudang sg ON bp.nama_barang = sg.nama_barang 
                        WHERE bp.kode_barcode = '$kode_barcode'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $data = mysqli_fetch_assoc($check_result);

            // Cek status saat ini
            if ($data['status'] == 'terjual') {
                $response = [
                    'status' => 'error',
                    'message' => 'Barang sudah terjual',
                    'data' => null
                ];
            } else if ($data['status'] != 'di_gudang') {
                $response = [
                    'status' => 'error',
                    'message' => 'Barang belum berada di gudang',
                    'data' => null
                ];
            } else if ($data['stok_gudang'] <= 0) {
                $response = [
                    'status' => 'error',
                    'message' => 'Stok gudang habis',
                    'data' => null
                ];
            } else {
                // Start transaction
                mysqli_autocommit($conn, FALSE);

                // Update status menjadi terjual
                $update_query = "UPDATE barcode_produk SET status = 'terjual', updated_at = NOW() WHERE kode_barcode = '$kode_barcode'";

                if (mysqli_query($conn, $update_query)) {
                    // Kurangi stok gudang
                    $kurangiStok = "UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = '{$data['nama_barang']}'";

                    if (mysqli_query($conn, $kurangiStok)) {
                        // Log aktivitas dengan jenis penjualan
                        $username = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Unknown');
                        $jenis_text = $jenis_penjualan === 'shopee' ? 'Shopee' : 'Perorangan';
                        $log_query = "INSERT INTO log_aktivitas (username, aksi, waktu) VALUES (
                            '$username', 
                            'Memproses barang keluar untuk kode: $kode_barcode - {$data['nama_barang']} (Terjual $jenis_text)', 
                            NOW()
                        )";
                        mysqli_query($conn, $log_query);

                        // Commit transaction
                        mysqli_commit($conn);

                        $response = [
                            'status' => 'success',
                            'message' => 'Barang berhasil diproses keluar (terjual ' . ($jenis_penjualan === 'shopee' ? 'Shopee' : 'perorangan') . ')',
                            'data' => [
                                'kode_barcode' => $kode_barcode,
                                'nama_barang' => $data['nama_barang'],
                                'status_baru' => 'terjual',
                                'jenis_penjualan' => $jenis_penjualan,
                                'status_display' => 'terjual (' . ($jenis_penjualan === 'shopee' ? 'Shopee' : 'Perorangan') . ')',
                                'stok_baru' => $data['stok_gudang'] - 1
                            ]
                        ];
                    } else {
                        mysqli_rollback($conn);
                        $response = [
                            'status' => 'error',
                            'message' => 'Gagal mengurangi stok gudang: ' . mysqli_error($conn),
                            'data' => null
                        ];
                    }
                } else {
                    mysqli_rollback($conn);
                    $response = [
                        'status' => 'error',
                        'message' => 'Gagal memperbarui status barang: ' . mysqli_error($conn),
                        'data' => null
                    ];
                }

                mysqli_autocommit($conn, TRUE);
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Barcode tidak ditemukan di database',
                'data' => null
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Kode barcode tidak valid',
            'data' => null
        ];
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'data' => null
    ];
}

// Bersihkan buffer dan pastikan hanya JSON yang dikirim
ob_clean();

// Log untuk debugging (opsional - bisa dihapus nanti)
error_log("Proses Barang Keluar Response: " . json_encode($response));

// Set header sekali lagi untuk memastikan
header('Content-Type: application/json; charset=utf-8');

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
