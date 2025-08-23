<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">📤 Scan QR Code Barang Keluar</h4>
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
                                    <strong>Stok Gudang:</strong>
                                    <p id="stok-gudang" class="mb-2">
                                        <span id="stok-badge" class="badge bg-info">-</span>
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <strong>Diupdate:</strong>
                                    <p id="updated-at" class="mb-2">-</p>
                                </div>
                            </div>
                            <div class="mt-3" id="action-buttons">
                                <!-- Dropdown pilihan jenis penjualan -->
                                <div class="mb-3">
                                    <label for="jenis-penjualan" class="form-label fw-bold">Pilih Jenis Penjualan:</label>
                                    <select class="form-select" id="jenis-penjualan">
                                        <option value="">-- Pilih Jenis Penjualan --</option>
                                        <option value="perorangan">👤 Terjual Perorangan</option>
                                        <option value="shopee">🛒 Terjual di Shopee</option>
                                        <option value="rusak">🗑️ Barang Rusak</option>
                                    </select>
                                </div>

                                <!-- <button type="button" class="btn btn-danger" id="proses-btn" onclick="prosesBarangKeluar()" disabled>
                                    📤 Proses Barang Keluar
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    🔄 Reset
                                </button> -->
                            </div>
                        </div>
                    </div>

                    <!-- Show Array Masukan Items -->
                    <div class="mt-3">
                        <h5>Daftar Barang Keluar:</h5>
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

                    <!-- Form Input Manual (backup) -->
                    <!-- <div class="card mt-3" id="manual-input-card">
                        <div class="card-body">
                            <h6 class="card-title">📝 Input Manual (Jika Scanner Tidak Berfungsi)</h6>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="manual-input" placeholder="Masukkan kode barcode secara manual">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary" onclick="processBarcode()">Cek Barcode</button>
                                </div>
                            </div>
                        </div>
                    </div> -->

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

    function onScanSuccess(decodedText, decodedResult) {
        // Tampilkan hasil scan
        document.getElementById('scan-result').value = decodedText;
        document.getElementById('scanner-status').innerHTML = `
        <span class="text-success">✅ QR Code berhasil dibaca!</span>
    `;
        document.getElementById('scanner-status').className = 'alert alert-success';

        const now = Date.now();

        // if less than 2 seconds since last scan, ignore
        if (now - lastScanTime < 2000) {
            return;
        }
        lastScanTime = now;

        // Proses barcode
        processBarcode(decodedText);

        // Optional: Hentikan scanner setelah berhasil scan
        // stopScanner();
    }

    function onScanFailure(error) {
        // Handle scan failure - bisa diabaikan untuk error normal
        // console.warn(`QR scan error: ${error}`);
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
            }, // Gunakan kamera belakang jika tersedia
            config,
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
            document.getElementById('scanner-status').innerHTML = `
            <span class="text-primary">📷 Scanner aktif - Arahkan kamera ke QR code</span>
        `;
            document.getElementById('scanner-status').className = 'alert alert-primary';
            document.getElementById('start-scan').disabled = true;
            document.getElementById('stop-scan').disabled = false;
        }).catch(err => {
            document.getElementById('scanner-status').innerHTML = `
            <span class="text-danger">❌ Error: ${err}</span>
        `;
            document.getElementById('scanner-status').className = 'alert alert-danger';
        });
    }

    function stopScanner() {
        if (!isScanning) return;

        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            document.getElementById('scanner-status').innerHTML = `
            <span class="text-secondary">⏹️ Scanner dihentikan</span>
        `;
            document.getElementById('scanner-status').className = 'alert alert-secondary';
            document.getElementById('start-scan').disabled = false;
            document.getElementById('stop-scan').disabled = true;
        }).catch(err => {
            console.error("Error stopping scanner:", err);
        });
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

        if (!confirm(`Apakah Anda yakin ingin memproses ${masukanItems.length} item?`)) {
            hideLoading();
            return;
        }

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

    function processBarcode(code = null) {
        const barcodeValue = code || document.getElementById('manual-input').value;

        if (!barcodeValue) {
            alert('Silakan scan QR code atau masukkan kode secara manual');
            return;
        }

        // Clear manual input after processing
        if (document.getElementById('manual-input')) {
            document.getElementById('manual-input').value = '';
        }

        // Update scan result display
        document.getElementById('scan-result').value = barcodeValue;

        // Show loading
        showLoading('Memeriksa barcode di database...');

        // Cek barcode di database menggunakan AJAX
        fetch('cek-barcode-keluar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'kode_barcode=' + encodeURIComponent(barcodeValue)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }

                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);

                    hideLoading();

                    if (data.status === 'success') {
                        // Barcode ditemukan - tampilkan info produk
                        // showBarcodeInfo(data.data);
                        if (masukanItems.some(i => i.kode_barcode === data.data.kode_barcode)) {
                            alert("⚠️ Barcode sudah ada di daftar!");
                            return;
                        }
                        if (data.data.status === 'di_gudang') {
                            masukanItems.push({
                                kode_barcode: data.data.kode_barcode,
                                nama_barang: data.data.nama_barang,
                                qty: 1
                            });
                            renderMasukanTable();
                            showAlert('success', '✅ Barcode ditemukan di database!');
                        } else {
                            showAlert('error', '❌ Barang belum di gudang atau sudah terjual!');
                        }


                    } else {
                        // Barcode tidak ditemukan
                        hideBarcodeInfo();
                        showAlert('error', '❌ ' + data.message);
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Raw text that failed to parse:', text);
                    hideLoading();
                    showAlert('error', '❌ Response tidak valid dari server');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', '❌ Terjadi kesalahan saat memeriksa barcode: ' + error.message);
            });
    }

    function showBarcodeInfo(data) {
        document.getElementById('nama-barang').textContent = data.nama_barang;
        document.getElementById('kode-barcode').textContent = data.kode_barcode;
        document.getElementById('updated-at').textContent = new Date(data.updated_at).toLocaleString('id-ID');

        // Update status dengan badge
        const statusBadge = document.getElementById('status-badge');
        const status = data.status || 'pending';

        statusBadge.textContent = status;

        // Set warna badge berdasarkan status
        statusBadge.className = 'badge ';
        if (status === 'terjual') {
            statusBadge.className += 'bg-danger text-white';
        } else if (status === 'di_gudang') {
            statusBadge.className += 'bg-success';
        } else if (status === 'pending') {
            statusBadge.className += 'bg-warning text-dark';
        } else {
            statusBadge.className += 'bg-secondary';
        }

        // Update stok gudang
        const stokBadge = document.getElementById('stok-badge');
        const stokGudang = data.stok_gudang || 0;
        stokBadge.textContent = stokGudang + ' unit';

        // Sembunyikan tombol proses jika status sudah terjual atau stok habis
        const prosesBtn = document.getElementById('proses-btn');
        if (status === 'terjual') {
            prosesBtn.style.display = 'none';

            // Tampilkan pesan bahwa barang sudah terjual
            document.getElementById('scanner-status').innerHTML = `
                <span class="text-danger">⚠️ Barang sudah terjual</span>
            `;
            document.getElementById('scanner-status').className = 'alert alert-danger';
        } else if (status !== 'di_gudang') {
            prosesBtn.style.display = 'none';

            // Tampilkan pesan bahwa barang belum di gudang
            document.getElementById('scanner-status').innerHTML = `
                <span class="text-warning">⚠️ Barang belum berada di gudang</span>
            `;
            document.getElementById('scanner-status').className = 'alert alert-warning';
        } else if (stokGudang <= 0) {
            prosesBtn.style.display = 'none';

            // Tampilkan pesan stok habis
            document.getElementById('scanner-status').innerHTML = `
                <span class="text-warning">⚠️ Stok gudang habis</span>
            `;
            document.getElementById('scanner-status').className = 'alert alert-warning';
        } else {
            prosesBtn.style.display = 'inline-block';
            prosesBtn.disabled = true; // Disabled sampai jenis penjualan dipilih
        }

        document.getElementById('barcode-info').style.display = 'block';
    }

    function hideBarcodeInfo() {
        document.getElementById('barcode-info').style.display = 'none';
    }

    function showLoading(message) {
        document.getElementById('scanner-status').innerHTML = `
            <span class="text-info">🔄 ${message}</span>
        `;
        document.getElementById('scanner-status').className = 'alert alert-info';
    }

    function hideLoading() {
        // Status akan diupdate oleh fungsi lain
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        document.getElementById('scanner-status').innerHTML = `
            <span>${message}</span>
        `;
        document.getElementById('scanner-status').className = `alert ${alertClass}`;
    }

    function prosesBarangKeluar() {
        const kodeBarcode = document.getElementById('scan-result').value;
        const jenisPenjualan = document.getElementById('jenis-penjualan').value;

        if (!kodeBarcode) {
            alert('Tidak ada barcode yang dipilih');
            return;
        }

        if (!jenisPenjualan) {
            alert('Silakan pilih jenis penjualan terlebih dahulu');
            return;
        }

        const jenisPenjualanText = jenisPenjualan === 'perorangan' ? 'Perorangan' : 'Shopee';

        if (confirm(`Apakah Anda yakin ingin memproses barang keluar untuk:\nKode: ${kodeBarcode}\nJenis: Terjual ${jenisPenjualanText}?`)) {
            // Show loading
            showLoading('Memproses barang keluar...');

            // Disable tombol proses
            document.getElementById('proses-btn').disabled = true;

            // Proses barang keluar menggunakan AJAX
            fetch('proses-barang-keluar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'kode_barcode=' + encodeURIComponent(kodeBarcode) + '&jenis_penjualan=' + encodeURIComponent(jenisPenjualan)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers.get('content-type'));

                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }

                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);

                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);

                        if (data.status === 'success') {
                            // Berhasil diproses
                            showAlert('success', '✅ ' + data.message);

                            // Update status di tampilan dengan jenis penjualan
                            const statusBadge = document.getElementById('status-badge');
                            statusBadge.textContent = data.data.status_display || 'terjual';
                            statusBadge.className = 'badge bg-danger text-white';

                            // Update stok gudang
                            const stokBadge = document.getElementById('stok-badge');
                            const newStok = (parseInt(stokBadge.textContent) || 1) - 1;
                            stokBadge.textContent = newStok + ' unit';

                            // Sembunyikan tombol proses
                            document.getElementById('proses-btn').style.display = 'none';

                            // Reset disabled state untuk proses selanjutnya
                            document.getElementById('proses-btn').disabled = false;

                        } else {
                            // Gagal diproses
                            showAlert('error', '❌ ' + data.message);
                            document.getElementById('proses-btn').disabled = false;
                        }
                    } catch (parseError) {
                        console.error('JSON Parse Error:', parseError);
                        console.error('Raw text that failed to parse:', text);
                        showAlert('error', '❌ Response tidak valid dari server');
                        document.getElementById('proses-btn').disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    showAlert('error', '❌ Terjadi kesalahan saat memproses barang keluar: ' + error.message);
                    document.getElementById('proses-btn').disabled = false;
                });
        }
    }

    function resetForm() {
        document.getElementById('scan-result').value = '';
        if (document.getElementById('manual-input')) {
            document.getElementById('manual-input').value = '';
        }

        // Reset dropdown jenis penjualan
        document.getElementById('jenis-penjualan').value = '';

        hideBarcodeInfo();

        // Reset tombol proses ke keadaan normal
        const prosesBtn = document.getElementById('proses-btn');
        prosesBtn.disabled = true; // Disabled karena belum ada pilihan
        prosesBtn.style.display = 'inline-block';

        document.getElementById('scanner-status').innerHTML = `
            <span class="text-secondary mt-2">🔄 Form direset</span>
        `;
        document.getElementById('scanner-status').className = 'alert alert-secondary';
    }

    // Auto start scanner saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Delay sedikit untuk memastikan elemen sudah siap
        setTimeout(() => {
            startScanner();
        }, 1000);

        // Event listener untuk dropdown jenis penjualan
        document.getElementById('jenis-penjualan').addEventListener('change', function() {
            const prosesBtn = document.getElementById('proses-btn');
            const kodeBarcode = document.getElementById('scan-result').value;

            // Enable tombol proses jika ada barcode dan jenis penjualan dipilih
            if (kodeBarcode && this.value) {
                prosesBtn.disabled = false;
            } else {
                prosesBtn.disabled = true;
            }
        });

        // 👉 Event listener untuk input manual (scan-result) pakai Enter
        const scanResult = document.getElementById('scan-result');
        if (scanResult) {
            scanResult.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processBarcode(this.value.trim());
                }
            });
        }

        // Event listener untuk input manual - Enter key (jika ada)
        const manualInput = document.getElementById('manual-input');
        if (manualInput) {
            manualInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processBarcode();
                }
            });
        }
    });

    // Cleanup saat halaman ditutup
    window.addEventListener('beforeunload', function() {
        if (isScanning) {
            stopScanner();
        }
    });
</script>

<?php include 'footer.php'; ?>