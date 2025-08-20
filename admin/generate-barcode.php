<?php
function generateBarcode($id_gudang, $nama_barang, $jumlah) {
    include '../koneksi.php';

    // Mapping varian ke kode pendek
    $kode_varian = [
        'Soff Pink' => 'SFP',
        'Orgasme' => 'ORG',
        'Scandalous' => 'SCL',
        'La Viest Belle' => 'LVB',
        'S. O. T. B' => 'SOTB',
        'One Million Lucky' => 'OML',
        'Candy Love' => 'CDL',
        'Stronger With You' => 'SWY',
        'V. Eros' => 'VER'
    ];

    // Ambil kode pendek varian
    $kode = $kode_varian[$nama_barang] ?? 'UNK'; // UNK = Unknown

    // Waktu saat ini
    $tanggal = date('Y-m-d H:i:s');

    for ($i = 0; $i < $jumlah; $i++) {
        // Buat kode unik per item
        $unique_code = $kode . '-' . uniqid();

        // Simpan barcode ke database
        $query = "INSERT INTO barcode_produk 
    (kode_barcode, id_gudang, nama_barang, status, lokasi, asal_toko, created_at, updated_at) 
    VALUES 
    ('$unique_code', '$id_gudang', '$nama_barang', 'di_gudang', '', '', '$tanggal', '$tanggal')";


        mysqli_query($conn, $query);
    }
}
?>
