<?php
include '../koneksi.php';
include 'header.php';

if (!isset($_GET['id_toko'])) {
    header("Location: stok-toko.php");
    exit;
}

$id_toko = (int)$_GET['id_toko'];

// Ambil nama toko
$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko"));
$nama_toko = $toko ? $toko['nama_toko'] : 'Toko Tidak Dikenal';

// Ambil semua varian parfum dari stok_gudang
$varian = mysqli_query($conn, "SELECT DISTINCT nama_barang FROM stok_gudang");

// Ambil stok saat ini di toko
$stok_toko = [];
$result_stok = mysqli_query($conn, "SELECT nama_barang, jumlah FROM stok_toko WHERE id_toko = $id_toko");
while ($row = mysqli_fetch_assoc($result_stok)) {
    $stok_toko[$row['nama_barang']] = $row['jumlah'];
}
?>

<h2 style="text-align: center;">Kurangi Stok <strong><?= htmlspecialchars($nama_toko) ?></strong></h2>

<div class="table-wrapper" style="max-width: 950px; margin: 0 auto;">
    <form action="proses-kurang-stok-toko.php" method="POST">
        <input type="hidden" name="id_toko" value="<?= $id_toko; ?>">

        <table>
            <thead style="background-color: black; color: white;">
                <tr>
                    <th>Varian</th>
                    <th>Stok Saat Ini</th>
                    <th>Jumlah Kurang</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($v = mysqli_fetch_assoc($varian)) :
                    $nama_barang = $v['nama_barang'];
                    $stok_saat_ini = isset($stok_toko[$nama_barang]) ? $stok_toko[$nama_barang] : 0;
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($nama_barang) ?>
                        <input type="hidden" name="nama_barang[]" value="<?= htmlspecialchars($nama_barang) ?>">
                    </td>
                    <td><?= $stok_saat_ini ?> pcs</td>
                    <td>
                        <input type="number" name="kurang[]" value="0" min="0" max="<?= $stok_saat_ini ?>" style="width: 80px;">
                    </td>
                    <td>
                        <select name="keterangan[]" style="width: 130px;">
                            <option value="Terjual">Terjual</option>
                            <option value="Balik Stok">Balik Stok</option>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 20px;">
            <button type="submit" style="padding: 10px 30px; background-color: black; color: white; border: none; border-radius: 4px;">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
