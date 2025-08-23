<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">📦 Scan QR Code Barang Masuk</h4>
                </div>
                <div class="card-body">
                    <!-- QR Scanner Container -->
                    <div class="qr-scanner-container mb-3">
                        <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                    </div>

                    <!-- Status dan Hasil -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="scanner-status" class="form-label">Status Scanner:</label>
                                <div id="scanner-status" class="alert alert-info">
                                    Memulai kamera...
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="scan-result" class="form-label">Hasil Scan:</label>
                                <input type="text" class="form-control" id="scan-result" autofocus placeholder="Kode QR akan muncul di sini">
                            </div>
                        </div>
                    </div>

                    <!-- Hasil Pengecekan Database -->
                    <div id="barcode-info" class="card mt-3" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">📋 Informasi Produk</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nama Barang:</strong>
                                    <p id="nama-barang" class="mb-2">-</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Kode Barcode:</strong>
                                    <p id="kode-barcode" class="mb-2">-</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p id="status-barang" class="mb-2">
                                        <span id="status-badge" class="badge">-</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Diupdate:</strong>
                                    <p id="updated-at" class="mb-2">-</p>
                                </div>
                            </div>
                            <!-- <div class="mt-3" id="action-buttons">
                                <button type="button" class="btn btn-primary" id="proses-btn" onclick="prosesBarangMasuk()">
                                    📦 Proses Barang Masuk
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    🔄 Reset
                                </button>
                            </div> -->
                        </div>
                    </div>

                    <!-- Show Array Masukan Items -->
                    <div class="mt-3">
                        <h5>Daftar Barang Masuk:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm mt-2 mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center">Kode Barcode</th>
                                        <th class="text-center">Nama Barang</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="masukan-items-table">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data</td>
                                    </tr>
                                </tbody>
                                <tfoot class="d-none" id="masukan-items-footer">
                                    <tr>
                                        <td colspan="3" class="text-end">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeAllMasukanItems()">
                                                🗑️ Hapus Semua Item
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="processAllMasukanItems()">
                                                📦 Proses Semua Item
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>


                    <!-- Tombol Kontrol -->
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-success mb-2" id="start-scan" onclick="startScanner()">
                            📷 Mulai Scanner
                        </button>
                        <button type="button" class="btn btn-danger mb-2" id="stop-scan" onclick="stopScanner()">
                            ⏹️ Hentikan Scanner
                        </button>
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
    let masukanItems = [];

    let lastScanTime = 0; // track last scan timestamp


    function onScanSuccess(decodedText) {
        const scanInput = document.getElementById('scan-result');
        const statusBox = document.getElementById('scanner-status');

        if (scanInput) scanInput.value = decodedText;
        if (statusBox) {
            statusBox.innerHTML = `<span class="text-success">✅ QR Code berhasil dibaca!</span>`;
            statusBox.className = 'alert alert-success';
        }

        const now = Date.now();

        // if less than 2 seconds since last scan, ignore
        if (now - lastScanTime < 2000) {
            return;
        }
        lastScanTime = now;

        // Tambahkan ke daftar (tidak langsung proses DB)
        processBarcode(decodedText);
    }

    function onScanFailure(error) {
        // bisa diabaikan
    }

    function startScanner() {
        if (isScanning) return;

        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
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
            setStatus('📷 Scanner aktif - Arahkan kamera ke QR code', 'primary');
            toggleScanButtons(true);
        }).catch(err => {
            setStatus(`❌ Error: ${err}`, 'danger');
        });
    }

    function stopScanner() {
        if (!isScanning) return;
        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            setStatus('⏹️ Scanner dihentikan', 'secondary');
            toggleScanButtons(false);
        }).catch(err => console.error("Error stopping scanner:", err));
    }

    function toggleScanButtons(active) {
        document.getElementById('start-scan').disabled = active;
        document.getElementById('stop-scan').disabled = !active;
    }

    function renderMasukanTable() {
        const tbody = document.getElementById("masukan-items-table");
        tbody.innerHTML = "";

        if (masukanItems.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>`;
            document.getElementById("masukan-items-footer").classList.add("d-none");
            return;
        }
        document.getElementById("masukan-items-footer").classList.remove("d-none");

        masukanItems.forEach((item, index) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="text-center">${item.kode_barcode}</td>
                <td class="text-center">${item.nama_barang ?? "-"}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" onclick="removeMasukanItem(${index})">Hapus</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function processBarcode(code = null) {
        const scanInput = document.getElementById('scan-result');
        const manualInput = document.getElementById('manual-input');

        const barcodeValue = code ||
            (scanInput?.value ?? '') ||
            (manualInput?.value ?? '');

        if (!barcodeValue) {
            alert("Kode barcode kosong!");
            return;
        }

        showLoading('Memeriksa barcode di database...');

        fetch('cek-barcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'kode_barcode=' + encodeURIComponent(barcodeValue)
            })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.status === 'success') {
                    if (masukanItems.some(i => i.kode_barcode === data.data.kode_barcode)) {
                        alert("⚠️ Barcode sudah ada di daftar!");
                        return;
                    }
                    if (data.data.status === 'di_gudang') {
                        alert("❌ Barang sudah berada di gudang!");
                        return;
                    }

                    masukanItems.push({
                        kode_barcode: data.data.kode_barcode,
                        nama_barang: data.data.nama_barang,
                        qty: 1
                    });
                    renderMasukanTable();
                    showAlert('success', '✅ Barcode ditemukan & ditambahkan ke daftar');

                } else {
                    showAlert('error', '❌ ' + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                console.error(err);
                showAlert('error', '❌ Error saat memeriksa barcode');
            })
            .finally(() => {
                // kosongkan input selalu
                if (scanInput) scanInput.value = '';
                if (manualInput) manualInput.value = '';
            });
    }


    function removeMasukanItem(index) {
        if (confirm("Apakah Anda yakin ingin menghapus item ini?")) {
            masukanItems.splice(index, 1);
            renderMasukanTable();
        }
    }

    function removeAllMasukanItems() {
        if (confirm("Apakah Anda yakin ingin menghapus semua item?")) {
            masukanItems = [];
            renderMasukanTable();
        }
    }

    function processAllMasukanItems() {
        if (masukanItems.length === 0) {
            showAlert('error', '❌ Tidak ada item untuk diproses!');
            return;
        }
        showLoading('Memproses semua barang masuk...');

        if(!confirm(`Apakah Anda yakin ingin memproses ${masukanItems.length} item?`)) {
            hideLoading();
            return;
        }

        fetch('proses-bulk-barang-masuk.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    items: masukanItems
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', '✅ ' + data.message);
                    masukanItems = []; // kosongkan daftar setelah sukses
                    renderMasukanTable();
                } else {
                    showAlert('error', '❌ ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('error', '❌ Error saat proses bulk');
            });
    }

    // Helper UI
    function setStatus(msg, type) {
        const box = document.getElementById('scanner-status');
        box.innerHTML = `<span>${msg}</span>`;
        box.className = `alert alert-${type}`;
    }

    function showLoading(msg) {
        setStatus(`🔄 ${msg}`, 'info');
    }

    function hideLoading() {
        /* dibiarkan kosong */
    }

    function showAlert(type, msg) {
        setStatus(msg, type === 'success' ? 'success' : 'danger');
    }

    // Reset form
    function resetForm() {
        document.getElementById('scan-result').value = '';
        if (document.getElementById('manual-input')) {
            document.getElementById('manual-input').value = '';
        }
        masukanItems = [];
        renderMasukanTable();
        setStatus('🔄 Form direset', 'secondary');
    }

    // Auto start scanner
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(startScanner, 1000);

        // enter listener
        ['manual-input', 'scan-result'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (el.value.trim()) {
                            processBarcode(el.value.trim());
                        }
                    }
                });
            }
        });
    });

    window.addEventListener('beforeunload', function() {
        if (isScanning) stopScanner();
    });
</script>


<?php include 'footer.php'; ?>