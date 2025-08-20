<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
    header("Location: ../index.php");
    exit;
}

include '../koneksi.php';

$username = $_SESSION['username'];
$id_toko = $_POST['id_toko'];
$id_barang = $_POST['id_barang'];
$tambah = $_POST['tambah'];
$keterangan = $_POST['keterangan'];
$tanggal = date('Y-m-d H:i:s');

// Ambil nama toko
$q_toko = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko");
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko = $d_toko['nama_toko'];

$errors = [];

// Validasi stok cukup terlebih dahulu untuk semua input
foreach ($id_barang as $key => $barang_id) {
    $jumlah_tambah = (int)$tambah[$key];
    if ($jumlah_tambah > 0) {
        $qbarang = mysqli_query($conn, "SELECT nama_barang, jumlah FROM stok_gudang WHERE id=$barang_id");
        $dp = mysqli_fetch_assoc($qbarang);
        $stok_gudang = (int)$dp['jumlah'];
        if ($jumlah_tambah > $stok_gudang) {
            $errors[] = "Stok gudang tidak mencukupi untuk varian: {$dp['nama_barang']} (tersedia: $stok_gudang, diminta: $jumlah_tambah)";
        }
    }
}

// Jika ada error stok, kembalikan ke halaman form dengan pesan error
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header("Location: tambah-stok-toko.php?id_toko=$id_toko");
    exit;
}

// Jika lolos validasi, lanjut proses simpan
$items_added = []; // Array untuk menyimpan item yang ditambahkan
foreach ($id_barang as $key => $barang_id) {
    $jumlah_tambah = (int)$tambah[$key];
    $ket = mysqli_real_escape_string($conn, $keterangan[$key]);

    if ($jumlah_tambah > 0) {
        $qbarang = mysqli_query($conn, "SELECT nama_barang, jumlah FROM stok_gudang WHERE id=$barang_id");
        $dp = mysqli_fetch_assoc($qbarang);
        $nama_barang = $dp['nama_barang'];
        $stok_gudang = (int)$dp['jumlah'];

        // Cek stok toko
        $cek = mysqli_query($conn, "SELECT * FROM stok_toko WHERE id_toko=$id_toko AND nama_barang='$nama_barang'");
        if (mysqli_num_rows($cek) > 0) {
            $row = mysqli_fetch_assoc($cek);
            $stok_awal = (int)$row['jumlah'];
            $stok_akhir = $stok_awal + $jumlah_tambah;

            mysqli_query($conn, "UPDATE stok_toko 
                                 SET jumlah=$stok_akhir, updated_by='$username', updated_at='$tanggal' 
                                 WHERE id_toko=$id_toko AND nama_barang='$nama_barang'");
        } else {
            $stok_awal = 0;
            $stok_akhir = $jumlah_tambah;

            mysqli_query($conn, "INSERT INTO stok_toko (id_toko, nama_barang, jumlah, updated_by, updated_at) 
                                 VALUES ($id_toko, '$nama_barang', $jumlah_tambah, '$username', '$tanggal')");
        }

        // Kurangi stok gudang
        $sisa_gudang = $stok_gudang - $jumlah_tambah;
        mysqli_query($conn, "UPDATE stok_gudang SET jumlah=$sisa_gudang WHERE id=$barang_id");

        // Log Aktivitas
        $aksi = "Menambahkan $jumlah_tambah pcs $nama_barang ke $nama_toko";
        mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu) 
                             VALUES ('$username', '$aksi', 'stok_toko', '$tanggal')");

        // Laporan Stok
        mysqli_query($conn, "INSERT INTO laporan_stok 
                             (tanggal, nama_barang, stok_awal, masuk, keluar, stok_akhir, lokasi, dibuat_oleh, dibuat_pada, keterangan)
                             VALUES ('$tanggal', '$nama_barang', $stok_awal, $jumlah_tambah, 0, $stok_akhir, '$nama_toko', '$username', '$tanggal', '$ket')");

        // Simpan item yang ditambahkan untuk print
        $items_added[] = [
            'nama_barang' => $nama_barang,
            'jumlah' => $jumlah_tambah,
            'keterangan' => $ket,
            'harga' => 15000
        ];
    }
}

// Simpan data untuk print dalam session
$_SESSION['print_data'] = [
    'toko' => $nama_toko,
    'tanggal' => $tanggal,
    'items' => $items_added,
    'username' => $username
];

// Redirect ke halaman print
header("Location: print-tambah-stok.php");
exit;
