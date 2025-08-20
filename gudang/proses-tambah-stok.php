<?php
session_start();
include '../koneksi.php';
include 'generate-barcode.php'; // pastikan file ini sudah ada

if (!isset($_POST['id']) || !isset($_POST['tambah'])) {
    echo "<script>alert('Data tidak lengkap!'); window.location.href='stok-gudang.php';</script>";
    exit;
}

$id = $_POST['id'];
$tambah = (int) $_POST['tambah'];
$keterangan = $_POST['keterangan'];
$username = $_SESSION['username'];
$waktu = date('Y-m-d H:i:s');

// Ambil data stok saat ini
$stok = mysqli_query($conn, "SELECT * FROM stok_gudang WHERE id='$id'");
$data = mysqli_fetch_assoc($stok);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location.href='stok-gudang.php';</script>";
    exit;
}

$nama_barang = $data['nama_barang'];
$jumlah_sekarang = (int) $data['jumlah'];

// Validasi jumlah
if ($tambah <= 0) {
    echo "<script>alert('Jumlah tidak valid!'); window.location.href='stok-gudang.php';</script>";
    exit;
}

// Tambahkan stok gudang
$sisa = $jumlah_sekarang + $tambah;
mysqli_query($conn, "UPDATE stok_gudang 
    SET jumlah='$sisa', updated_by='$username', updated_at='$waktu', asal_toko=NULL 
    WHERE id='$id'");

// Log aktivitas
$aksi = "Tambah Stok Gudang ($tambah pcs) - $nama_barang ($keterangan)";
$tabel = "stok_gudang";
mysqli_query($conn, "INSERT INTO log_aktivitas 
    (username, aksi, tabel, waktu) 
    VALUES ('$username', '$aksi', '$tabel', '$waktu')");

// Laporan stok
mysqli_query($conn, "INSERT INTO laporan_stok 
    (tanggal, nama_barang, lokasi, stok_awal, masuk, keluar, stok_akhir, dibuat_oleh, dibuat_pada, keterangan)
    VALUES 
    (CURDATE(), '$nama_barang', 'Gudang', $jumlah_sekarang, $tambah, 0, $sisa, '$username', '$waktu', '$keterangan')");

// Generate QR
generateBarcode($id, $nama_barang, $tambah);

// Ambil ID terakhir barcode
$result = mysqli_query($conn, "SELECT id FROM barcode_produk 
    WHERE id_gudang='$id' AND nama_barang='$nama_barang' 
    ORDER BY id DESC LIMIT $tambah");

$ids = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['id'];
}
$ids_param = implode(',', $ids);

// Redirect ke cetak barcode
header("Location: stok-gudang.php?ids=$ids_param");
exit;
?>
