<?php
include 'header.php';
include '../koneksi.php';

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal, $format = 'lengkap')
{
    if (!$tanggal) return '-';

    $bulan_indonesia = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    $bulan_pendek = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ags',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    if ($format == 'lengkap') {
        $bulan = $bulan_indonesia[(int)date('m', $timestamp)];
        return $hari . ' ' . $bulan . ' ' . $tahun . ' ' . $jam;
    } elseif ($format == 'pendek') {
        $bulan = $bulan_pendek[(int)date('m', $timestamp)];
        return $hari . ' ' . $bulan . ' ' . $tahun;
    } else {
        $bulan = $bulan_indonesia[(int)date('m', $timestamp)];
        return $hari . ' ' . $bulan . ' ' . $tahun;
    }
}

// Ambil filter
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Bangun kondisi WHERE
$kondisi = "WHERE 1";
if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
    $kondisi .= " AND DATE(dibuat_pada) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
} elseif ($tanggal_awal !== '') {
    $kondisi .= " AND DATE(dibuat_pada) >= '$tanggal_awal'";
} elseif ($tanggal_akhir !== '') {
    $kondisi .= " AND DATE(dibuat_pada) <= '$tanggal_akhir'";
}

// Ambil data toko dengan last visit
$query = "SELECT t.*, 
          (SELECT MAX(la.waktu) 
           FROM log_aktivitas la 
           WHERE la.aksi LIKE CONCAT('%', t.nama_toko, '%') 
           OR la.aksi LIKE CONCAT('%', t.nama_toko, ':%')
          ) as last_visit
          FROM toko t 
          $kondisi 
          ORDER BY last_visit $sort";

$result = mysqli_query($conn, $query);
$data_toko = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_toko[] = $row;
}
?>

<?php if (isset($_GET['hapus']) && $_GET['hapus'] == 'success'): ?>
    <script>
        alert("Toko berhasil dihapus!");
    </script>
<?php endif; ?>

<style>
    .badge-sm {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .qr-container:hover {
        transform: scale(1.05);
        transition: transform 0.2s ease;
    }

    .btn-group .btn-sm {
        font-size: 0.75rem;
    }

    .last-visit-indicator {
        position: relative;
    }

    .last-visit-indicator::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: currentColor;
    }
</style>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">
                        <i class="fas fa-store text-primary"></i> Daftar Mitra XLPerfume
                    </h2>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <!-- <a href="tambah-toko.php" class="btn btn-success btn-lg mb-2">
                            <i class="fas fa-plus"></i> Tambah Toko
                        </a> -->

                        <!-- Form Ekspor PDF -->
                        <form method="POST" action="export-toko-pdf.php" class="mb-2">
                            <input type="hidden" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                            <input type="hidden" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort'] ?? 'desc') ?>">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-file-pdf"></i> Ekspor ke PDF
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter text-info"></i> Filter & Pencarian
                        </h5>
                        <?php if (!empty($tanggal_awal) || !empty($tanggal_akhir)): ?>
                            <div class="badge bg-info">
                                <i class="fas fa-info-circle"></i> Filter Aktif
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Tanggal & Urutkan -->
                    <form method="GET" class="mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Dari:</label>
                                <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Sampai:</label>
                                <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Urutkan:</label>
                                <select name="sort" class="form-select">
                                    <option value="desc" <?= ($_GET['sort'] ?? '') === 'desc' ? 'selected' : '' ?>>Terbaru</option>
                                    <option value="asc" <?= ($_GET['sort'] ?? '') === 'asc' ? 'selected' : '' ?>>Terlama</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="daftar-toko.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <small class="text-muted">Total: <?= count($data_toko) ?> toko</small>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Search Bar -->
                    <div class="row">
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="searchInput" placeholder="Cari Nama Toko..." class="form-control form-control-lg">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Toko -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table"></i> Data Toko
                    </h5>
                    <span id="totalCount" class="badge bg-light text-dark fs-6"></span>
                </div>
                <div class="card-body p-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-3">
                        <div class="d-flex align-items-center gap-2">
                            <label for="perPageSelect" class="mb-0 fw-bold">Tampilkan</label>
                            <select id="perPageSelect" class="form-select form-select-sm w-auto">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="all">Semua</option>
                            </select>
                            <span class="ms-2">toko per halaman</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tokoTable" class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 120px;">
                                        <i class="fas fa-qrcode"></i> QR Code
                                    </th>
                                    <th style="min-width: 200px;">
                                        <i class="fas fa-store"></i> Nama Toko
                                    </th>
                                    <th style="min-width: 150px;">
                                        <i class="fas fa-map-marker-alt"></i> Lokasi
                                    </th>
                                    <th style="min-width: 130px;">
                                        <i class="fas fa-clock"></i> Last Visit
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_toko as $row): ?>
                                    <tr class="align-middle">
                                        <td class="text-center p-2">
                                            <div class="qr-container" style="background: #f8f9fa; border-radius: 8px; padding: 5px; display: inline-block; cursor:pointer;" onclick="printQrToko(<?= $row['id'] ?>)">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($row['nama_toko']) ?>&size=80x80"
                                                    alt="QR Code" class="img-fluid" style="border-radius: 4px;">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($row['nama_toko']) ?></div>
                                            <small class="text-muted">ID: #<?= $row['id'] ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($row['lokasi_maps']) ?>" target="_blank"
                                                class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-external-link-alt"></i> Lihat Lokasi
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($row['last_visit']): ?>
                                                <?php
                                                $last_visit_time = strtotime($row['last_visit']);
                                                $now = time();
                                                $diff_days = floor(($now - $last_visit_time) / (60 * 60 * 24));

                                                if ($diff_days == 0) {
                                                    $status_class = 'text-success';
                                                    $badge_class = 'bg-success';
                                                    $status_text = 'Hari ini';
                                                    $tooltip_text = 'Aktif hari ini';
                                                } elseif ($diff_days <= 7) {
                                                    $status_class = 'text-primary';
                                                    $badge_class = 'bg-primary';
                                                    $status_text = $diff_days . ' hari lalu';
                                                    $tooltip_text = 'Aktif dalam seminggu terakhir';
                                                } elseif ($diff_days <= 30) {
                                                    $status_class = 'text-warning';
                                                    $badge_class = 'bg-warning';
                                                    $status_text = $diff_days . ' hari lalu';
                                                    $tooltip_text = 'Aktif dalam sebulan terakhir';
                                                } else {
                                                    $status_class = 'text-danger';
                                                    $badge_class = 'bg-danger';
                                                    $status_text = $diff_days . ' hari lalu';
                                                    $tooltip_text = 'Tidak aktif lebih dari sebulan';
                                                }
                                                ?>
                                                <div class="text-center" title="<?= $tooltip_text ?>" data-bs-toggle="tooltip">
                                                    <div class="fw-bold <?= $status_class ?>"><?= formatTanggalIndonesia($row['last_visit'], 'pendek') ?></div>
                                                    <small class="text-muted"><?= date('H:i', strtotime($row['last_visit'])) ?> WIB</small>
                                                    <div class="mt-1">
                                                        <span class="badge <?= $badge_class ?> badge-sm"><?= $status_text ?></span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center" title="Belum pernah ada aktivitas di toko ini" data-bs-toggle="tooltip">
                                                    <div class="text-muted mb-1">
                                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                                    </div>
                                                    <small class="text-muted">Belum ada aktivitas</small>
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary badge-sm">Tidak aktif</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination bawah -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <span id="pageInfo" class="text-muted fw-bold"></span>
                            </div>
                            <nav>
                                <div class="btn-group" role="group">
                                    <button onclick="changePage(-1)" class="btn btn-outline-secondary" id="prevBtn" title="Halaman Sebelumnya">
                                        <i class="fas fa-chevron-left"></i> Prev
                                    </button>
                                    <span class="btn btn-light disabled" id="currentPageBtn">
                                        <span id="currentPageDisplay">1</span>
                                    </span>
                                    <button onclick="changePage(1)" class="btn btn-outline-secondary" id="nextBtn" title="Halaman Selanjutnya">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Aktivitas Toko -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-chart-line"></i> Log Aktivitas: <span id="modalNamaToko" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeDetailModal()" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Filter Section -->
                <div class="card bg-light mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-filter text-info"></i> Filter Data
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Dari:</label>
                                <input type="date" id="modalTanggalAwal" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Sampai:</label>
                                <input type="date" id="modalTanggalAkhir" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Urutkan:</label>
                                <select id="modalSort" class="form-select">
                                    <option value="desc">Terbaru</option>
                                    <option value="asc">Terlama</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button onclick="filterModalData()" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button onclick="resetModalFilter()" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="modalSearch" placeholder="Cari dalam log aktivitas..." class="form-control">
                    </div>
                </div>

                <!-- Loading -->
                <div id="modalLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Memuat data...</div>
                </div>

                <!-- Content -->
                <div id="modalContent">
                    <!-- Content akan dimuat di sini -->
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentTokoId = null;
    let currentNamaToko = null;
    let detailModal = null;

    // Initialize modal when document ready
    document.addEventListener('DOMContentLoaded', function() {
        detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        updateTotalCount();
        showPage(currentPage);
    });

    function showDetailModal(id_toko, nama_toko) {
        currentTokoId = id_toko;
        currentNamaToko = nama_toko;

        document.getElementById('modalNamaToko').textContent = nama_toko;

        // Reset filter
        document.getElementById('modalTanggalAwal').value = '';
        document.getElementById('modalTanggalAkhir').value = '';
        document.getElementById('modalSort').value = 'desc';
        document.getElementById('modalSearch').value = '';

        // Show modal
        detailModal.show();

        // Load data
        loadModalData();
    }

    function closeDetailModal() {
        detailModal.hide();
        currentTokoId = null;
        currentNamaToko = null;
    }

    function loadModalData() {
        if (!currentTokoId) return;

        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').innerHTML = '';

        const tanggalAwal = document.getElementById('modalTanggalAwal').value;
        const tanggalAkhir = document.getElementById('modalTanggalAkhir').value;
        const sort = document.getElementById('modalSort').value;

        const params = new URLSearchParams({
            id_toko: currentTokoId,
            nama_toko: currentNamaToko,
            tanggal_awal: tanggalAwal,
            tanggal_akhir: tanggalAkhir,
            sort: sort
        });

        fetch(`get-log-aktivitas-toko.php?${params}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalLoading').style.display = 'none';

                if (data.status === 'success') {
                    displayModalData(data.logs, data.stats);
                } else {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle"></i> Error: ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan saat memuat data
                    </div>
                `;
            });
    }

    function displayModalData(logs, stats) {
        let html = '';

        // Statistik

        // Tabel log
        html += `
            <div class="table-responsive">
                <table id="modalLogTable" class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="fas fa-clock"></i> Waktu</th>
                            <th><i class="fas fa-user"></i> Username</th>
                            <th><i class="fas fa-tasks"></i> Aktivitas</th>
                            <th><i class="fas fa-database"></i> Tabel</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (logs.length > 0) {
            logs.forEach(log => {
                html += `
                    <tr>
                        <td><small class="text-muted">${log.waktu}</small></td>
                        <td><span class="badge bg-primary">${log.username}</span></td>
                        <td><small>${log.aksi}</small></td>
                        <td><span class="badge bg-secondary">${log.tabel}</span></td>
                    </tr>
                `;
            });
        } else {
            html += `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        Tidak ada log aktivitas ditemukan untuk toko ini.
                    </td>
                </tr>
            `;
        }

        html += '</tbody></table></div>';

        if (logs.length > 0) {
            html += `
                <div class="text-center mt-3">
                    <span class="badge bg-info fs-6">Total: ${logs.length} aktivitas ditemukan</span>
                </div>
            `;
        }

        document.getElementById('modalContent').innerHTML = html;

        // Setup search
        setupModalSearch();
    }

    function setupModalSearch() {
        const searchInput = document.getElementById('modalSearch');
        // Remove existing event listeners
        searchInput.replaceWith(searchInput.cloneNode(true));

        document.getElementById('modalSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('modalLogTable');
            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(filter)) {
                        found = true;
                        break;
                    }
                }

                rows[i].style.display = found ? '' : 'none';
            }
        });
    }

    function filterModalData() {
        loadModalData();
    }

    function resetModalFilter() {
        document.getElementById('modalTanggalAwal').value = '';
        document.getElementById('modalTanggalAkhir').value = '';
        document.getElementById('modalSort').value = 'desc';
        document.getElementById('modalSearch').value = '';
        loadModalData();
    }

    // Pagination
    let currentPage = 1;
    let rowsPerPage = 10;
    const perPageSelect = document.getElementById('perPageSelect');
    const tokoTableBody = document.querySelector('#tokoTable tbody');
    let allRows = Array.from(tokoTableBody.querySelectorAll('tr'));
    let filteredRows = allRows;

    function renderTablePage(page) {
        const totalRows = filteredRows.length;
        const totalPages = rowsPerPage === Number.MAX_SAFE_INTEGER ? 1 : Math.ceil(totalRows / rowsPerPage);
        if (page < 1) page = 1;
        if (page > totalPages && totalPages > 0) page = totalPages;
        currentPage = page;
        tokoTableBody.innerHTML = '';
        let startIndex = rowsPerPage === Number.MAX_SAFE_INTEGER ? 0 : (page - 1) * rowsPerPage;
        let endIndex = rowsPerPage === Number.MAX_SAFE_INTEGER ? totalRows : startIndex + rowsPerPage;
        for (let i = startIndex; i < endIndex && i < totalRows; i++) {
            tokoTableBody.appendChild(filteredRows[i]);
        }
        // Update info
        if (totalPages > 0) {
            document.getElementById('pageInfo').textContent = `Menampilkan ${Math.min(startIndex + 1, totalRows)} - ${Math.min(endIndex, totalRows)} dari ${totalRows} toko`;
            document.getElementById('currentPageDisplay').textContent = page;
        } else {
            document.getElementById('pageInfo').textContent = 'Tidak ada data yang ditampilkan';
            document.getElementById('currentPageDisplay').textContent = '0';
        }
        // Update button states
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = page >= totalPages || totalPages === 0;
    }

    function updateFilteredRows() {
        filteredRows = allRows.filter(row => row.style.display !== 'none');
    }

    // Search bar for main table
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        allRows.forEach(row => {
            const namaToko = row.cells[1].innerText.toLowerCase();
            row.style.display = namaToko.includes(filter) ? '' : 'none';
        });
        updateFilteredRows();
        currentPage = 1;
        renderTablePage(currentPage);
        updateTotalCount(filteredRows.length);
    });

    perPageSelect.addEventListener('change', function() {
        if (this.value === 'all') {
            rowsPerPage = Number.MAX_SAFE_INTEGER;
        } else {
            rowsPerPage = parseInt(this.value);
        }
        currentPage = 1;
        renderTablePage(currentPage);
    });

    function changePage(delta) {
        const totalRows = filteredRows.length;
        const totalPages = rowsPerPage === Number.MAX_SAFE_INTEGER ? 1 : Math.ceil(totalRows / rowsPerPage);
        let newPage = currentPage + delta;
        if (newPage < 1) newPage = 1;
        if (newPage > totalPages) newPage = totalPages;
        renderTablePage(newPage);
    }

    function updateTotalCount(count = null) {
        if (count === null) count = filteredRows.length;
        document.getElementById('totalCount').textContent = `${count} Toko`;
    }

    // Inisialisasi awal
    updateFilteredRows();
    renderTablePage(currentPage);
    updateTotalCount();

    function printQrToko(idToko) {
        window.open('cetak-barcode-produk.php?id_toko=' + idToko, '_blank');
    }
</script>

<?php include 'footer.php'; ?>