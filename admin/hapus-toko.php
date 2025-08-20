<?php
include '../koneksi.php';
session_start();

$id = $_GET['id'];
$username = $_SESSION['username'];

// Ambil nama toko untuk log
$get = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = '$id'");
$toko = mysqli_fetch_assoc($get);
$nama_toko = mysqli_real_escape_string($conn, $toko['nama_toko']);

// Cek total stok
$cek_stok = mysqli_query($conn, "SELECT SUM(jumlah) AS total_stok FROM stok_toko WHERE id_toko = '$id'");
$data_stok = mysqli_fetch_assoc($cek_stok);
$total_stok = (int)$data_stok['total_stok'];

// Jika masih ada stok, batalkan hapus dan beri alert
if ($total_stok > 0) {
    echo "<script>
        alert('Tidak bisa menghapus toko \"$nama_toko\" karena masih memiliki stok sebanyak $total_stok pcs.');
        window.location.href = 'daftar-toko.php';
    </script>";
    exit;
}

// Jika aman, hapus dari tabel toko
mysqli_query($conn, "DELETE FROM toko WHERE id = '$id'");

// Simpan log aktivitas
$aksi = "Menghapus toko: $nama_toko";
$waktu = date('Y-m-d H:i:s');
mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES ('$username', '$aksi', 'toko', '$waktu')");

header("Location: daftar-toko.php?hapus=success");
exit;
?>
