<?php
include '../koneksi.php';
session_start();
$username = $_SESSION['username'];

$id_toko = $_POST['id_toko'];
$id_barang = $_POST['id_barang'];
$tambah = $_POST['tambah'];
$keterangan = $_POST['keterangan'];
$tanggal = date('Y-m-d H:i:s');

$q_toko = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko");
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko = $d_toko['nama_toko'];

$gagal = false; // untuk mendeteksi jika ada stok gudang yang kurang

foreach ($id_barang as $key => $barang_id) {
    $jumlah_tambah = (int)$tambah[$key];
    $ket = mysqli_real_escape_string($conn, $keterangan[$key]);

    if ($jumlah_tambah > 0) {
        $qbarang = mysqli_query($conn, "SELECT nama_barang, jumlah FROM stok_gudang WHERE id=$barang_id");
        $dp = mysqli_fetch_assoc($qbarang);
        $nama_barang = $dp['nama_barang'];
        $stok_gudang = (int)$dp['jumlah'];

        // 💡 Cek stok gudang cukup atau tidak
        if ($jumlah_tambah > $stok_gudang) {
            $gagal = true;
            continue; // skip proses ini
        }

        // Cek stok toko sebelumnya
        $cek = mysqli_query($conn, "SELECT * FROM stok_toko WHERE id_toko=$id_toko AND nama_barang='$nama_barang'");
        if (mysqli_num_rows($cek) > 0) {
            $row = mysqli_fetch_assoc($cek);
            $stok_awal = $row['jumlah'];
            $stok_akhir = $stok_awal + $jumlah_tambah;

            mysqli_query($conn, "UPDATE stok_toko SET jumlah=$stok_akhir, updated_by='$username', updated_at='$tanggal' 
                                WHERE id_toko=$id_toko AND nama_barang='$nama_barang'");
        } else {
            $stok_awal = 0;
            $stok_akhir = $jumlah_tambah;

            mysqli_query($conn, "INSERT INTO stok_toko (id_toko, nama_barang, jumlah, updated_by, updated_at) 
                                VALUES ($id_toko, '$nama_barang', $jumlah_tambah, '$username', '$tanggal')");
        }

        // Update stok gudang
        $sisa_gudang = $stok_gudang - $jumlah_tambah;
        mysqli_query($conn, "UPDATE stok_gudang SET jumlah=$sisa_gudang WHERE nama_barang='$nama_barang'");

        // Log Aktivitas
        $aksi = "Menambahkan $jumlah_tambah pcs $nama_barang ke $nama_toko";
        mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                            VALUES ('$username', '$aksi', 'stok_toko', '$tanggal')");

        // Laporan Stok
        // mysqli_query($conn, "INSERT INTO laporan_stok 
        // (tanggal, nama_barang, stok_awal, masuk, keluar, stok_akhir, lokasi, dibuat_oleh, dibuat_pada, keterangan)
        // VALUES ('$tanggal', '$nama_barang', $stok_awal, $jumlah_tambah, 0, $stok_akhir, '$nama_toko', '$username', '$tanggal', '$ket')");
    }
}

// Redirect dengan status
if ($gagal) {
    header("Location: stok-toko.php?id_toko=$id_toko&status=gagal");
} else {
    header("Location: stok-toko.php?id_toko=$id_toko&status=berhasil");
}
exit;
?>
