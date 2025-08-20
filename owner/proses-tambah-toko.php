<?php
session_start();
include '../koneksi.php';

$nama_toko = trim($_POST['nama_toko']);
$lokasi_maps = $_POST['lokasi_maps'];
$alamat_manual = $_POST['alamat_manual'];
$username = $_SESSION['username'];
$waktu = date('Y-m-d H:i:s');

// Cek duplikat nama toko
$cek = mysqli_query($conn, "SELECT * FROM toko WHERE nama_toko = '$nama_toko'");
if (mysqli_num_rows($cek) > 0) {
    header("Location: tambah-toko.php?error=duplicate");
    exit;
}

// Simpan ke database
$query = "INSERT INTO toko (nama_toko, lokasi_maps, alamat_manual, dibuat_oleh) 
          VALUES ('$nama_toko', '$lokasi_maps', '$alamat_manual', '$username')";
mysqli_query($conn, $query);

// Log aktivitas
$aksi = "Menambahkan toko baru: $nama_toko";
$tabel = "toko";
mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                     VALUES ('$username', '$aksi', '$tabel', '$waktu')");

echo "<script>alert('Toko berhasil ditambahkan!'); window.location.href='daftar-toko.php';</script>";
