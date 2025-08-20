<?php
include '../koneksi.php';
include 'header.php';

$id_toko = isset($_GET['id_toko']) ? (int)$_GET['id_toko'] : 0;

// Ambil nama toko
$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko"));
$nama_toko = $toko ? $toko['nama_toko'] : 'Toko Tidak Dikenal';

// Ambil semua varian dari stok_gudang
$varian_query = mysqli_query($conn, "SELECT DISTINCT nama_barang FROM stok_gudang");

// Siapkan data varian dan jumlah stok toko
$data_varian = [];
while ($v = mysqli_fetch_assoc($varian_query)) {
    $varian = $v['nama_barang'];

    $stok_query = mysqli_query($conn, "
        SELECT jumlah 
        FROM stok_toko 
        WHERE id_toko = $id_toko AND nama_barang = '$varian'
    ");
    $stok_data = mysqli_fetch_assoc($stok_query);
    $jumlah = $stok_data ? $stok_data['jumlah'] : 0;

    $data_varian[] = [
        'nama_barang' => $varian,
        'jumlah' => $jumlah
    ];
}
?>

<h2 style="text-align: center;">Stok Varian di <strong><?= htmlspecialchars($nama_toko) ?></strong></h2>

<div style="display: flex; justify-content: center;">
  <table style="width: 100%; max-width: 500px; border-collapse: collapse; font-size: 13px;">
    <thead style="background-color: #333; color: white;">
      <tr>
        <th style="padding: 8px; text-align: left;">Nama Varian</th>
        <th style="padding: 8px; text-align: center;">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data_varian as $row): ?>
        <tr style="border-bottom: 1px solid #ccc;">
          <td style="padding: 8px;"><?= htmlspecialchars($row['nama_barang']) ?></td>
          <td style="padding: 8px; text-align: center;"><?= $row['jumlah'] ?> pcs</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div style="text-align: center; margin-top: 20px;">
  <a href="stok-toko.php" style="text-decoration: none; color: #00bfff;">&larr; Kembali ke Daftar Toko</a>
</div>






<?php include 'footer.php'; ?>
