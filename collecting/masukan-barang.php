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
                    <li class="breadcrumb-item active" aria-current="page">Masukan Barang</li>
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
                                <i class="fas fa-arrow-down"></i> Masukan Barang - Scan Barang dari Collecting ke Toko
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-success p-2">
                                <i class="fas fa-download"></i> Mode Masukan
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
                <div class="card-header bg-success text-white">
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
                        <small class="text-muted">Scan barang yang ada di collecting untuk dimasukkan ke toko</small>
                    </div> -->

                    <!-- Scanner Controls -->
                    <div class="mt-3 d-grid gap-2">
                        <button type="button" class="btn btn-success" id="start-scan" onclick="startScanner()">
                            <i class="fas fa-play"></i> Mulai Scanner
                        </button>
                        <button type="button" class="btn btn-danger" id="stop-scan" onclick="stopScanner()" disabled>
                            <i class="fas fa-stop"></i> Hentikan Scanner
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="selesaiMasukan()">
                            <i class="fas fa-check"></i> Selesai Masukan Barang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Status Pemasukan</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="card bg-success text-white">
                                <div class="card-body p-2">
                                    <h6 class="mb-1">Barang Dimasukan</h6>
                                    <h4 id="count-masukan">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-warning text-dark">
                                <div class="card-body p-2">
                                    <h6 class="mb-1">Di Collecting</h6>
                                    <h4 id="count-collecting">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List Barang yang Dimasukan -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6><i class="fas fa-arrow-down text-success"></i> Barang yang Akan Dimasukan:</h6>
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                onclick="clearAllMasukan()"
                                title="Hapus semua"
                                style="display: none;" id="clear-all-btn">
                                <i class="fas fa-trash-alt"></i> Clear All
                            </button>
                        </div>
                        <div id="masukan-list" class="border rounded p-2" style="height: 200px; overflow-y: auto;">
                            <p class="text-muted text-center">Belum ada barang yang dipilih untuk dimasukan</p>
                        </div>
                    </div>

                    <!-- Informasi Barang -->
                    <div id="barang-info" style="display: none;" class="mt-3">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-info-circle"></i> Barang Berhasil Dipilih untuk Dimasukan:</h6>
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
            <a href="lihat-stok-toko.php?id_toko=<?= $id_toko ?>&updated=1" class="btn btn-outline-secondary">
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
    let masukanItems = [];
    let totalCollecting = 0;
    let idToko = <?= $id_toko ?>;
    let lastScannedBarcode = null;
    let lastScannedTime = 0;
    let isProcessingBarcode = false;

    // Load data saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        loadTotalCollecting();
        setTimeout(() => {
            startScanner();
        }, 1000);
        document.getElementById('qr-result').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processBarcode(this.value.trim());
            }
        });
    });

    // Load total barang di collecting
    function loadTotalCollecting() {
        fetch(`get-collecting-data.php`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    totalCollecting = data.total_collecting || 0;
                    updateCounters();
                }
            })
            .catch(error => {
                console.error('Error loading collecting data:', error);
                updateScannerStatus('Gagal memuat data collecting', 'danger');
            });
    }

    // Callback saat scan berhasil
    function onScanSuccess(decodedText, decodedResult) {
        document.getElementById('qr-result').value = decodedText;
        console.log(`QR Code detected: ${decodedText}`);
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
            updateScannerStatus('Scanner aktif - Scan barang dari collecting', 'primary');
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
        if (isProcessingBarcode) {
            updateScannerStatus('Sedang memproses barcode sebelumnya, tunggu sebentar...', 'warning');
            return;
        }
        isProcessingBarcode = true;
        const now = Date.now();
        if (barcode === lastScannedBarcode && (now - lastScannedTime) < 1000) {
            updateScannerStatus(`Barcode ${barcode} terdeteksi ganda, abaikan.`, 'warning');
            isProcessingBarcode = false;
            return;
        }
        lastScannedBarcode = barcode;
        lastScannedTime = now;
        if (!barcode || barcode.trim() === '') {
            updateScannerStatus('Barcode kosong atau tidak valid', 'warning');
            isProcessingBarcode = false;
            return;
        }
        barcode = barcode.trim();
        if (masukanItems.some(item => item.kode_barcode === barcode)) {
            updateScannerStatus(`Barcode ${barcode} sudah pernah dipilih`, 'warning');
            isProcessingBarcode = false;
            return;
        }
        updateScannerStatus('Memeriksa validitas barcode...', 'info');
        const formData = new FormData();
        formData.append('kode_barcode', barcode);
        fetch('cek-barcode-masukan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Cek ulang setelah AJAX (race condition)
                    if (masukanItems.some(item => item.kode_barcode === barcode)) {
                        updateScannerStatus(`Barcode ${barcode} sudah pernah dipilih (setelah cek ulang)`, 'warning');
                        isProcessingBarcode = false;
                        return;
                    }
                    fetch('get-stok-toko.php?id_toko=' + idToko)
                        .then(response => response.json())
                        .then(data2 => {
                            if (data2.status === 'success') {
                                if (data2.data.length > 0) {
                                    for (let item of data2.data) {
                                        if (item.nama_barang === data.data.nama_barang) {
                                            if (!confirm(`Barang ${item.nama_barang} sudah ada di stok toko. Apakah Anda yakin ingin memasukkan barang ini?`)) {
                                                isProcessingBarcode = false;
                                                updateScannerStatus('Pembatalan masukan barang', 'warning');
                                                masukanItems = masukanItems.filter(i => i.kode_barcode !== data.data.kode_barcode);
                                                updateMasukanList();
                                                updateCounters();
                                                return;
                                            }
                                        }
                                    }
                                }
                                if (masukanItems.length > 1) {
                                    for (let item of masukanItems) {
                                        if (item.nama_barang === data.data.nama_barang) {
                                            if (!confirm(`Barang ${item.nama_barang} sudah ada di daftar. Apakah Anda yakin ingin memasukkan barang ini?`)) {
                                                isProcessingBarcode = false;
                                                updateScannerStatus('Pembatalan masukan barang', 'warning');
                                                masukanItems = masukanItems.filter(i => i.kode_barcode !== data.data.kode_barcode);
                                                updateMasukanList();
                                                updateCounters();
                                                return;
                                            }
                                        }
                                    }
                                }
                                // totalCollecting = data.total_collecting || 0;
                                // document.getElementById('count-collecting').textContent = totalCollecting;
                            } else {
                                console.error('Error loading collecting data:', data2.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading collecting data:', error);
                        });
                    masukanItems.push(data.data);
                    updateScannerStatus(`✅ ${data.data.nama_barang} berhasil dipilih untuk dimasukan!`, 'success');
                    showBarangInfo(data.data);
                    updateMasukanList();
                    updateCounters();
                } else {
                    updateScannerStatus(`❌ ${data.message}`, 'danger');
                }
                isProcessingBarcode = false;
            })
            .catch(() => {
                isProcessingBarcode = false;
            });
    }

    // Tampilkan info barang
    function showBarangInfo(barang) {
        document.getElementById('nama-barang').textContent = barang.nama_barang;
        document.getElementById('kode-barcode').textContent = barang.kode_barcode;
        document.getElementById('barang-info').style.display = 'block';

        setTimeout(() => {
            document.getElementById('barang-info').style.display = 'none';
        }, 3000);
    }

    // Update list barang yang dimasukan
    function updateMasukanList() {
        const listDiv = document.getElementById('masukan-list');
        const clearBtn = document.getElementById('clear-all-btn');

        if (masukanItems.length === 0) {
            listDiv.innerHTML = '<p class="text-muted text-center">Belum ada barang yang dipilih untuk dimasukan</p>';
            clearBtn.style.display = 'none';
            return;
        }

        clearBtn.style.display = 'block';

        let html = '';
        masukanItems.forEach((item, index) => {
            html += `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <small class="fw-bold">${index + 1}. ${item.nama_barang}</small><br>
                        <small class="text-muted font-monospace">${item.kode_barcode}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="removeMasukanItem(${index})" 
                            title="Hapus dari daftar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });

        listDiv.innerHTML = html;
    }

    // Hapus item dari daftar masukan
    function removeMasukanItem(index) {
        if (index >= 0 && index < masukanItems.length) {
            const removedItem = masukanItems[index];
            masukanItems.splice(index, 1);
            updateMasukanList();
            updateCounters();
            updateScannerStatus(`Barcode ${removedItem.kode_barcode} dihapus dari daftar masukan`, 'warning');
        }
    }

    // Clear semua masukan
    function clearAllMasukan() {
        if (masukanItems.length > 0 && confirm('Hapus semua barang dari daftar masukan?')) {
            masukanItems = [];
            updateMasukanList();
            updateCounters();
            updateScannerStatus('Semua daftar masukan dibersihkan', 'info');
        }
    }

    // Update counters
    function updateCounters() {
        document.getElementById('count-masukan').textContent = masukanItems.length;
        document.getElementById('count-collecting').textContent = totalCollecting;
    }

    // Input manual
    function processManualInput() {
        const input = document.getElementById('manual-input');
        const barcode = input.value.trim();

        if (barcode) {
            processBarcode(barcode);
            input.value = '';
            input.focus();
        } else {
            updateScannerStatus('⚠️ Masukkan kode barcode terlebih dahulu', 'warning');
            input.focus();
        }
    }

    // Selesai masukan
    function selesaiMasukan() {
        if (masukanItems.length === 0) {
            alert('Belum ada barang yang dipilih untuk dimasukan!');
            return;
        }

        if (confirm(`Apakah Anda yakin ingin memasukkan ${masukanItems.length} barang ke toko? Status akan berubah menjadi di_toko.`)) {
            const masukanBarcodes = masukanItems.map(item => item.kode_barcode);

            fetch('proses-masukan-barang.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_toko: idToko,
                        masukan_barcodes: masukanBarcodes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Masukan barang berhasil!');
                        window.location.href = `lihat-stok-toko.php?id_toko=${idToko}&updated=1`;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memasukkan barang');
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