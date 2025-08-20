<?php
include 'header.php';
include '../koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM toko WHERE id = '$id'");
$toko = mysqli_fetch_assoc($data);

// Antisipasi null
$nama_toko = htmlspecialchars($toko['nama_toko'] ?? '');
$alamat_manual = htmlspecialchars($toko['alamat_manual'] ?? '');
$lokasi_maps = htmlspecialchars($toko['lokasi_maps'] ?? '');
?>

<h2 style="text-align: center; margin-bottom: 20px;">Edit Toko</h2>

<div style="max-width: 600px; margin: 0 auto;">
    <form action="proses-edit-toko.php" method="POST">
        <input type="hidden" name="id" value="<?= $toko['id'] ?>">

        <label for="nama_toko"><strong>Nama Toko:</strong></label><br>
        <input type="text" name="nama_toko" id="nama_toko" value="<?= $nama_toko ?>" required style="width: 100%; padding: 10px; margin-bottom: 15px;"><br>

        <label for="alamat_manual"><strong>Alamat Manual:</strong></label><br>
        <input type="text" name="alamat_manual" id="alamat_manual" value="<?= $alamat_manual ?>" style="width: 100%; padding: 10px; margin-bottom: 15px;"><br>

        <label for="lokasi_maps"><strong>Lokasi Google Maps (Link):</strong></label><br>
        <input type="text" name="lokasi_maps" id="lokasi_maps" value="<?= $lokasi_maps ?>" required style="width: 100%; padding: 10px; margin-bottom: 20px;"><br>

        <div style="text-align: center;">
            <button type="submit" style="background-color: orange; color: white; padding: 10px 25px; border: none; border-radius: 5px;">
                Update
            </button>
        </div>
    </form>
</div>
