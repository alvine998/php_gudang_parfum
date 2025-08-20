<?php
include 'header.php';
include '../koneksi.php';

// Cek parameter ID
$id_toko = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_toko <= 0) {
    echo "<script>alert('ID Toko tidak valid!'); window.location.href='daftar-toko.php';</script>";
    exit;
}

// Ambil data toko
$query_toko = "SELECT * FROM toko WHERE id = ?";
$stmt_toko = $conn->prepare($query_toko);
$stmt_toko->bind_param("i", $id_toko);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();

if ($result_toko->num_rows === 0) {
    echo "<script>alert('Toko tidak ditemukan!'); window.location.href='daftar-toko.php';</script>";
    exit;
}

$data_toko = $result_toko->fetch_assoc();
$nama_toko = $data_toko['nama_toko'];

// Ambil filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Bangun kondisi WHERE untuk log aktivitas
$kondisi_log = "WHERE (aksi LIKE '%$nama_toko%' OR aksi LIKE '%{$nama_toko}:%')";
if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
    $kondisi_log .= " AND DATE(waktu) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
} elseif ($tanggal_awal !== '') {
    $kondisi_log .= " AND DATE(waktu) >= '$tanggal_awal'";
} elseif ($tanggal_akhir !== '') {
    $kondisi_log .= " AND DATE(waktu) <= '$tanggal_akhir'";
}

// Ambil log aktivitas terkait toko
$query_log = "SELECT * FROM log_aktivitas $kondisi_log ORDER BY waktu $sort";
$result_log = mysqli_query($conn, $query_log);

// Ambil statistik stok toko
$query_stok = "SELECT 
                COUNT(*) as total_produk,
                SUM(jumlah) as total_stok,
                MAX(updated_at) as last_update
                FROM stok_toko 
                WHERE id_toko = ?";
$stmt_stok = $conn->prepare($query_stok);
$stmt_stok->bind_param("i", $id_toko);
$stmt_stok->execute();
$result_stok = $stmt_stok->get_result();
$data_stok = $result_stok->fetch_assoc();

// Hitung total barang aktual dari barcode_produk
$status_toko = "di_toko - " . $nama_toko;
$query_barcode = "SELECT COUNT(*) as total_barang_aktual FROM barcode_produk WHERE status = ?";
$stmt_barcode = $conn->prepare($query_barcode);
$stmt_barcode->bind_param("s", $status_toko);
$stmt_barcode->execute();
$result_barcode = $stmt_barcode->get_result();
$data_barcode = $result_barcode->fetch_assoc();
?>

<style>
    .detail-container {
        width: 95%;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #007bff;
    }

    .info-card h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 14px;
        font-weight: bold;
    }

    .info-card p {
        margin: 5px 0;
        color: #666;
        font-size: 13px;
    }

    .log-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .log-table th,
    .log-table td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 13px;
    }

    .log-table th {
        background-color: #343a40;
        color: white;
        font-weight: bold;
    }

    .log-table tr:hover {
        background-color: #f5f5f5;
    }

    .filter-container {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .back-btn {
        display: inline-block;
        background-color: #6c757d;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .back-btn:hover {
        background-color: #5a6268;
        color: white;
    }
</style>

<a href="daftar-toko.php" class="back-btn">← Kembali ke Daftar Toko</a>

<div class="detail-container">
    <h2 style="color: #333; margin-bottom: 20px;">Detail Toko: <?= htmlspecialchars($nama_toko) ?></h2>

    <!-- Informasi Toko -->
    <div class="info-grid">
        <div class="info-card">
            <h4>📍 Informasi Toko</h4>
            <p><strong>Nama:</strong> <?= htmlspecialchars($data_toko['nama_toko']) ?></p>
            <p><strong>Alamat:</strong> <?= htmlspecialchars($data_toko['alamat_manual'] ?? 'Tidak ada') ?></p>
            <p><strong>Dibuat:</strong> <?= $data_toko['dibuat_pada'] ? date('d-m-Y H:i', strtotime($data_toko['dibuat_pada'])) : '-' ?></p>
            <p><strong>Maps:</strong> <a href="<?= htmlspecialchars($data_toko['lokasi_maps']) ?>" target="_blank">Lihat Lokasi</a></p>
        </div>

        <div class="info-card">
            <h4>📦 Statistik Stok</h4>
            <p><strong>Jenis Produk:</strong> <?= $data_stok['total_produk'] ?? 0 ?></p>
            <p><strong>Total Stok (Database):</strong> <?= $data_stok['total_stok'] ?? 0 ?> pcs</p>
            <p><strong>Total Barang Aktual:</strong> <?= $data_barcode['total_barang_aktual'] ?? 0 ?> pcs</p>
            <p><strong>Update Terakhir:</strong> <?= $data_stok['last_update'] ? date('d-m-Y H:i', strtotime($data_stok['last_update'])) : '-' ?></p>
            <div style="margin-top: 10px;">
                <a href="detail-stok-toko.php?id_toko=<?= $id_toko ?>" style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;">📋 Lihat Detail Stok</a>
            </div>
        </div>

        <div class="info-card">
            <h4>📊 QR Code Toko</h4>
            <div style="text-align: center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($nama_toko) ?>&size=120x120" alt="QR Code Toko" style="border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
    </div>
</div>

<!-- Filter Log Aktivitas -->
<div class="detail-container">
    <h3 style="color: #333; margin-bottom: 15px;">📋 Log Aktivitas Toko</h3>

    <div class="filter-container">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="hidden" name="id" value="<?= $id_toko ?>">

            <label>Dari:</label>
            <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">

            <label>Sampai:</label>
            <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">

            <label>Urutkan:</label>
            <select name="sort" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="desc" <?= ($_GET['sort'] ?? '') === 'desc' ? 'selected' : '' ?>>Terbaru</option>
                <option value="asc" <?= ($_GET['sort'] ?? '') === 'asc' ? 'selected' : '' ?>>Terlama</option>
            </select>

            <button type="submit" style="padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">🔍 Filter</button>
            <a href="detail-toko.php?id=<?= $id_toko ?>" style="padding: 6px 12px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">🔄 Reset</a>
        </form>
    </div>

    <!-- Search Log -->
    <div style="margin-bottom: 15px;">
        <input type="text" id="searchLog" placeholder="Cari dalam log aktivitas..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <!-- Tabel Log Aktivitas -->
    <div style="overflow-x: auto;">
        <table class="log-table" id="logTable">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Username</th>
                    <th>Aktivitas</th>
                    <th>Tabel</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_logs = 0;
                while ($log = mysqli_fetch_assoc($result_log)):
                    $total_logs++;
                ?>
                    <tr>
                        <td><?= date('d-m-Y H:i:s', strtotime($log['waktu'])) ?></td>
                        <td><span style="color: #007bff; font-weight: bold;"><?= htmlspecialchars($log['username']) ?></span></td>
                        <td><?= htmlspecialchars($log['aksi']) ?></td>
                        <td><span style="background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars($log['tabel']) ?></span></td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($total_logs === 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666; padding: 20px;">
                            Tidak ada log aktivitas ditemukan untuk toko ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_logs > 0): ?>
        <div style="text-align: center; margin-top: 15px; color: #666; font-size: 13px;">
            Total: <?= $total_logs ?> aktivitas ditemukan
        </div>
    <?php endif; ?>
</div>

<script>
    // Search functionality
    document.getElementById("searchLog").addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#logTable tbody tr");

        rows.forEach(row => {
            const cells = row.getElementsByTagName("td");
            let found = false;

            for (let i = 0; i < cells.length; i++) {
                if (cells[i].innerText.toLowerCase().includes(filter)) {
                    found = true;
                    break;
                }
            }

            row.style.display = found ? "" : "none";
        });
    });

    // Auto refresh setiap 30 detik jika tidak ada filter
    <?php if (empty($tanggal_awal) && empty($tanggal_akhir)): ?>
        setInterval(function() {
            // Hanya refresh jika tidak ada pencarian aktif
            const searchValue = document.getElementById("searchLog").value;
            if (!searchValue) {
                location.reload();
            }
        }, 30000);
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>