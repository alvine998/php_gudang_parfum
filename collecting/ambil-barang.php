<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-qrcode"></i> Scan Barang - Ambil dari Gudang</h4>
                </div>
                <div class="card-body">
                    <!-- Scanner Section -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-camera"></i> QR Code Scanner</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>

                                    <div class="mt-3">
                                        <div id="scanner-status" class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Memulai scanner...
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 mt-3">
                                        <button type="button" class="btn btn-success" id="start-scan" onclick="startScanner()">
                                            <i class="fas fa-play"></i> Mulai Scanner
                                        </button>
                                        <button type="button" class="btn btn-danger" id="stop-scan" onclick="stopScanner()" disabled>
                                            <i class="fas fa-stop"></i> Hentikan Scanner
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 px-3">
                                <input type="text" class="form-control" id="qr-result" autofocus placeholder="Kode QR akan muncul di sini">
                            </div>

                            <!-- Input Manual -->
                            <!-- <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-keyboard"></i> Input Manual</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="manual-input" class="form-label">Kode Barcode</label>
                                        <input type="text" class="form-control" id="manual-input"
                                            placeholder="Masukkan kode barcode manual">
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="processBarcode()">
                                        <i class="fas fa-search"></i> Cek Barcode
                                    </button>
                                </div>
                            </div> -->
                        </div>

                        <div class="col-lg-6">
                            <!-- Hasil Scan -->
                            <!-- <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info"></i> Informasi Barang</h6>
                                </div>
                                <div class="card-body">
                                    <div id="scan-result" class="mb-3">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-qrcode fa-3x mb-3"></i>
                                            <p>Scan atau masukkan kode barcode untuk melihat informasi barang</p>
                                        </div>
                                    </div>

                                    <div id="barcode-info" style="display: none;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Nama Barang:</label>
                                                    <p id="nama-barang" class="form-control-plaintext">-</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Kode Barcode:</label>
                                                    <p id="kode-barcode" class="form-control-plaintext font-monospace">-</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Status:</label>
                                                    <p id="status-barang">
                                                        <span id="status-badge" class="badge">-</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Lokasi:</label>
                                                    <p id="lokasi-barang" class="form-control-plaintext">-</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2" id="action-buttons">
                                            <button type="button" class="btn btn-success btn-lg" id="ambil-btn" onclick="ambilBarang()">
                                                <i class="fas fa-hand-paper"></i> Ambil Barang
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                            <!-- Bulk Items Table -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-list"></i> Daftar Barang Masukan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped" id="bulk-items-table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Barang</th>
                                                    <th>Kode Barcode</th>
                                                    <th>Status</th>
                                                    <th>Lokasi</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="text-center">
                                                    <td colspan="6">Belum ada barang yang dimasukkan</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-none gap-2 mt-3" id="bulk-action-buttons">
                                        <button type="button" class="btn btn-danger btn-lg" onclick="removeAllItems()">
                                            <i class="fas fa-trash"></i> Hapus Semua Barang
                                        </button>
                                        <button type="button" class="btn btn-success btn-lg" id="ambil-bulk-btn" onclick="ambilBulkBarang()">
                                            <i class="fas fa-hand-paper"></i> Ambil Semua Barang
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Riwayat Pengambilan -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-history"></i> Riwayat Pengambilan Hari Ini</h6>
                                </div>
                                <div class="card-body">
                                    <div id="riwayat-pengambilan">
                                        <div class="text-center text-muted">
                                            <p>Belum ada pengambilan hari ini</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let html5QrcodeScanner;
    let isScanning = false;
    let currentBarcode = null;
    let masukanItems = [];
    let lastScanTime = 0;

    // Fungsi untuk memulai scanner
    function startScanner() {
        if (isScanning) return;

        const config = {
            fps: 10,
            qrbox: {
                width: 300,
                height: 300
            },
            aspectRatio: 1.0,
            rememberLastUsedCamera: true
        };

        html5QrcodeScanner = new Html5Qrcode("qr-reader");

        html5QrcodeScanner.start({
                facingMode: "environment"
            },
            config,
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
            updateScannerStatus('Scanner aktif - Arahkan kamera ke QR code', 'success');
            document.getElementById('start-scan').disabled = true;
            document.getElementById('stop-scan').disabled = false;
        }).catch(err => {
            updateScannerStatus('Error: ' + err, 'danger');
            console.error("Scanner error:", err);
        });
    }

    // Fungsi untuk menghentikan scanner
    function stopScanner() {
        if (!isScanning) return;

        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            updateScannerStatus('Scanner dihentikan', 'secondary');
            document.getElementById('start-scan').disabled = false;
            document.getElementById('stop-scan').disabled = true;
        }).catch(err => {
            console.error("Error stopping scanner:", err);
        });
    }

    // Callback saat scan berhasil
    function onScanSuccess(decodedText, decodedResult) {
        document.getElementById('qr-result').value = decodedText;
        const now = Date.now();
        if (now - lastScanTime < 3000) return; // cegah double scan cepat
        lastScanTime = now;
        processBarcode(decodedText);
        updateScannerStatus('QR Code berhasil dibaca!', 'success');
    }

    // Callback saat scan gagal (bisa diabaikan)
    function onScanFailure(error) {
        // Tidak perlu menampilkan error untuk setiap frame
    }

    // Update status scanner
    function updateScannerStatus(message, type) {
        const statusDiv = document.getElementById('scanner-status');
        statusDiv.className = `alert alert-${type}`;
        statusDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    }

    // Proses barcode
    function processBarcode(code = null) {
        const barcodeValue = code || document.getElementById('qr-result').value;
        if (!barcodeValue) {
            showAlert('Silakan scan QR code untuk melihat informasi barang', 'warning');
            return;
        }

        // Cek duplicate
        if (masukanItems.find(item => item.kode_barcode === barcodeValue)) {
            showAlert('Barcode sudah ditambahkan ke daftar', 'warning');
            return;
        }



        currentBarcode = barcodeValue;
        showLoading('Memeriksa barcode di database...');

        fetch('cek-barcode-ambil.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'kode_barcode=' + encodeURIComponent(barcodeValue)
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (data.status === 'success') {
                    masukanItems.push(data.data);
                    renderBulkTable();
                    document.getElementById('bulk-action-buttons').className = 'd-grid gap-2 mt-3';
                    showAlert('Barang ditambahkan ke daftar!', 'success');
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(err => {
                hideLoading();
                console.error(err);
                showAlert('Terjadi kesalahan saat memeriksa barcode', 'danger');
            });
    }

    function renderBulkTable() {
        const tbody = document.querySelector('#bulk-items-table tbody');
        if (masukanItems.length === 0) {
            tbody.innerHTML = '<tr class="text-center"><td colspan="6">Belum ada barang yang dimasukkan</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        masukanItems.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.nama_barang}</td>
            <td><code>${item.kode_barcode}</code></td>
            <td><span class="badge ${item.status === 'di_gudang' ? 'bg-success' : 'bg-secondary'}">${item.status}</span></td>
            <td>${item.lokasi || 'Gudang Utama'}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                    Hapus
                </button>
            </td>
        `;
            tbody.appendChild(tr);
        });
    }

    function removeItem(index) {
        masukanItems.splice(index, 1);
        if (masukanItems.length === 0) {
            document.getElementById('bulk-action-buttons').className = 'd-none gap-2 mt-3';
        }
        renderBulkTable();
    }

    function removeAllItems() {
        if (confirm('Apakah Anda yakin ingin menghapus semua barang dari daftar?')) {
            masukanItems = [];
            document.getElementById('bulk-action-buttons').className = 'd-none gap-2 mt-3';
            renderBulkTable();
        }
    }


    function ambilBulkBarang() {
        if (masukanItems.length === 0) {
            showAlert('Tidak ada barang untuk diambil', 'warning');
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin mengambil ${masukanItems.length} barang ini dari gudang?`)) return;

        const barcodes = masukanItems.map(item => item.kode_barcode);
        const btn = document.getElementById('ambil-bulk-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

        fetch('proses-bulk-ambil-barang.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'kode_barcode=' + encodeURIComponent(JSON.stringify(barcodes))
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-hand-paper"></i> Ambil Semua Barang';

                if (data.status === 'success') {
                    showAlert('Semua barang berhasil diambil!', 'success');
                    document.getElementById('qr-result').value = '';
                    masukanItems = [];
                    renderBulkTable();
                    loadRiwayatPengambilan();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('Terjadi kesalahan saat mengambil barang', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-hand-paper"></i> Ambil Semua Barang';
            });
    }


    // Tampilkan informasi barcode
    function showBarcodeInfo(data) {
        document.getElementById('nama-barang').textContent = data.nama_barang;
        document.getElementById('kode-barcode').textContent = data.kode_barcode;
        document.getElementById('lokasi-barang').textContent = data.lokasi || 'Gudang Utama';

        // Update status badge
        const statusBadge = document.getElementById('status-badge');
        statusBadge.textContent = data.status;

        // Set warna badge berdasarkan status
        statusBadge.className = 'badge ';
        if (data.status === 'di_gudang') {
            statusBadge.className += 'bg-success';
        } else if (data.status.includes('di_collecting')) {
            statusBadge.className += 'bg-primary';
        } else {
            statusBadge.className += 'bg-secondary';
        }

        // Tampilkan tombol ambil hanya jika status di_gudang
        const ambilBtn = document.getElementById('ambil-btn');
        if (data.status === 'di_gudang') {
            ambilBtn.style.display = 'block';
            ambilBtn.disabled = false;
        } else {
            ambilBtn.style.display = 'none';
            if (data.status.includes('di_collecting')) {
                showAlert('Barang sudah diambil oleh Collecting: ' + data.status.replace('di_collecting - ', ''), 'warning');
            } else {
                showAlert('Barang tidak dapat diambil. Status: ' + data.status, 'warning');
            }
        }

        document.getElementById('scan-result').style.display = 'none';
        document.getElementById('barcode-info').style.display = 'block';
    }

    // Sembunyikan informasi barcode
    function hideBarcodeInfo() {
        document.getElementById('scan-result').style.display = 'block';
        document.getElementById('barcode-info').style.display = 'none';
    }

    // Ambil barang
    function ambilBarang() {
        if (!currentBarcode) {
            showAlert('Tidak ada barcode yang dipilih', 'warning');
            return;
        }

        if (confirm('Apakah Anda yakin ingin mengambil barang ini dari gudang?')) {
            const ambilBtn = document.getElementById('ambil-btn');
            ambilBtn.disabled = true;
            ambilBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            fetch('proses-ambil-barang.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'kode_barcode=' + encodeURIComponent(currentBarcode)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('Barang berhasil diambil!', 'success');

                        // Update status badge
                        const statusBadge = document.getElementById('status-badge');
                        statusBadge.textContent = data.data.status_baru;
                        statusBadge.className = 'badge bg-primary';

                        // Sembunyikan tombol ambil
                        document.getElementById('ambil-btn').style.display = 'none';

                        // Refresh riwayat
                        loadRiwayatPengambilan();

                        // Reset form setelah 2 detik
                        setTimeout(() => {
                            resetForm();
                        }, 2000);
                    } else {
                        showAlert(data.message, 'danger');
                        ambilBtn.disabled = false;
                        ambilBtn.innerHTML = '<i class="fas fa-hand-paper"></i> Ambil Barang';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Terjadi kesalahan saat mengambil barang', 'danger');
                    ambilBtn.disabled = false;
                    ambilBtn.innerHTML = '<i class="fas fa-hand-paper"></i> Ambil Barang';
                });
        }
    }

    // Reset form
    function resetForm() {
        currentBarcode = null;
        hideBarcodeInfo();

        const ambilBtn = document.getElementById('ambil-btn');
        ambilBtn.disabled = false;
        ambilBtn.style.display = 'block';
        ambilBtn.innerHTML = '<i class="fas fa-hand-paper"></i> Ambil Barang';

        updateScannerStatus('Siap untuk scan barcode berikutnya', 'info');
    }

    // Fungsi utility
    function showLoading(message) {
        updateScannerStatus(message, 'info');
    }

    function hideLoading() {
        updateScannerStatus('Scanner siap', 'info');
    }

    function showAlert(message, type) {
        // Buat toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1050';
        toast.style.minWidth = '300px';

        toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        document.body.appendChild(toast);

        // Auto remove setelah 5 detik
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    // Load riwayat pengambilan
    function loadRiwayatPengambilan() {
        fetch('get-riwayat-pengambilan.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('riwayat-pengambilan');

                if (data.status === 'success' && data.data.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                    html += '<thead><tr><th>Waktu</th><th>Nama Barang</th><th>Kode Barcode</th><th>Status</th></tr></thead><tbody>';

                    data.data.forEach(item => {
                        html += `
                        <tr>
                            <td>${item.waktu}</td>
                            <td>${item.nama_barang}</td>
                            <td><code>${item.kode_barcode}</code></td>
                            <td><span class="badge bg-primary">${item.status}</span></td>
                        </tr>
                    `;
                    });

                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-center text-muted"><p>Belum ada pengambilan hari ini</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading riwayat:', error);
            });
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Auto start scanner setelah 1 detik
        setTimeout(startScanner, 1000);
        document.getElementById('qr-result').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processBarcode(this.value.trim());
            }
        });
        // Load riwayat pengambilan
        loadRiwayatPengambilan();
    });

    // Cleanup saat halaman ditutup
    window.addEventListener('beforeunload', function() {
        if (isScanning) {
            stopScanner();
        }
    });
</script>

<style>
    .card {
        border-radius: 15px;
        border: none;
    }

    .card-header {
        border-radius: 15px 15px 0 0 !important;
    }

    #qr-reader {
        border-radius: 10px;
        overflow: hidden;
    }

    .form-control-plaintext {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 12px;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }

    .badge {
        font-size: 0.9em;
    }

    .btn {
        border-radius: 10px;
    }

    .alert {
        border-radius: 10px;
    }
</style>

<?php include 'footer.php'; ?>