<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-store"></i> Kunjungan Toko - Scan Barcode</h4>
                </div>
                <div class="card-body">
                    <!-- Scanner Section -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-qrcode"></i> QR Code Scanner</h6>
                                </div>
                                <div class="card-body">
                                    <!-- QR Reader -->
                                    <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>

                                    <!-- Scanner Status -->
                                    <div id="scanner-status" class="alert alert-info mt-3">
                                        <span class="text-info">📷 Mempersiapkan scanner...</span>
                                    </div>

                                    <div class="mt-2">
                                        <input type="text" class="form-control" id="qr-result" autofocus placeholder="Kode QR akan muncul di sini">
                                    </div>

                                    <!-- Manual Input -->
                                    <!-- <div class="mt-3" id="manual-input-section" style="display: none;">
                                        <label for="manual-input" class="form-label fw-bold">
                                            <i class="fas fa-keyboard"></i> Input Manual (Alternatif)
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="manual-input"
                                                placeholder="Masukkan kode barcode toko">
                                            <button class="btn btn-outline-primary" type="button" onclick="processManualInput()">
                                                <i class="fas fa-search"></i> Cek
                                            </button>
                                        </div>
                                    </div> -->

                                    <!-- Scanner Controls -->
                                    <div class="mt-3 d-grid gap-2">
                                        <button type="button" class="btn btn-success" id="start-scan" onclick="startScanner()">
                                            <i class="fas fa-play"></i> Mulai Scanner
                                        </button>
                                        <button type="button" class="btn btn-danger" id="stop-scan" onclick="stopScanner()" disabled>
                                            <i class="fas fa-stop"></i> Hentikan Scanner
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Toko -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Toko</h6>
                                </div>
                                <div class="card-body">
                                    <div id="toko-info" style="display: none;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Nama Toko:</label>
                                                    <p id="nama-toko" class="form-control-plaintext fs-5 fw-bold text-primary">-</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Kode Barcode:</label>
                                                    <p id="kode-barcode" class="form-control-plaintext font-monospace">-</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Alamat:</label>
                                                    <p id="alamat-toko" class="form-control-plaintext">-</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Total Stok:</label>
                                                    <p id="total-stok" class="form-control-plaintext">
                                                        <span class="badge bg-info fs-6">- pcs</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Status:</label>
                                                    <p id="status-toko" class="form-control-plaintext">
                                                        <span class="badge bg-success">Aktif</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2" id="action-buttons">
                                            <button type="button" class="btn btn-primary btn-lg" id="lihat-stok-btn" onclick="lihatStokToko()">
                                                <i class="fas fa-boxes"></i> Lihat Stok Toko
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                        </div>
                                    </div>

                                    <div id="no-toko-info" class="text-center text-muted">
                                        <i class="fas fa-store fa-3x mb-3 text-muted"></i>
                                        <p>Scan barcode toko untuk melihat informasi</p>
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
    let currentToko = null;

    // Callback saat scan berhasil
    function onScanSuccess(decodedText, decodedResult) {
        // tampilkan hasil scan di input
        document.getElementById('qr-result').value = decodedText;
        // Update status
        updateScannerStatus('QR Code berhasil dibaca!', 'success');

        // Proses barcode toko
        processTokoBarcode(decodedText);
    }

    // Callback saat scan gagal (bisa diabaikan)
    function onScanFailure(error) {
        // Tidak perlu tampilkan error untuk setiap frame
    }

    // Mulai scanner
    function startScanner() {
        if (isScanning) {
            console.log('Scanner sudah aktif');
            return;
        }

        // Bersihkan scanner sebelumnya jika ada
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.stop().catch(e => console.log('Cleanup error:', e));
            } catch (e) {
                console.log('Scanner cleanup error:', e);
            }
        }

        // Update status loading
        updateScannerStatus('Mengakses kamera...', 'info');

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
            updateScannerStatus('Scanner aktif - Arahkan kamera ke QR code toko', 'success');
            document.getElementById('start-scan').disabled = true;
            document.getElementById('stop-scan').disabled = false;
            console.log('Scanner berhasil dimulai');
        }).catch(err => {
            console.error("Error starting scanner:", err);
            updateScannerStatus('Error: Tidak dapat mengakses kamera. Pastikan browser memiliki izin kamera.', 'danger');

            // Tampilkan tombol manual jika kamera gagal
            showManualInputOption();
        });
    }

    // Hentikan scanner
    function stopScanner() {
        if (!isScanning || !html5QrcodeScanner) return;

        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            html5QrcodeScanner = null; // Clear reference
            updateScannerStatus('Scanner dihentikan', 'secondary');
            document.getElementById('start-scan').disabled = false;
            document.getElementById('stop-scan').disabled = true;
            console.log('Scanner berhasil dihentikan');
        }).catch(err => {
            console.error("Error stopping scanner:", err);
            // Force cleanup
            isScanning = false;
            html5QrcodeScanner = null;
        });
    }

    // Update status scanner
    function updateScannerStatus(message, type) {
        const statusDiv = document.getElementById('scanner-status');
        statusDiv.className = `alert alert-${type} mt-3`;

        let icon = '📷';
        if (type === 'success') icon = '✅';
        else if (type === 'danger') icon = '❌';
        else if (type === 'primary') icon = '📷';

        statusDiv.innerHTML = `<span class="text-${type}">${icon} ${message}</span>`;
    }

    // Proses barcode toko
    function processTokoBarcode(barcode) {
        if (!barcode) {
            updateScannerStatus('Barcode kosong', 'warning');
            return;
        }

        // Show loading
        updateScannerStatus('Mencari toko...', 'info');

        // AJAX request untuk cek barcode toko
        fetch('cek-barcode-toko.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'kode_barcode=' + encodeURIComponent(barcode)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showTokoInfo(data.data);
                    updateScannerStatus('Toko ditemukan!', 'success');
                } else {
                    updateScannerStatus(data.message, 'danger');
                    hideTokoInfo();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateScannerStatus('Terjadi kesalahan saat mencari toko', 'danger');
                hideTokoInfo();
            });
    }

    // Tampilkan informasi toko
    function showTokoInfo(toko) {
        currentToko = toko;

        document.getElementById('nama-toko').textContent = toko.nama_toko;
        document.getElementById('kode-barcode').textContent = toko.kode_barcode;
        document.getElementById('alamat-toko').textContent = toko.alamat || 'Tidak ada alamat';
        document.getElementById('total-stok').innerHTML = `<span class="badge bg-info fs-6">${toko.total_stok || 0} pcs</span>`;

        document.getElementById('toko-info').style.display = 'block';
        document.getElementById('no-toko-info').style.display = 'none';
    }

    // Sembunyikan informasi toko
    function hideTokoInfo() {
        document.getElementById('toko-info').style.display = 'none';
        document.getElementById('no-toko-info').style.display = 'block';
        currentToko = null;
    }

    // Lihat stok toko
    function lihatStokToko() {
        if (!currentToko) {
            alert('Tidak ada toko yang dipilih');
            return;
        }

        // Redirect ke halaman stok toko
        window.location.href = `lihat-stok-toko.php?id_toko=${currentToko.id}`;
    }

    // Tampilkan opsi input manual
    function showManualInputOption() {
        document.getElementById('manual-input-section').style.display = 'block';
        updateScannerStatus('Kamera tidak tersedia. Gunakan input manual di bawah.', 'warning');
    }

    // Input manual
    function processManualInput() {
        const barcode = document.getElementById('manual-input').value.trim();
        if (barcode) {
            processTokoBarcode(barcode);
            // Clear input setelah proses
            document.getElementById('manual-input').value = '';
        } else {
            updateScannerStatus('Masukkan kode barcode terlebih dahulu', 'warning');
        }
    }

    // Reset form
    function resetForm() {
        hideTokoInfo();

        // Clear manual input jika ada
        const manualInput = document.getElementById('manual-input');
        if (manualInput) {
            manualInput.value = '';
        }

        updateScannerStatus('Scanner siap untuk scan berikutnya', 'info');

        // Tidak perlu stop scanner, biarkan tetap aktif untuk scan berikutnya
    }

    // Event listener untuk Enter key pada input manual
    document.addEventListener('DOMContentLoaded', function() {
        // Event listener untuk input manual
        const manualInput = document.getElementById('manual-input');
        if (manualInput) {
            manualInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processManualInput();
                }
            });
        }
    });

    // Auto-start scanner saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Auto start scanner setelah 500ms untuk memberikan waktu DOM selesai render
        setTimeout(() => {
            updateScannerStatus('Memulai scanner otomatis...', 'info');
            startScanner();
        }, 500);
        document.getElementById('qr-result').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processTokoBarcode(this.value.trim());
            }
        });
    });

    // Cleanup saat halaman ditutup
    window.addEventListener('beforeunload', function() {
        if (isScanning && html5QrcodeScanner) {
            try {
                html5QrcodeScanner.stop();
            } catch (e) {
                console.log('Cleanup on beforeunload error:', e);
            }
        }
    });
</script>

<?php include 'footer.php'; ?>