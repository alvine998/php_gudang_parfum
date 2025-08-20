<?php
include 'header.php';
include '../koneksi.php';

if (!isset($_GET['id'])) {
  echo "<script>alert('ID tidak ditemukan.'); window.location.href='stok-gudang.php';</script>";
  exit;
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM stok_gudang WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
  echo "<script>alert('Data tidak ditemukan.'); window.location.href='stok-gudang.php';</script>";
  exit;
}
?>

<h2 style="text-align: center; margin-bottom: 30px;">➕ Tambah Stok Gudang</h2>

<form method="POST" action="proses-tambah-stok.php">
  <input type="hidden" name="id" value="<?= $data['id'] ?>">

  <div class="table-wrapper">
    <table>
      <thead style="background-color: black; color: white;">
        <tr>
          <th>Varian Parfum</th>
          <th>Jumlah Saat Ini</th>
          <th>Tambah Sebanyak (pcs)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= htmlspecialchars($data['nama_barang']) ?></td>
          <td><?= $data['jumlah'] ?> pcs</td>
          <td>
            <input type="number" name="tambah" min="1" required style="width: 100%; padding: 6px;">
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div style="margin-top: 30px; text-align: center;">
    <label><strong>Keterangan</strong></label><br>
    <select name="keterangan" required style="padding: 8px; margin-top: 5px;">
      <option value="Produksi Baru">Produksi Baru</option>
    </select>

    <br><br>
    <button type="submit" style="padding: 10px 20px; background-color: green; color: white; border: none; border-radius: 6px; cursor: pointer;">
      Simpan Tambahan
    </button>
  </div>
</form>

</div>
</body>

</html>