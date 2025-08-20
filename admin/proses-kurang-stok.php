<?php
session_start();
include '../koneksi.php';

$id = $_POST['id'];
$kurangi = (int) $_POST['kurangi'];
$keterangan = $_POST['keterangan'];
$username = $_SESSION['username'];
$waktu = date('Y-m-d H:i:s');

// Ambil data stok saat ini
$stok = mysqli_query($conn, "SELECT * FROM stok_gudang WHERE id='$id'");
$data = mysqli_fetch_assoc($stok);
$nama_barang = $data['nama_barang'];
$jumlah_sekarang = (int) $data['jumlah'];

// Validasi jumlah
if ($kurangi <= 0 || $kurangi > $jumlah_sekarang) {
    echo "<script>alert('Jumlah tidak valid!'); window.location.href='stok-gudang.php';</script>";
    exit;
}

// Hitung sisa stok
$sisa = $jumlah_sekarang - $kurangi;

// Cek apakah pengiriman ke toko atau hilang
$asal_toko = ($keterangan === "Stok Hilang") ? '-' : str_replace('Dikirim ke ', '', $keterangan);

// Update stok gudang
mysqli_query($conn, "UPDATE stok_gudang 
    SET jumlah='$sisa', updated_by='$username', updated_at='$waktu', asal_toko='$asal_toko' 
    WHERE id='$id'");

// Catat ke log aktivitas
$aksi = "Kurangi Stok Gudang ($kurangi pcs) - $nama_barang ($keterangan)";
$tabel = "stok_gudang";
mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
    VALUES ('$username', '$aksi', '$tabel', '$waktu')");

// Catat ke laporan stok (tambahkan kolom keterangan)
// mysqli_query($conn, "INSERT INTO laporan_stok 
//     (tanggal, nama_barang, lokasi, stok_awal, masuk, keluar, stok_akhir, dibuat_oleh, dibuat_pada, keterangan) 
//     VALUES 
//     (CURDATE(), '$nama_barang', 'Gudang', $jumlah_sekarang, 0, $kurangi, $sisa, '$username', '$waktu', '$keterangan')");

// Redirect
echo "<script>alert('Stok berhasil dikurangi!'); window.location.href='stok-gudang.php';</script>";
?>
