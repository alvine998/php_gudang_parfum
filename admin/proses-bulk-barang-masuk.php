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
    // Ambil JSON input
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data || !isset($data['items']) || !is_array($data['items'])) {
        $response = [
            'status' => 'error',
            'message' => 'Data items tidak valid',
            'data' => null
        ];
    } else {
        $items = $data['items'];
        $username = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Unknown');

        $successCount = 0;
        $errorCount = 0;
        $errorMessages = [];

        // Start transaction
        mysqli_autocommit($conn, FALSE);

        foreach ($items as $item) {
            $kode_barcode = mysqli_real_escape_string($conn, $item['kode_barcode'] ?? '');
            if (empty($kode_barcode)) {
                $errorCount++;
                $errorMessages[] = "Kode barcode kosong di salah satu item";
                continue;
            }

            // Cek barcode
            $check_query = "SELECT * FROM barcode_produk WHERE kode_barcode = '$kode_barcode'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $row = mysqli_fetch_assoc($check_result);

                if ($row['status'] == 'di_gudang') {
                    $errorCount++;
                    $errorMessages[] = "Barang $kode_barcode sudah di gudang";
                    continue;
                }

                // Update status barang
                $update_query = "UPDATE barcode_produk 
                                 SET status = 'di_gudang', updated_at = NOW() 
                                 WHERE kode_barcode = '$kode_barcode'";

                if (!mysqli_query($conn, $update_query)) {
                    $errorCount++;
                    $errorMessages[] = "Gagal update status untuk $kode_barcode: " . mysqli_error($conn);
                    continue;
                }

                // Update stok gudang
                $nama_barang = mysqli_real_escape_string($conn, $row['nama_barang']);
                $check_stok = "SELECT * FROM stok_gudang WHERE nama_barang = '$nama_barang'";
                $stok_result = mysqli_query($conn, $check_stok);

                if (mysqli_num_rows($stok_result) > 0) {
                    $tambahStok = "UPDATE stok_gudang SET jumlah = jumlah + 1 WHERE nama_barang = '$nama_barang'";
                } else {
                    $tambahStok = "INSERT INTO stok_gudang (nama_barang, jumlah, created_at) 
                                   VALUES ('$nama_barang', 1, NOW())";
                }

                if (!mysqli_query($conn, $tambahStok)) {
                    $errorCount++;
                    $errorMessages[] = "Gagal update stok untuk $kode_barcode: " . mysqli_error($conn);
                    continue;
                }

                // Log aktivitas
                $log_query = "INSERT INTO log_aktivitas (username, aksi, waktu) VALUES (
                    '$username', 
                    'Memproses barang masuk untuk kode: $kode_barcode - $nama_barang', 
                    NOW()
                )";
                mysqli_query($conn, $log_query);

                $successCount++;
            } else {
                $errorCount++;
                $errorMessages[] = "Barcode $kode_barcode tidak ditemukan";
            }
        }

        if ($errorCount === 0) {
            mysqli_commit($conn);
            $response = [
                'status' => 'success',
                'message' => "Semua barang berhasil diproses ($successCount items)",
                'data' => [
                    'processed' => $successCount,
                    'errors' => []
                ]
            ];
        } else {
            // Kalau ada error rollback semua
            mysqli_rollback($conn);
            $response = [
                'status' => 'error',
                'message' => "Gagal memproses semua barang. Berhasil: $successCount, Gagal: $errorCount",
                'data' => [
                    'processed' => $successCount,
                    'errors' => $errorMessages
                ]
            ];
        }

        mysqli_autocommit($conn, TRUE);
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

// Log untuk debugging (opsional)
error_log("Proses Barang Masuk Response: " . json_encode($response));

// Output JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
