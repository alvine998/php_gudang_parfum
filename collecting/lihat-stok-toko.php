<?php
include 'header.php';
include '../koneksi.php';

// Validasi parameter id_toko
if (!isset($_GET['id_toko']) || empty($_GET['id_toko'])) {
    echo '<div class="alert alert-danger mt-4">ID Toko tidak valid</div>';
    include 'footer.php';
    exit;
}

$id_toko = intval($_GET['id_toko']);
$updated = isset($_GET['updated']) && $_GET['updated'] == '1';

// Get toko info
$query_toko = "SELECT * FROM toko WHERE id = ?";
$stmt_toko = $conn->prepare($query_toko);
$stmt_toko->bind_param("i", $id_toko);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();

if ($result_toko->num_rows === 0) {
    echo '<div class="alert alert-danger mt-4">Toko tidak ditemukan</div>';
    include 'footer.php';
    exit;
}

$toko = $result_toko->fetch_assoc();
?>

<div class="container mt-4">
    <?php if ($updated): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <strong>Update Stok Berhasil!</strong>
                    Sekarang Anda dapat:
                    <ul class="mb-0 mt-2">
                        <li><strong>Tarik Barang:</strong> Untuk mengambil barang dari toko ke collecting</li>
                        <li><strong>Masukan Barang:</strong> Untuk menyetor barang dari collecting ke toko</li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard-collecting.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kunjungan-toko.php">Kunjungan Toko</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stok Toko</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1"><?= htmlspecialchars($toko['nama_toko']) ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($toko['alamat'] ?? 'Alamat tidak tersedia') ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-primary p-2">
                                <i class="fas fa-barcode"></i> <?= htmlspecialchars($toko['kode_barcode'] ?? $toko['nama_toko']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <h5 class="mb-2 mb-md-0"><i class="fas fa-boxes"></i> Stok Barang di Toko</h5>
                        <div class="w-100 w-md-auto">
                            <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
                                <span class="d-block d-md-none fw-bold mb-1">Aksi:</span>
                                <?php if (!$updated): ?>
                                    <a href="update-stok-toko.php?id_toko=<?= $id_toko ?>" class="btn btn-warning w-100 w-md-auto">
                                        <i class="fas fa-barcode"></i> Update Stok
                                    </a>
                                <?php else: ?>
                                    <a href="update-stok-toko.php?id_toko=<?= $id_toko ?>" class="btn btn-outline-warning w-100 w-md-auto">
                                        <i class="fas fa-barcode"></i> Update Lagi
                                    </a>
                                    <a href="tarik-barang.php?id_toko=<?= $id_toko ?>" class="btn btn-danger border-2 w-100 w-md-auto">
                                        <i class="fas fa-arrow-up"></i> Tarik Barang
                                    </a>
                                    <a href="masukan-barang.php?id_toko=<?= $id_toko ?>" class="btn btn-success w-100 w-md-auto">
                                        <i class="fas fa-arrow-down"></i> Masukan Barang
                                    </a>
                                    <button type="button" class="btn btn-info w-100 w-md-auto text-white" onclick="cetakStruk()">
                                        <i class="fas fa-print"></i> Cetak Struk
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Terakhir Update</th>
                                </tr>
                            </thead>
                            <tbody id="stok-list">
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="no-stok" class="text-center p-5" style="display: none;">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>Belum ada stok di toko ini</h5>
                        <p class="text-muted">Toko belum memiliki stok barang</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="kunjungan-toko.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Scan Toko
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load stok toko
        loadStokToko(<?= $id_toko ?>);
    });

    function loadStokToko(id_toko) {
        fetch(`get-stok-toko.php?id_toko=${id_toko}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.data.length > 0) {
                        renderStokTable(data.data);
                    } else {
                        document.getElementById('stok-list').innerHTML = '';
                        document.getElementById('no-stok').style.display = 'block';
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan saat memuat data stok');
            });
    }

    function renderStokTable(stokList) {
        const tableBody = document.getElementById('stok-list');
        tableBody.innerHTML = '';

        stokList.forEach((item, index) => {
            const row = document.createElement('tr');

            // Format date
            const updateDate = new Date(item.updated_at);
            const formattedDate = updateDate.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.nama_barang}</td>
                <td><span class="badge bg-info">${item.jumlah} pcs</span></td>
                <td>${formattedDate}</td>
            `;

            tableBody.appendChild(row);
        });
    }

    function showError(message) {
        const tableBody = document.getElementById('stok-list');
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </td>
            </tr>
        `;
    }


    // Function untuk cetak struk
    function cetakStruk() {
        if (confirm('Apakah Anda yakin ingin mencetak struk aktivitas toko ini?')) {
            // Disable button sementara
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            // Prepare data untuk print
            fetch('prepare-struk-collecting.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_toko=<?= $id_toko ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Redirect ke halaman print
                        window.open('print-struk-collecting.php', '_blank');
                        showToast('Struk berhasil disiapkan untuk print!', 'success');
                    } else {
                        showToast(data.message || 'Gagal menyiapkan struk', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Terjadi kesalahan saat menyiapkan struk', 'error');
                })
                .finally(() => {
                    // Reset button
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
    }

    // Function untuk menampilkan toast notification
    function showToast(message, type) {
        // Buat toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'info' ? 'info' : 'success'} alert-dismissible fade show position-fixed`;
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1050';
        toast.style.minWidth = '300px';

        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';

        toast.innerHTML = `
            ${icon} ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        // Auto remove setelah 5 detik
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);

        return toast;
    }
</script>

<?php include 'footer.php'; ?>