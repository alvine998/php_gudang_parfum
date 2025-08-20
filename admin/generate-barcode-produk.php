<?php
include 'header.php';
include '../koneksi.php';

require '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_gudang = $_POST['id_gudang'];
    $jumlah = (int) $_POST['jumlah'];
    $username = $_SESSION['username'];

    // Ambil nama_barang dari stok_gudang
    $query = mysqli_query($conn, "SELECT nama_barang FROM stok_gudang WHERE id = '$id_gudang'");
    $data = mysqli_fetch_assoc($query);
    $nama_barang = $data['nama_barang'] ?? '';

    $id_tergenerate = [];

    for ($i = 0; $i < $jumlah; $i++) {
        $kode_unik = 'QR-' . Uuid::uuid4()->toString();

        mysqli_query($conn, "INSERT INTO barcode_produk 
            (id_gudang, nama_barang, kode_barcode, status, updated_at) 
            VALUES ('$id_gudang', '$nama_barang', '$kode_unik', '', NOW())");

        $id_tergenerate[] = mysqli_insert_id($conn);
    }

    // Redirect ke halaman cetak batch
    $id_string = implode(",", $id_tergenerate);
    header("Location: cetak-barcode-batch.php?ids=$id_string");
    exit;
}
?>

<div class="container mt-4">
    <h2>🎯 Generate Barcode Produk</h2>
    <form method="POST" class="mt-3">
        <div class="form-group mb-3">
            <label>Varian Parfum</label>
            <select name="id_gudang" class="form-control" required>
                <option value="">-- Pilih Varian --</option>
                <?php
                $stok = mysqli_query($conn, "SELECT id, nama_barang FROM stok_gudang ORDER BY nama_barang ASC");
                while ($row = mysqli_fetch_assoc($stok)) {
                    echo "<option value='{$row['id']}'>{$row['nama_barang']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group mb-3">
            <label>Jumlah Barcode</label>
            <input type="number" name="jumlah" class="form-control" min="1" required>
        </div>

        <button type="submit" class="btn btn-primary">🔄 Generate & Cetak</button>
    </form>
</div>