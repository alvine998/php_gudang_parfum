<?php
include '../koneksi.php';
include 'header.php';

$id_toko = $_GET['id_toko'];
$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM toko WHERE id = $id_toko"));
$stok = mysqli_query($conn, "SELECT * FROM stok_toko WHERE id_toko = $id_toko ORDER BY id ASC");
$parfum = mysqli_query($conn, "SELECT * FROM stok_gudang ORDER BY id ASC");
?>

<h2 style="text-align: center;">Tambah Stok untuk <strong><?= htmlspecialchars($toko['nama_toko']); ?></strong></h2>

<div class="table-wrapper" style="max-width: 900px; margin: 0 auto;">
    <form action="proses-tambah-stok-toko.php" method="post">
        <input type="hidden" name="id_toko" value="<?= $id_toko; ?>">

        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead style="background-color: black; color: white;">
                <tr>
                    <th style="padding: 10px;">Varian Parfum</th>
                    <th style="padding: 10px;">Stok Saat Ini</th>
                    <th style="padding: 10px;">Tambah (pcs)</th>
                    <th style="padding: 10px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = mysqli_fetch_assoc($parfum)) {
                    $id_barang = $p['id'];
                    $nama_barang = $p['nama_barang'];
                    $stok_saat_ini = 0;
                    $nama_barang_safe = mysqli_real_escape_string($conn, $nama_barang);
                    $q = mysqli_query($conn, "SELECT * FROM stok_toko WHERE id_toko=$id_toko AND nama_barang='$nama_barang_safe'");
                    if (mysqli_num_rows($q) > 0) {
                        $row = mysqli_fetch_assoc($q);
                        $stok_saat_ini = $row['jumlah'];
                    }
                ?>
                <tr>
                    <td style="padding: 8px;">
                        <?= htmlspecialchars($nama_barang); ?>
                        <input type="hidden" name="id_barang[]" value="<?= $id_barang; ?>">
                    </td>
                    <td style="padding: 8px;"><?= $stok_saat_ini; ?> pcs</td>
                    <td style="padding: 8px;"><input type="number" name="tambah[]" value="0" min="0" style="width: 70px;"></td>
                    <td style="padding: 8px;"><input type="text" name="keterangan[]" value="Dari Gudang" style="width: 150px;"></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 20px;">
            <button type="submit" style="padding: 10px 30px; background-color: black; color: white; border: none; border-radius: 4px;">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
