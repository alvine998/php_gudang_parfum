<?php
session_start();
include '../koneksi.php';

// Tangkap semua error dan jangan tampilkan di output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Buffer output untuk mencegah output yang tidak diinginkan
ob_start();

try {
    if (isset($_POST['kode_barcode']) && !empty($_POST['kode_barcode'])) {
        $kode_barcode = mysqli_real_escape_string($conn, $_POST['kode_barcode']);

        // Cek apakah barcode ada dan statusnya bukan di_gudang
        $check_query = "SELECT * FROM barcode_produk WHERE kode_barcode = '$kode_barcode'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $data = mysqli_fetch_assoc($check_result);

            // Cek status saat ini
            if ($data['status'] == 'di_gudang') {
                $response = [
                    'status' => 'error',
                    'message' => 'Barang sudah berada di gudang',
                    'data' => null
                ];
            } else {
                // Start transaction
                mysqli_autocommit($conn, FALSE);

                // Update status menjadi di_gudang
                $update_query = "UPDATE barcode_produk SET status = 'di_gudang', updated_at = NOW() WHERE kode_barcode = '$kode_barcode'";

                if (mysqli_query($conn, $update_query)) {
                    // Cek dan update stok gudang
                    $check_stok = "SELECT * FROM stok_gudang WHERE nama_barang = '{$data['nama_barang']}'";
                    $stok_result = mysqli_query($conn, $check_stok);

                    if (mysqli_num_rows($stok_result) > 0) {
                        // Update stok yang sudah ada
                        $tambahStok = "UPDATE stok_gudang SET jumlah = jumlah + 1 WHERE nama_barang = '{$data['nama_barang']}'";
                    } else {
                        // Insert stok baru
                        $tambahStok = "INSERT INTO stok_gudang (nama_barang, jumlah, created_at) VALUES ('{$data['nama_barang']}', 1, NOW())";
                    }

                    if (mysqli_query($conn, $tambahStok)) {
                        // Log aktivitas
                        $username = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Unknown');
                        $log_query = "INSERT INTO log_aktivitas (username, aksi, waktu) VALUES (
                            '$username', 
                     
                            'Memproses barang masuk untuk kode: $kode_barcode - {$data['nama_barang']}', 
                            NOW()
                        )";
                        mysqli_query($conn, $log_query);

                        // Commit transaction
                        mysqli_commit($conn);

                        $response = [
                            'status' => 'success',
                            'message' => 'Barang berhasil diproses masuk ke gudang',
                            'data' => [
                                'kode_barcode' => $kode_barcode,
                                'nama_barang' => $data['nama_barang'],
                                'status_baru' => 'di_gudang'
                            ]
                        ];
                    } else {
                        mysqli_rollback($conn);
                        $response = [
                            'status' => 'error',
                            'message' => 'Gagal memperbarui stok gudang: ' . mysqli_error($conn),
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
error_log("Proses Barang Masuk Response: " . json_encode($response));

// Set header sekali lagi untuk memastikan
header('Content-Type: application/json; charset=utf-8');

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
