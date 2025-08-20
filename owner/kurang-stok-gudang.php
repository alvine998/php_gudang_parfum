<?php include 'header.php'; ?>
<?php include '../koneksi.php'; ?>

<?php
$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM stok_gudang WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

// Ambil daftar toko (jika nanti mau digunakan)
$toko = mysqli_query($conn, "SELECT * FROM toko ORDER BY nama_toko ASC");
?>

<h2 style="text-align: center; margin-bottom: 30px;">➖ Kurangi Stok Gudang</h2>

<form method="POST" action="proses-kurang-stok.php">
  <input type="hidden" name="id" value="<?= $data['id'] ?>">

  <div class="table-wrapper">
    <table>
      <thead style="background-color: black; color: white;">
        <tr>
          <th>Varian Parfum</th>
          <th>Jumlah Saat Ini</th>
          <th>Kurangi Sebanyak (pcs)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= htmlspecialchars($data['nama_barang']) ?></td>
          <td><?= $data['jumlah'] ?> pcs</td>
          <td>
            <input type="number" name="kurangi" min="1" max="<?= $data['jumlah'] ?>" required style="width: 100%; padding: 6px;">
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div style="margin-top: 30px; text-align: center;">
    <label><strong>Keterangan</strong></label><br>
    <select name="keterangan" required style="padding: 8px; margin-top: 5px;">
      <option value="">-- Pilih Keterangan --</option>
      <option value="Stok Hilang">Stok Hilang</option>
      <option value="Barang Rusak">Barang Rusak</option>
    </select>
  </div>

  <br>
  <div style="text-align: center;">
    <button type="submit" style="padding: 10px 30px; background-color: #d9534f; color: white; border: none; border-radius: 6px; cursor: pointer;">
      Kurangi
    </button>
  </div>
</form>

</div> <!-- tutup .content -->
</body>
</html>
