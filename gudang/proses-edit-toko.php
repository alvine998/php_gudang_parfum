<?php
include '../koneksi.php';
session_start();

$username = $_SESSION['username'];

$id = $_POST['id'];
$nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko']);
$alamat_manual = mysqli_real_escape_string($conn, $_POST['alamat_manual']);
$lokasi_maps = mysqli_real_escape_string($conn, $_POST['lokasi_maps']);

// Ambil nama toko lama untuk log (sebelum diubah)
$get = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = '$id'");
$old = mysqli_fetch_assoc($get);
$nama_toko_lama = mysqli_real_escape_string($conn, $old['nama_toko']);

// Cek duplikat nama toko selain ID ini
$cek = mysqli_query($conn, "SELECT * FROM toko WHERE nama_toko = '$nama_toko' AND id != '$id'");
if (mysqli_num_rows($cek) > 0) {
    header("Location: edit-toko.php?id=$id&error=Nama toko sudah terdaftar");
    exit;
}

// Update data toko
mysqli_query($conn, "UPDATE toko SET 
                        nama_toko = '$nama_toko', 
                        alamat_manual = '$alamat_manual', 
                        lokasi_maps = '$lokasi_maps' 
                    WHERE id = '$id'");

// Simpan log aktivitas
$aksi = "Mengedit toko: $nama_toko";
$waktu = date('Y-m-d H:i:s');
mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES ('$username', '$aksi', 'toko', '$waktu')");

header("Location: daftar-toko.php?update=success");
exit;
?>
