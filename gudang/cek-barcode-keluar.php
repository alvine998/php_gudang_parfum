<?php
include '../koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['kode_barcode']) && !empty($_POST['kode_barcode'])) {
    $kode_barcode = mysqli_real_escape_string($conn, $_POST['kode_barcode']);

    // Cek apakah kode barcode ada di database dan ambil info stok
    $query = "SELECT bp.*, 
                     COALESCE(sg.jumlah, 0) as stok_gudang 
              FROM barcode_produk bp 
              LEFT JOIN stok_gudang sg ON bp.nama_barang = sg.nama_barang 
              WHERE bp.kode_barcode = '$kode_barcode'";
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
                'status' => $data['status'] ?? 'pending',
                'stok_gudang' => $data['stok_gudang'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Tidak ditemukan
        echo json_encode([
            'status' => 'error',
            'message' => 'Barcode tidak ditemukan di database',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kode barcode tidak valid',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

exit;
