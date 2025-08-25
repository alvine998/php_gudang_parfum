<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">📤 Scan QR Code Barang Keluar</h4>
                </div>
                <div class="card-body">
                    <!-- QR Scanner -->
                    <div id="qr-reader" style="width: 100%; max-width: 500px; margin: auto;"></div>

                    <!-- Status dan Hasil -->
                    <div class="mt-3">
                        <label class="form-label">Status Scanner:</label>
                        <div id="scanner-status" class="alert alert-info">Memulai kamera...</div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Hasil Scan:</label>
                        <input type="text" class="form-control" id="scan-result" autofocus placeholder="Kode QR akan muncul di sini">
                    </div>

                    <!-- Daftar Barang Keluar -->
                    <div class="mt-4">
                        <h5>Daftar Barang Keluar:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center">Kode Barcode</th>
                                        <th class="text-center">Nama Barang</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="masukan-items-table">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                    </tr>
                                </tbody>
                                <tfoot class="d-none" id="masukan-items-footer">
                                    <tr>
                                        <td colspan="3" class="text-end">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeAllMasukanItems()">
                                                🗑️ Hapus Semua
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="processAllMasukanItems()">
                                                📦 Proses Semua
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Kontrol Scanner -->
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-success mb-2" id="start-scan" onclick="startScanner()">📷 Mulai Scanner</button>
                        <button type="button" class="btn btn-danger mb-2" id="stop-scan" onclick="stopScanner()" disabled>⏹️ Hentikan Scanner</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let html5QrcodeScanner;
    let isScanning = false;
    let masukanItems = [];
    let lastScanTime = 0;

    // Success Scan
    function onScanSuccess(decodedText, decodedResult) {
        console.log("Scan success:", decodedText, decodedResult); // debug

        document.getElementById('scan-result').value = decodedText;
        document.getElementById('scanner-status').innerHTML = `<span class="text-success">✅ QR Code berhasil dibaca!</span>`;
        document.getElementById('scanner-status').className = 'alert alert-success';

        const now = Date.now();
        if (now - lastScanTime < 2000) return; // debounce biar tidak double
        lastScanTime = now;

        processBarcode(decodedText.trim());
    }

    // Start & Stop
    function startScanner() {
        if (isScanning) return;

        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.0
        };
        html5QrcodeScanner = new Html5Qrcode("qr-reader");

        html5QrcodeScanner
            .start({
                facingMode: "environment"
            }, config, onScanSuccess, (err) => {
                console.warn("Scan error:", err);
            })
            .then(() => {
                isScanning = true;
                document.getElementById('scanner-status').innerHTML = `<span class="text-primary">📷 Scanner aktif</span>`;
                document.getElementById('scanner-status').className = 'alert alert-primary';
                document.getElementById('start-scan').disabled = true;
                document.getElementById('stop-scan').disabled = false;
            })
            .catch(err => {
                console.error("Start scanner error:", err);
                document.getElementById('scanner-status').innerHTML = `<span class="text-danger">❌ ${err}</span>`;
                document.getElementById('scanner-status').className = 'alert alert-danger';
            });
    }

    function stopScanner() {
        if (!isScanning) return;
        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            document.getElementById('scanner-status').innerHTML = `<span class="text-secondary">⏹️ Scanner dihentikan</span>`;
            document.getElementById('scanner-status').className = 'alert alert-secondary';
            document.getElementById('start-scan').disabled = false;
            document.getElementById('stop-scan').disabled = true;
        }).catch(err => console.error("Stop scanner error:", err));
    }

    // Tabel daftar barang keluar
    function renderMasukanTable() {
        const tbody = document.getElementById("masukan-items-table");
        tbody.innerHTML = "";

        if (masukanItems.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>`;
            document.getElementById("masukan-items-footer").classList.add("d-none");
            return;
        }

        document.getElementById("masukan-items-footer").classList.remove("d-none");

        masukanItems.forEach((item, index) => {
            tbody.innerHTML += `
            <tr>
                <td class="text-center">${item.kode_barcode}</td>
                <td class="text-center">${item.nama_barang ?? "-"}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" onclick="removeMasukanItem(${index})">Hapus</button>
                </td>
            </tr>`;
        });
    }

    function removeMasukanItem(index) {
        if (confirm("Hapus item ini?")) {
            masukanItems.splice(index, 1);
            renderMasukanTable();
        }
    }

    function removeAllMasukanItems() {
        if (confirm("Hapus semua item?")) {
            masukanItems = [];
            renderMasukanTable();
        }
    }

    // Proses semua barang keluar
    function processAllMasukanItems() {
        if (masukanItems.length === 0) {
            showAlert('error', '❌ Tidak ada item untuk diproses!');
            return;
        }

        if (!confirm(`Proses ${masukanItems.length} item?`)) return;

        fetch('proses-bulk-barang-keluar.php', {
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
                    masukanItems = [];
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

    // Proses per-barcode
    function processBarcode(code) {
        if (!code) return;

        console.log("Processing barcode:", code); // debug
        showLoading('Memeriksa barcode...');

        fetch('cek-barcode-keluar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'kode_barcode=' + encodeURIComponent(code)
            })
            .then(r => r.json())
            .then(data => {
                console.log("Response cek-barcode:", data); // debug
                if (data.status === 'success') {
                    if (masukanItems.some(i => i.kode_barcode === data.data.kode_barcode)) {
                        showAlert('error', '⚠️ Barcode sudah ada!');
                        return;
                    }
                    if (data.data.status === 'di_gudang') {
                        masukanItems.push({
                            kode_barcode: data.data.kode_barcode,
                            nama_barang: data.data.nama_barang
                        });
                        renderMasukanTable();
                        showAlert('success', '✅ Barang ditemukan & ditambahkan!');
                    } else {
                        showAlert('error', '❌ Barang tidak di gudang!');
                    }
                } else {
                    showAlert('error', '❌ ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('error', '❌ Error koneksi');
            });
    }

    function showLoading(msg) {
        document.getElementById('scanner-status').innerHTML = `<span class="text-info">🔄 ${msg}</span>`;
        document.getElementById('scanner-status').className = 'alert alert-info';
    }

    function showAlert(type, msg) {
        document.getElementById('scanner-status').innerHTML = msg;
        document.getElementById('scanner-status').className = `alert ${type === 'success' ? 'alert-success' : 'alert-danger'}`;
    }

    // Auto start
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => startScanner(), 1000);
        document.getElementById('scan-result').addEventListener('keypress', e => {
            if (e.key === 'Enter') processBarcode(e.target.value.trim());
        });
    });
</script>


<?php include 'footer.php'; ?>