<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard-spg.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Daftar Toko</li>
                </ol>
            </nav>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Data Toko
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Mobile Cards View (Hanya tampil di mobile) -->
                    <div class="d-block d-md-none" id="mobileView">
                        <?php
                        include '../koneksi.php';
                        $username = $_SESSION['username'];
                        $query = "SELECT id, nama_toko, lokasi_maps, alamat_manual, dibuat_pada FROM toko where dibuat_oleh = '$username' ORDER BY dibuat_pada DESC";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0):
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): ?>
                                <div class="card mb-3 border-start border-dark border-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0 text-dark fw-bold">
                                                <i class="fas fa-store me-1"></i>
                                                <?= htmlspecialchars($row['nama_toko']) ?>
                                            </h6>
                                            <span class="badge bg-light text-dark">#<?= $no ?></span>
                                        </div>

                                        <?php if (!empty($row['alamat_manual'])): ?>
                                            <p class="card-text text-muted small mb-2">
                                                <i class="fas fa-map-signs me-1"></i>
                                                <?= htmlspecialchars($row['alamat_manual']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Dibuat:</small>
                                                <small class="fw-semibold"><?= date('d/m/Y', strtotime($row['dibuat_pada'])) ?></small>
                                            </div>
                                            <div class="col-6">
                                                <?php if (!empty($row['lokasi_maps'])): ?>
                                                    <a href="<?= htmlspecialchars($row['lokasi_maps']) ?>" target="_blank"
                                                        class="btn btn-outline-info btn-sm w-100">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <small> Lokasi</small>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                        <small>No Maps</small>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <a href="cetak-qr-toko.php?id=<?= $row['id'] ?>"
                                                class="btn btn-primary btn-sm" target="_blank">
                                                <i class="fas fa-print me-1"></i>
                                                Cetak QR Code
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $no++;
                            endwhile;
                        else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-store fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum Ada Toko</h5>
                                <p class="text-muted">Belum ada toko yang terdaftar.</p>
                                <a href="tambah-toko.php" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Tambah Toko Baru
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table View (Sembunyikan di mobile) -->
                    <div class="d-none d-md-block table-responsive">
                        <table id="tokoTable" class="table table-hover table-striped" style="width: 100%;">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 60px;">
                                        <i class="fas fa-hashtag"></i> No
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-store"></i> Nama Toko
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <i class="fas fa-map-marker-alt"></i> Maps
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-map-signs"></i> Alamat
                                    </th>
                                    <th class="text-center" style="width: 140px;">
                                        <i class="fas fa-calendar"></i> Dibuat
                                    </th>
                                    <th class="text-center" style="width: 120px;">
                                        <i class="fas fa-cogs"></i> Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reset query untuk table view
                                $result = mysqli_query($conn, $query);
                                $no = 1;

                                if (mysqli_num_rows($result) > 0):
                                    while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-primary"><?= $no ?></td>
                                            <td class="text-center">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_toko']) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($row['lokasi_maps'])): ?>
                                                    <a href="<?= htmlspecialchars($row['lokasi_maps']) ?>" target="_blank"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span class="d-none d-lg-inline ms-1 text-white">Lokasi</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-start">
                                                <?php if (!empty($row['alamat_manual'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($row['alamat_manual']) ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="small">
                                                    <div><?= date('d/m/Y', strtotime($row['dibuat_pada'])) ?></div>
                                                    <small class="text-muted"><?= date('H:i', strtotime($row['dibuat_pada'])) ?></small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="cetak-qr-toko.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-primary btn-sm"
                                                    target="_blank"
                                                    title="Cetak QR Code">
                                                    <i class="fas fa-print"></i>
                                                    <span class="d-none d-lg-inline ms-1">QR</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                        $no++;
                                    endwhile;
                                else: ?>
                                    <tr id="empty-row">
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-store fa-3x mb-3 text-light"></i>
                                                <h5>Belum Ada Toko</h5>
                                                <p>Belum ada toko yang terdaftar. Klik tombol 'Tambah Toko Baru' untuk menambahkan toko pertama.</p>
                                                <a href="tambah-toko.php" class="btn btn-success">
                                                    <i class="fas fa-plus"></i> Tambah Toko Baru
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Hanya inisialisasi DataTable untuk desktop view
        if (window.innerWidth >= 768) {
            // Cek apakah table memiliki data
            var hasData = $('#tokoTable tbody tr').length > 0 && !$('#empty-row').length;

            if (hasData) {
                $('#tokoTable').DataTable({
                    responsive: false, // Disable responsive karena kita handle manual
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                    },
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, 50, -1],
                        [5, 10, 25, 50, "Semua"]
                    ],
                    columnDefs: [{
                        targets: [0, 5], // Kolom No dan Aksi
                        orderable: false,
                        searchable: false
                    }],
                    order: [
                        [1, 'asc']
                    ], // Sort by nama toko
                    scrollX: true, // Enable horizontal scroll untuk table yang lebar
                    autoWidth: false
                });
            }
        }

        // Handle resize untuk switch antara mobile dan desktop view
        $(window).resize(function() {
            if (window.innerWidth < 768) {
                // Mobile: destroy DataTable jika ada
                if ($.fn.DataTable.isDataTable('#tokoTable')) {
                    $('#tokoTable').DataTable().destroy();
                }
            } else {
                // Desktop: reinitialize DataTable jika belum ada
                if (!$.fn.DataTable.isDataTable('#tokoTable')) {
                    var hasData = $('#tokoTable tbody tr').length > 0 && !$('#empty-row').length;
                    if (hasData) {
                        $('#tokoTable').DataTable({
                            responsive: false,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                            },
                            pageLength: 10,
                            lengthMenu: [
                                [5, 10, 25, 50, -1],
                                [5, 10, 25, 50, "Semua"]
                            ],
                            columnDefs: [{
                                targets: [0, 5],
                                orderable: false,
                                searchable: false
                            }],
                            order: [
                                [1, 'asc']
                            ],
                            scrollX: true,
                            autoWidth: false
                        });
                    }
                }
            }
        });
    });
</script>

<style>
    /* Custom responsive styles */
    @media (max-width: 767.98px) {
        .container {
            padding-left: 10px;
            padding-right: 10px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header h5 {
            font-size: 1.1rem;
        }

        /* Mobile card styling */
        .card .card-body {
            padding: 1rem;
        }

        .card-title {
            font-size: 1rem;
            line-height: 1.3;
        }

        .btn-sm {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {

        /* Tablet view adjustments */
        .table th,
        .table td {
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    }

    @media (min-width: 992px) {

        /* Desktop view */
        .table th,
        .table td {
            padding: 0.75rem;
        }
    }

    /* DataTable custom responsive */
    @media (max-width: 991.98px) {

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            text-align: center;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            text-align: center;
            margin-top: 1rem;
        }
    }

    /* Ensure proper mobile card spacing */
    .border-start.border-primary.border-3 {
        border-left-width: 4px !important;
    }

    /* Improved mobile button styling */
    @media (max-width: 767.98px) {
        .btn {
            border-radius: 8px;
        }

        .btn-outline-info:hover {
            color: #fff;
            background-color: #0dcaf0;
            border-color: #0dcaf0;
        }
    }
</style>

<?php include 'footer.php'; ?>