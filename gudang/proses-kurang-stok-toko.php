<?php
include '../koneksi.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

$id_toko = $_POST['id_toko'];
$barang = $_POST['nama_barang'];
$jumlah_kurang = $_POST['kurang'];
$keterangan = $_POST['keterangan'];
$username = $_SESSION['username'];
$tanggal = date('Y-m-d H:i:s');

// Ambil nama toko dulu
$q_toko = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko");
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko = $d_toko['nama_toko'];

foreach ($barang as $i => $nama_barang) {
    $kurangi = (int)$jumlah_kurang[$i];
    $ket = mysqli_real_escape_string($conn, $keterangan[$i]);

    if ($kurangi <= 0) continue;

    // Ambil stok toko
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jumlah FROM stok_toko WHERE id_toko=$id_toko AND nama_barang='$nama_barang'"));
    $stok_awal = (int)$cek['jumlah'];

    if ($kurangi > $stok_awal) {
        echo "<script>alert('Jumlah pengurangan $nama_barang melebihi stok toko ($stok_awal pcs)'); window.location='kurang-stok-toko.php?id_toko=$id_toko';</script>";
        exit;
    }

    $stok_akhir = $stok_awal - $kurangi;

    // Update stok toko
    mysqli_query($conn, "UPDATE stok_toko SET jumlah = $stok_akhir, updated_by='$username', updated_at='$tanggal' 
                         WHERE id_toko=$id_toko AND nama_barang='$nama_barang'");

    // Jika pengembalian, tambahkan ke gudang
    if ($ket === 'Balik Stok') {
        $cekGudang = mysqli_query($conn, "SELECT COUNT(*) as ada FROM stok_gudang WHERE nama_barang='$nama_barang'");
        $adaGudang = mysqli_fetch_assoc($cekGudang)['ada'];

        if ($adaGudang) {
            mysqli_query($conn, "UPDATE stok_gudang SET jumlah = jumlah + $kurangi, updated_by='$username', updated_at='$tanggal' 
                                 WHERE nama_barang='$nama_barang'");
        } else {
            mysqli_query($conn, "INSERT INTO stok_gudang (nama_barang, jumlah, satuan, updated_by, updated_at) 
                                 VALUES ('$nama_barang', $kurangi, 'pcs', '$username', '$tanggal')");
        }
    }

    // Log Aktivitas
    $aksi_log = ($ket === 'Balik Stok') 
        ? "Mengembalikan $kurangi pcs $nama_barang dari $nama_toko ke Gudang" 
        : "Mengurangi $kurangi pcs $nama_barang dari $nama_toko (Terjual)";
    
    mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu)
                         VALUES ('$username', '$aksi_log', 'stok_toko', '$tanggal')");

    // Laporan Stok
    // mysqli_query($conn, "INSERT INTO laporan_stok 
    //     (tanggal, nama_barang, stok_awal, masuk, keluar, stok_akhir, lokasi, dibuat_oleh, dibuat_pada, keterangan)
    //     VALUES 
    //     ('$tanggal', '$nama_barang', $stok_awal, 0, $kurangi, $stok_akhir, '$nama_toko', '$username', '$tanggal', '$ket')");
}

header("Location: stok-toko.php?id_toko=$id_toko&status=berhasil");
exit;
?>
