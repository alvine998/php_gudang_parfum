<?php
include 'header.php';
include '../koneksi.php';

// Ambil daftar toko untuk filter
$toko_result = mysqli_query($conn, "SELECT id, nama_toko FROM toko ORDER BY nama_toko ASC");
$toko_list = [];
while ($row = mysqli_fetch_assoc($toko_result)) {
    $toko_list[] = $row;
}

// Ambil filter
$filter_toko = isset($_GET['id_toko']) ? intval($_GET['id_toko']) : '';
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

// Bangun kondisi WHERE
$where = "WHERE 1";
if ($filter_toko) {
    $where .= " AND lst.id_toko = $filter_toko";
}
if ($tanggal_awal && $tanggal_akhir) {
    $where .= " AND DATE(lst.created_at) BETWEEN '" . mysqli_real_escape_string($conn, $tanggal_awal) . "' AND '" . mysqli_real_escape_string($conn, $tanggal_akhir) . "'";
} elseif ($tanggal_awal) {
    $where .= " AND DATE(lst.created_at) >= '" . mysqli_real_escape_string($conn, $tanggal_awal) . "'";
} elseif ($tanggal_akhir) {
    $where .= " AND DATE(lst.created_at) <= '" . mysqli_real_escape_string($conn, $tanggal_akhir) . "'";
}

// Query data laporan terjual
$query = "SELECT lst.*, t.nama_toko, bp.nama_barang, bp.kode_barcode, bp.status, st.jumlah AS stok_terakhir
		  FROM laporan_stok_toko lst
		  JOIN toko t ON lst.id_toko = t.id
		  JOIN barcode_produk bp ON lst.id_barcode_produk = bp.id
		  JOIN stok_toko st ON lst.id_stok_toko = st.id
		  $where
		  ORDER BY lst.created_at DESC";
$result = mysqli_query($conn, $query);

?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow card-hover">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Laporan Barang Terjual</h4>
                            <small>Rekap barang terjual di seluruh toko</small>
                        </div>
                        <div class="col-auto">
                            <form method="POST" action="export-laporan-terjual.php" target="_blank" class="d-inline-block">
                                <input type="hidden" name="id_toko" value="<?= htmlspecialchars($filter_toko) ?>">
                                <input type="hidden" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                                <input type="hidden" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                                <button type="submit" class="btn btn-light btn-sm">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <form class="row mb-3" method="GET">
                        <div class="col-md-3">
                            <label class="form-label">Toko</label>
                            <select name="id_toko" class="form-select">
                                <option value="">Semua Toko</option>
                                <?php foreach ($toko_list as $toko): ?>
                                    <option value="<?= $toko['id'] ?>" <?= $filter_toko == $toko['id'] ? 'selected' : '' ?>><?= htmlspecialchars($toko['nama_toko']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                        </div>
                    </form>
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="produk-table">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>No</th>
                                    <th>Toko</th>
                                    <th>Nama Barang</th>
                                    <th>Kode Barcode</th>
                                    <th>Status</th>
                                    <th>Waktu Terjual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $total_terjual = 0;
                                if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): $total_terjual++; ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_toko']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                            <td><span class="badge text-dark px-2 py-1 fs-6"><?= htmlspecialchars($row['kode_barcode']) ?></span></td>
                                            <td><span class="badge bg-danger px-3 py-2">Terjual</span></td>
                                            <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data terjual.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($filter_toko && $total_terjual > 0): ?>
                        <div class="alert alert-info mt-4 text-end shadow-sm fw-bold">
                            Total Barang Terjual untuk Toko ini: <span class="text-primary fs-4"><?= $total_terjual ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>