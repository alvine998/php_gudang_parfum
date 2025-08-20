<?php
// Debug mode untuk development
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'header.php';

// Debug session
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'owner') {
    echo "<script>console.error('Session tidak valid:', " . json_encode($_SESSION ?? []) . ");</script>";
}
?>

<script>
    console.log('Daftar Produk page loaded');
    console.log('Session check:', {
        hasUsername: <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>,
        role: '<?php echo $_SESSION['role'] ?? 'not_set'; ?>'
    });
</script>

<style>
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .status-pending {
        background-color: #ffc107;
    }

    .status-di_gudang {
        background-color: #28a745;
    }

    .status-terjual {
        background-color: #dc3545;
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .modal-xl {
        max-width: 95%;
    }

    @media (max-width: 768px) {
        .modal-xl {
            max-width: 98%;
        }
    }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow card-hover">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="mb-0"><i class="fas fa-list"></i> Daftar Produk</h4>
                            <small>Kelola dan pantau semua produk dalam sistem</small>
                        </div>
                        <div class="col-auto">

                            <button class="btn btn-light btn-sm" onclick="refreshTable()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">


                    <!-- Filter dan Search -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filter-status" class="form-label">Filter Status:</label>
                            <select class="form-select" id="filter-status">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="di_gudang">Di Gudang</option>
                                <option value="di_spg">Di SPG</option>
                                <option value="di_toko">Di Toko</option>
                                <option value="di_collecting">Di Collecting</option>
                                <option value="terjual">Terjual</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter-nama" class="form-label">Cari Nama Barang:</label>
                            <input type="text" class="form-control" id="filter-nama" placeholder="Masukkan nama barang...">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-tanggal" class="form-label">Filter Tanggal:</label>
                            <input type="date" class="form-control" id="filter-tanggal">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-secondary me-2" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filter
                            </button>
                            <button class="btn btn-primary" onclick="loadProdukData(1)">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </div>

                    <!-- Filter Status Indicator -->
                    <div id="filter-indicator" class="mb-3" style="display: none;">
                        <div class="alert alert-info alert-dismissible">
                            <span id="filter-text">Filter aktif</span>
                            <button type="button" class="btn-close" onclick="clearFilters()"></button>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loading" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data produk...</p>
                    </div>

                    <!-- Tabel Produk -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="produk-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Kode Barcode</th>
                                    <th>Status</th>
                                    <th>Tanggal Update</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="produk-tbody">
                                <!-- Data akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center" id="pagination">
                            <!-- Pagination akan dimuat via AJAX -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Produk -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle"></i> Detail Produk & History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Info Produk -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-6 col-xl-6 col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-box"></i> Informasi Produk</h6>
                            </div>
                            <div class="card-body overflow-auto">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Nama Barang:</strong></td>
                                        <td id="modal-nama-barang">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kode Barcode:</strong></td>
                                        <td id="modal-kode-barcode" class="font-monospace">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span id="modal-status" class="badge">-</span>
                                            <div id="modal-status-detail" class="mt-1 small text-muted" style="display: none;"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat:</strong></td>
                                        <td id="modal-created-at">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Diupdate:</strong></td>
                                        <td id="modal-updated-at">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-6 col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Statistik</h6>
                            </div>
                            <div class="card-body">
                                <div id="statistik-produk">
                                    <!-- Statistik akan dimuat via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Log -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0 text-white"><i class="fas fa-history"></i> History Aktivitas</h6>
                    </div>
                    <div class="card-body">
                        <div id="loading-history" class="text-center" style="display: none;">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Loading history...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Tanggal</th>
                                        <th width="15%">User</th>
                                        <th>Aktivitas</th>
                                    </tr>
                                </thead>
                                <tbody id="history-tbody">
                                    <!-- History akan dimuat via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;

    // Load data saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        loadProdukData();
        loadStatistik();

        // Event listeners untuk filter
        document.getElementById('filter-status').addEventListener('change', function() {
            console.log('Status filter changed:', this.value);
            loadProdukData(1); // Reset ke halaman 1
        });

        document.getElementById('filter-nama').addEventListener('input', debounce(function() {
            console.log('Nama filter changed:', document.getElementById('filter-nama').value);
            loadProdukData(1); // Reset ke halaman 1
        }, 300));

        document.getElementById('filter-tanggal').addEventListener('change', function() {
            console.log('Tanggal filter changed:', this.value);
            loadProdukData(1); // Reset ke halaman 1
        });
    });

    // Load statistik
    function loadStatistik() {
        fetch('get-produk-statistik.php')
            .then(response => {
                console.log('Statistik response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw statistik response:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed statistik data:', data);

                    if (data.status === 'success') {
                        document.getElementById('stat-pending').textContent = data.data.pending || 0;
                        document.getElementById('stat-di-gudang').textContent = data.data.di_gudang || 0;
                        document.getElementById('stat-di-spg').textContent = data.data.di_spg || 0;
                        document.getElementById('stat-di-toko').textContent = data.data.di_toko || 0;
                        document.getElementById('stat-di-collecting').textContent = data.data.di_collecting || 0;
                        document.getElementById('stat-terjual').textContent = data.data.terjual || 0;
                        document.getElementById('stat-total').textContent = data.data.total || 0;
                    } else {
                        console.error('Statistik error:', data.message);
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error for statistik:', parseError);
                    console.error('Raw text that failed to parse:', text);
                }
            })
            .catch(error => {
                console.error('Error loading statistik:', error);
            });
    } // Debounce function untuk search
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Load data produk
    function loadProdukData(page = 1) {
        currentPage = page;

        const filterStatus = document.getElementById('filter-status').value;
        const filterNama = document.getElementById('filter-nama').value.trim();
        const filterTanggal = document.getElementById('filter-tanggal').value;

        // Update filter indicator
        updateFilterIndicator(filterStatus, filterNama, filterTanggal);

        console.log('Loading data with filters:', {
            page: page,
            status: filterStatus,
            nama: filterNama,
            tanggal: filterTanggal
        });

        document.getElementById('loading').style.display = 'block';
        document.getElementById('produk-tbody').innerHTML = '';

        const params = new URLSearchParams();
        params.append('page', page);

        if (filterStatus !== '') {
            params.append('status', filterStatus);
        }

        if (filterNama !== '') {
            params.append('nama', filterNama);
        }

        if (filterTanggal !== '') {
            params.append('tanggal', filterTanggal);
        }

        console.log('API URL:', 'get-produk-data.php?' + params.toString());

        fetch('get-produk-data.php?' + params.toString())
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response (first 300 chars):', text.substring(0, 300));

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', {
                        status: data.status,
                        dataCount: data.data ? data.data.length : 0,
                        pagination: data.pagination
                    });

                    document.getElementById('loading').style.display = 'none';

                    if (data.status === 'success') {
                        displayProdukData(data.data);
                        updatePagination(data.pagination);
                    } else {
                        console.error('API Error:', data.message);
                        showAlert('error', data.message);
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Raw text that failed to parse:', text);
                    document.getElementById('loading').style.display = 'none';
                    showAlert('error', 'Response tidak valid dari server');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Fetch Error:', error);
                showAlert('error', 'Terjadi kesalahan saat memuat data: ' + error.message);
            });
    }

    // Update filter indicator
    function updateFilterIndicator(status, nama, tanggal) {
        const indicator = document.getElementById('filter-indicator');
        const filterText = document.getElementById('filter-text');

        let activeFilters = [];

        if (status !== '') {
            const statusText = status === 'pending' ? 'Pending' :
                status === 'di_gudang' ? 'Di Gudang' :
                status === 'di_spg' ? 'Di SPG' :
                status === 'di_toko' ? 'Di Toko' :
                status === 'di_collecting' ? 'Di Collecting' :
                status === 'terjual' ? 'Terjual' : status;
            activeFilters.push(`Status: ${statusText}`);
        }

        if (nama !== '') {
            activeFilters.push(`Nama: "${nama}"`);
        }

        if (tanggal !== '') {
            activeFilters.push(`Tanggal: ${tanggal}`);
        }

        if (activeFilters.length > 0) {
            filterText.textContent = `Filter aktif: ${activeFilters.join(', ')}`;
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    } // Display data produk
    function displayProdukData(products) {
        const tbody = document.getElementById('produk-tbody');

        if (products.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    Tidak ada data produk ditemukan
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = products.map((product, index) => {
            const statusBadge = getStatusBadge(product.status);
            const rowNumber = ((currentPage - 1) * 10) + index + 1;

            return `
            <tr>
                <td>${rowNumber}</td>
                <td>
                    <strong>${product.nama_barang}</strong>
                    <br>
                    <small class="text-muted" style="font-size:10px;">ID: #${product.id}</small>
                </td>
                <td>
                    <code>${product.kode_barcode}</code>
                </td>
                <td>${statusBadge}</td>
                <td>
                    <small>${formatDateTime(product.updated_at)}</small>
                </td>
                <td>
                    <button class="btn btn-info btn-sm text-white" onclick="showDetailModal('${product.kode_barcode}')">
                        <i class="fas fa-eye"></i> Detail
                    </button>
                </td>
            </tr>
        `;
        }).join('');
    }

    // Get status badge
    function getStatusBadge(status) {
        // Handle composite status like "di_spg - namaspg"
        if (status && status.includes(' - ')) {
            const [mainStatus, name] = status.split(' - ');
            switch (mainStatus) {
                case 'di_spg':
                    return `<span class="badge bg-info" title="SPG: ${name}">Di SPG</span>`;
                case 'di_toko':
                    return `<span class="badge bg-primary" title="Toko: ${name}">Di Toko</span>`;
                case 'di_collecting':
                    return `<span class="badge bg-secondary" title="Collecting: ${name}">Di Collecting</span>`;
                default:
                    return `<span class="badge bg-light text-dark">${status}</span>`;
            }
        }

        // Handle simple status
        switch (status) {
            case 'pending':
            case '':
            case null:
            case undefined:
                return '<span class="badge bg-warning text-dark">Pending</span>';
            case 'di_gudang':
                return '<span class="badge bg-success">Di Gudang</span>';
            case 'di_spg':
                return '<span class="badge bg-info">Di SPG</span>';
            case 'di_toko':
                return '<span class="badge bg-primary">Di Toko</span>';
            case 'di_collecting':
                return '<span class="badge bg-secondary">Di Collecting</span>';
            case 'terjual':
                return '<span class="badge bg-danger">Terjual</span>';

            case 'rusak':
                return '<span class="badge bg-dark">Rusak</span>';
            default:
                return '<span class="badge bg-light text-dark">Unknown</span>';
        }
    }

    // Update pagination
    function updatePagination(pagination) {
        totalPages = pagination.total_pages;
        const paginationEl = document.getElementById('pagination');

        if (totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        let paginationHtml = '';

        // Previous button
        paginationHtml += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadProdukData(${currentPage - 1})">Previous</a>
        </li>
    `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage || i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadProdukData(${i})">${i}</a>
                </li>
            `;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next button
        paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadProdukData(${currentPage + 1})">Next</a>
        </li>
    `;

        paginationEl.innerHTML = paginationHtml;
    }

    // Show detail modal
    function showDetailModal(kodeBarcode) {
        document.getElementById('loading-history').style.display = 'block';

        console.log('Loading detail for barcode:', kodeBarcode);

        // Load detail produk
        fetch('get-produk-detail.php?kode_barcode=' + encodeURIComponent(kodeBarcode))
            .then(response => {
                console.log('Detail response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw detail response:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed detail data:', data);

                    if (data.status === 'success') {
                        populateModalData(data.data);
                        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                        modal.show();
                    } else {
                        showAlert('error', data.message);
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error for detail:', parseError);
                    console.error('Raw text that failed to parse:', text);
                    showAlert('error', 'Response tidak valid dari server');
                }
            })
            .catch(error => {
                console.error('Error loading detail:', error);
                showAlert('error', 'Terjadi kesalahan saat memuat detail produk: ' + error.message);
            });
    } // Populate modal data
    function populateModalData(data) {
        // Info produk
        document.getElementById('modal-nama-barang').textContent = data.produk.nama_barang;
        document.getElementById('modal-kode-barcode').textContent = data.produk.kode_barcode;

        // Handle status with detail
        const statusBadge = getStatusBadge(data.produk.status);
        document.getElementById('modal-status').innerHTML = statusBadge;

        // Show additional status detail if it contains a name
        const statusDetail = document.getElementById('modal-status-detail');
        if (data.produk.status && data.produk.status.includes(' - ')) {
            const [mainStatus, name] = data.produk.status.split(' - ');
            let detailText = '';
            switch (mainStatus) {
                case 'di_spg':
                    detailText = `SPG: ${name}`;
                    break;
                case 'di_toko':
                    detailText = `Toko: ${name}`;
                    break;
                case 'di_collecting':
                    detailText = `Collecting oleh: ${name}`;
                    break;
            }
            if (detailText) {
                statusDetail.textContent = detailText;
                statusDetail.style.display = 'block';
            } else {
                statusDetail.style.display = 'none';
            }
        } else {
            statusDetail.style.display = 'none';
        }

        document.getElementById('modal-created-at').textContent = formatDateTime(data.produk.created_at);
        document.getElementById('modal-updated-at').textContent = formatDateTime(data.produk.updated_at);

        // Statistik
        document.getElementById('statistik-produk').innerHTML = `
        <div class="row text-center">
            <div class="col-12">
                <div class="bg-light p-3 rounded">
                    <h5 class="text-primary mb-1">${data.statistik.total_aktivitas}</h5>
                    <small class="text-muted">Total Aktivitas</small>
                </div>
            </div>
        </div>
    `;

        // History
        document.getElementById('loading-history').style.display = 'none';
        const historyTbody = document.getElementById('history-tbody');

        if (data.history.length === 0) {
            historyTbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    <i class="fas fa-history"></i> Belum ada history aktivitas
                </td>
            </tr>
        `;
        } else {
            historyTbody.innerHTML = data.history.map(log => `
            <tr>
                <td><small>${formatDateTime(log.waktu)}</small></td>
                <td><span class="badge bg-secondary">${log.username}</span></td>
                <td>${log.aksi}</td>
            </tr>
        `).join('');
        }
    }

    // Utility functions
    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('id-ID');
    }

    function refreshTable() {
        loadProdukData(currentPage);
        loadStatistik();
    }

    function clearFilters() {
        console.log('Clearing filters...');
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-nama').value = '';
        document.getElementById('filter-tanggal').value = '';
        document.getElementById('filter-indicator').style.display = 'none';
        console.log('Filters cleared, reloading data...');
        loadProdukData(1);
    }

    function exportData() {
        const filterStatus = document.getElementById('filter-status').value;
        const filterNama = document.getElementById('filter-nama').value;
        const filterTanggal = document.getElementById('filter-tanggal').value;

        const params = new URLSearchParams({
            export: '1',
            status: filterStatus,
            nama: filterNama,
            tanggal: filterTanggal
        });

        window.open('get-produk-data.php?' + params, '_blank');
    }

    function showAlert(type, message) {
        // Simple alert for now - could be improved with toast notifications
        if (type === 'error') {
            alert('Error: ' + message);
        } else {
            alert(message);
        }
    }
</script>

<?php include 'footer.php' ?>