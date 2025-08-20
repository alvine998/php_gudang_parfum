<?php
include '../koneksi.php';

header('Content-Type: application/json');

if ($_POST['kode_barcode']) {
    $kode_barcode = mysqli_real_escape_string($conn, $_POST['kode_barcode']);

    // Cek apakah kode barcode ada di database
    $query = "SELECT * FROM barcode_produk WHERE kode_barcode = '$kode_barcode'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);

        // Berhasil ditemukan
        echo json_encode([
            'status' => 'success',
            'message' => 'Barcode ditemukan',
            'data' => [
                'id' => $data['id'],
                'kode_barcode' => $data['kode_barcode'],
                'nama_barang' => $data['nama_barang'],
                'status' => $data['status'] ?? 'pending', // Default status jika null
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at']
            ]
        ]);
    } else {
        // Tidak ditemukan
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan di database',
            'data' => null
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kode barcode tidak valid',
        'data' => null
    ]);
}
