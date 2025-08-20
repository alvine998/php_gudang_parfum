<?php
session_start();
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_toko = $_POST['id_toko'];
    $username = $_SESSION['username'];
    $waktu = date('Y-m-d H:i:s');

    $sql_gudang = "SELECT nama_barang, jumlah FROM stok_gudang";
    $result_gudang = mysqli_query($conn, $sql_gudang);

    while ($row = mysqli_fetch_assoc($result_gudang)) {
        $varian = $row['nama_barang'];
        $stok_gudang_sekarang = (int) $row['jumlah'];

        // nama input di form harus sama persis dengan varian (tanpa spasi lebih baik, atau diganti jadi nama input 'stok_' . id)
        $input_nama = str_replace(' ', '_', strtolower($varian));
        $jumlah_input = isset($_POST[$input_nama]) ? (int) $_POST[$input_nama] : 0;

        if ($jumlah_input > 0) {
            // 1. Kurangi dari stok gudang
            $stok_gudang_baru = $stok_gudang_sekarang - $jumlah_input;
            $update_gudang = "UPDATE stok_gudang SET jumlah = $stok_gudang_baru, updated_by = '$username', updated_at = '$waktu', asal_toko = (SELECT nama FROM toko WHERE id = $id_toko) WHERE nama_barang = '$varian'";
            mysqli_query($conn, $update_gudang);

            // 2. Cek stok toko, update jika ada, insert jika belum
            $cek_stok_toko = mysqli_query($conn, "SELECT * FROM stok_toko WHERE id_toko = $id_toko AND nama_barang = '$varian'");
            if (mysqli_num_rows($cek_stok_toko) > 0) {
                // Update jumlah
                $row_stok = mysqli_fetch_assoc($cek_stok_toko);
                $jumlah_baru = $row_stok['jumlah'] + $jumlah_input;
                mysqli_query($conn, "UPDATE stok_toko SET jumlah = $jumlah_baru, updated_by = '$username', updated_at = '$waktu' WHERE id_toko = $id_toko AND nama_barang = '$varian'");
            } else {
                // Insert baru
                mysqli_query($conn, "INSERT INTO stok_toko (id_toko, nama_barang, jumlah, updated_by, updated_at) VALUES ($id_toko, '$varian', $jumlah_input, '$username', '$waktu')");
            }

            // 3. Catat laporan stok (lokasi = 'Toko')
            // mysqli_query($conn, "INSERT INTO laporan_stok (tanggal, nama_barang, lokasi, stok_awal, masuk, keluar, stok_akhir, dibuat_oleh, dibuat_pada, keterangan)
            //     VALUES (CURDATE(), '$varian', 'Toko', 0, $jumlah_input, 0, $jumlah_input, '$username', '$waktu', 
            //     (SELECT nama FROM toko WHERE id = $id_toko))");

            // 4. Log aktivitas
            mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu)
                VALUES ('$username', 'Input stok $jumlah_input pcs ke toko ID $id_toko ($varian)', 'stok_toko', '$waktu')");
        }
    }

    header("Location: stok-toko.php?status=sukses");
    exit;
} else {
    echo "Data stok belum dikirim.";
}
?>
