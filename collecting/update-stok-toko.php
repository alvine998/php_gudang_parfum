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
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard-collecting.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kunjungan-toko.php">Kunjungan Toko</a></li>
                    <li class="breadcrumb-item"><a href="lihat-stok-toko.php?id_toko=<?= $id_toko ?>">Stok Toko</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Update Stok</li>
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
                                <i class="fas fa-barcode"></i> Update Stok - Scan Barang yang Masih Ada
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-warning p-2">
                                <i class="fas fa-sync"></i> Mode Update
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Scanner Section -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-qrcode"></i> Scanner Barang</h5>
                </div>
                <div class="card-body">
                    <!-- QR Reader -->

                    <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                    <!-- Scanner Status -->
                    <div id="scanner-status" class="alert alert-info mt-3">
                        <span class="text-info">📷 Tekan tombol untuk memulai scanner</span>
                    </div>

                    <div class="mt-2">
                        <input type="text" class="form-control" id="qr-result" autofocus placeholder="Kode QR akan muncul di sini">
                    </div>

                    <!-- Manual Input -->
                    <!-- <div class="mt-3">
                        <label for="manual-input" class="form-label fw-bold">
                            <i class="fas fa-keyboard"></i> Input Manual (Alternatif)
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="manual-input"
                                placeholder="Masukkan kode barcode barang" autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" onclick="processManualInput()">
                                <i class="fas fa-search"></i> Cek
                            </button>
                        </div>
                        <small class="text-muted">Gunakan jika scanner bermasalah atau untuk input cepat</small>
                    </div> -->

                    <!-- Scanner Controls -->
                    <div class="mt-3 d-grid gap-2">
                        <button type="button" class="btn btn-success" id="start-scan" onclick="startScanner()">
                            <i class="fas fa-play"></i> Mulai Scanner
                        </button>
                        <button type="button" class="btn btn-danger" id="stop-scan" onclick="stopScanner()" disabled>
                            <i class="fas fa-stop"></i> Hentikan Scanner
                        </button>
                        <button type="button" class="btn btn-warning btn-lg" onclick="selesaiUpdate()">
                            <i class="fas fa-check"></i> Selesai Update
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Status Update</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="card bg-success text-white">
                                <div class="card-body p-2">
                                    <h6 class="mb-1">Barang Terscan</h6>
                                    <h4 id="count-scanned">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body p-2">
                                    <h6 class="mb-1">Total Stok</h6>
                                    <h4 id="count-total">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body p-2">
                                    <h6 class="mb-1">Akan Terjual</h6>
                                    <h4 id="count-terjual">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List Barang yang Discan -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6><i class="fas fa-check-circle text-success"></i> Barang yang Sudah Discan:</h6>
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                onclick="clearAllScanned()"
                                title="Hapus semua scan"
                                style="display: none;" id="clear-all-btn">
                                <i class="fas fa-trash-alt"></i> Clear All
                            </button>
                        </div>
                        <div id="scanned-list" class="border rounded p-2" style="height: 200px; overflow-y: auto;">
                            <p class="text-muted text-center">Belum ada barang yang discan</p>
                        </div>
                    </div>

                    <!-- Informasi Barang -->
                    <div id="barang-info" style="display: none;" class="mt-3">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-info-circle"></i> Barang Berhasil Discan:</h6>
                            <p class="mb-1"><strong>Nama:</strong> <span id="nama-barang">-</span></p>
                            <p class="mb-0"><strong>Barcode:</strong> <span id="kode-barcode" class="font-monospace">-</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="lihat-stok-toko.php?id_toko=<?= $id_toko ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Stok Toko
            </a>
        </div>
    </div>
</div>

<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let html5QrcodeScanner;
    let isScanning = false;
    let scannedItems = [];
    let lastScannedBarcode = null;
    let lastScannedTime = 0;
    let totalStok = 0;
    let idToko = <?= $id_toko ?>;

    // Load data saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        loadTotalStok();
        setTimeout(() => {
            startScanner();
        }, 1000);
        document.getElementById('qr-result').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processBarcode(this.value.trim());
            }
        });
    });

    // Load total stok
    function loadTotalStok() {
        fetch(`get-stok-toko.php?id_toko=${idToko}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Gunakan total barang aktual berdasarkan barcode yang ada di toko
                    totalStok = data.total_barang_aktual || 0;
                    updateCounters();

                    console.log('Data stok loaded:', {
                        'total_stok_toko': data.total_jumlah_stok_toko,
                        'total_barang_aktual': data.total_barang_aktual,
                        'nama_toko': data.nama_toko
                    });
                }
            })
            .catch(error => {
                console.error('Error loading total stok:', error);
                updateScannerStatus('Gagal memuat data stok', 'danger');
            });
    }

    // Callback saat scan berhasil
    function onScanSuccess(decodedText, decodedResult) {
        // tampilkan hasil scan di input
        document.getElementById('qr-result').value = decodedText;
        updateScannerStatus('QR Code berhasil dibaca!', 'success');
        processBarcode(decodedText);
    }

    // Callback saat scan gagal
    function onScanFailure(error) {
        // Tidak perlu tampilkan error untuk setiap frame
    }

    // Mulai scanner
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
            updateScannerStatus('Scanner aktif - Scan barcode barang yang masih ada', 'primary');
            document.getElementById('start-scan').disabled = true;
            document.getElementById('stop-scan').disabled = false;
        }).catch(err => {
            console.error("Error starting scanner:", err);
            updateScannerStatus('Error: ' + err, 'danger');
        });
    }

    // Hentikan scanner
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

    // Proses barcode
    function processBarcode(barcode) {
        const now = Date.now();
        // Jika barcode sama dengan yang terakhir dan kurang dari 1 detik, abaikan (anti double scan)
        if (barcode === lastScannedBarcode && (now - lastScannedTime) < 1000) {
            updateScannerStatus(`Barcode ${barcode} terdeteksi ganda, abaikan.`, 'warning');
            return;
        }
        lastScannedBarcode = barcode;
        lastScannedTime = now;
        if (!barcode || barcode.trim() === '') {
            updateScannerStatus('Barcode kosong atau tidak valid', 'warning');
            return;
        }

        // Normalisasi barcode (trim whitespace)
        barcode = barcode.trim();

        // Validasi duplikasi di frontend
        if (scannedItems.some(item => item.kode_barcode === barcode)) {
            updateScannerStatus(`Barcode ${barcode} sudah pernah discan`, 'warning');
            return;
        }

        updateScannerStatus('Memeriksa validitas barcode...', 'info');

        // Siapkan data untuk validasi
        const scannedBarcodes = scannedItems.map(item => item.kode_barcode);

        // AJAX request untuk cek barcode dengan validasi duplikasi
        const formData = new FormData();
        formData.append('kode_barcode', barcode);
        formData.append('id_toko', idToko);
        formData.append('scanned_barcodes', JSON.stringify(scannedBarcodes));

        fetch('cek-barcode-update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Tambah ke list barang yang discan
                    scannedItems.push(data.data);
                    updateScannerStatus(`✅ ${data.data.nama_barang} berhasil discan!`, 'success');
                    showBarangInfo(data.data);
                    updateScannedList();
                    updateCounters();
                } else {
                    updateScannerStatus(`❌ ${data.message}`, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateScannerStatus('❌ Terjadi kesalahan koneksi', 'danger');
            });
    }

    // Tampilkan info barang yang baru discan
    function showBarangInfo(barang) {
        document.getElementById('nama-barang').textContent = barang.nama_barang;
        document.getElementById('kode-barcode').textContent = barang.kode_barcode;
        document.getElementById('barang-info').style.display = 'block';

        // Hide setelah 3 detik
        setTimeout(() => {
            document.getElementById('barang-info').style.display = 'none';
        }, 3000);
    }

    // Update list barang yang discan
    function updateScannedList() {
        const listDiv = document.getElementById('scanned-list');
        const clearBtn = document.getElementById('clear-all-btn');

        if (scannedItems.length === 0) {
            listDiv.innerHTML = '<p class="text-muted text-center">Belum ada barang yang discan</p>';
            clearBtn.style.display = 'none';
            return;
        }

        clearBtn.style.display = 'block';

        let html = '';
        scannedItems.forEach((item, index) => {
            html += `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <small class="fw-bold">${index + 1}. ${item.nama_barang}</small><br>
                        <small class="text-muted font-monospace">${item.kode_barcode}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="removeScannedItem(${index})" 
                            title="Hapus dari scan">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });

        listDiv.innerHTML = html;
    }

    // Hapus item dari daftar scan
    function removeScannedItem(index) {
        if (index >= 0 && index < scannedItems.length) {
            const removedItem = scannedItems[index];
            scannedItems.splice(index, 1);
            updateScannedList();
            updateCounters();
            updateScannerStatus(`Barcode ${removedItem.kode_barcode} dihapus dari scan`, 'warning');
        }
    }

    // Clear semua scan
    function clearAllScanned() {
        if (scannedItems.length > 0 && confirm('Hapus semua barcode yang sudah discan?')) {
            scannedItems = [];
            updateScannedList();
            updateCounters();
            updateScannerStatus('Semua scan dibersihkan', 'info');
        }
    }

    // Update counters
    function updateCounters() {
        document.getElementById('count-scanned').textContent = scannedItems.length;
        document.getElementById('count-total').textContent = totalStok;
        document.getElementById('count-terjual').textContent = totalStok - scannedItems.length;
    }

    // Input manual
    function processManualInput() {
        const input = document.getElementById('manual-input');
        const barcode = input.value.trim();

        if (barcode) {
            processBarcode(barcode);
            input.value = '';
            input.focus(); // Focus kembali untuk input berikutnya
        } else {
            updateScannerStatus('⚠️ Masukkan kode barcode terlebih dahulu', 'warning');
            input.focus();
        }
    }

    // Selesai update
    function selesaiUpdate() {
        // if (scannedItems.length === 0) {
        //     alert('Belum ada barang yang discan!');
        //     return;
        // }

        if (confirm(`Apakah Anda yakin? Barang yang TIDAK discan (${totalStok - scannedItems.length} barang) akan ditandai sebagai TERJUAL.`)) {
            // Kirim data ke server
            const scannedBarcodes = scannedItems.map(item => item.kode_barcode);

            fetch('proses-update-stok.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_toko: idToko,
                        scanned_barcodes: scannedBarcodes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Update stok berhasil!');
                        window.location.href = `lihat-stok-toko.php?id_toko=${idToko}&updated=1`;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat update stok');
                });
        }
    }

    // Event listener untuk Enter key
    document.getElementById('manual-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
</script>

<?php include 'footer.php'; ?>