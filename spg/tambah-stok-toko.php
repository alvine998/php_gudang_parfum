<?php
include 'header.php';
include '../koneksi.php';

$id_toko = $_GET['id_toko'] ?? 0;
if ($id_toko == 0) {
  echo "<script>alert('ID Toko tidak valid'); window.location.href='kelola-stok-toko.php';</script>";
  exit;
}

$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM toko WHERE id = $id_toko"));
if (!$toko) {
  echo "<script>alert('Toko tidak ditemukan'); window.location.href='kelola-stok-toko.php';</script>";
  exit;
}
?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">
            <i class="fas fa-plus-circle"></i>
            Tambah Stok - <?= htmlspecialchars($toko['nama_toko']); ?>
          </h4>
          <small>Scan barcode untuk menambahkan barang ke toko</small>
        </div>
        <div class="card-body">
          <!-- Alert Messages -->
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle"></i>
              <?= $_SESSION['error'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php unset($_SESSION['error']);
          endif; ?>

          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle"></i>
              <?= $_SESSION['success'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php unset($_SESSION['success']);
          endif; ?>

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
                <div class="mt-2 px-3">
                  <input type="text" class="form-control" id="qr-result" autofocus placeholder="Kode QR akan muncul di sini">
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <!-- Informasi Barang -->
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0"><i class="fas fa-info"></i> Informasi Barang</h6>
                </div>
                <div class="card-body">
                  <div id="scan-result" class="mb-3">
                    <div class="text-center text-muted">
                      <i class="fas fa-qrcode fa-3x mb-3"></i>
                      <p>Scan barcode untuk melihat informasi barang</p>
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
                          <label class="form-label fw-bold">Stok Toko Saat Ini:</label>
                          <p id="stok-toko" class="form-control-plaintext">-</p>
                        </div>
                      </div>
                    </div>

                    <div class="d-grid gap-2" id="action-buttons">
                      <button type="button" class="btn btn-primary btn-lg" id="tambah-btn" onclick="tambahStok()">
                        <i class="fas fa-plus"></i> Tambah ke Toko
                      </button>
                      <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Riwayat Penambahan -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0"><i class="fas fa-history"></i> Riwayat Penambahan Hari Ini</h6>
                </div>
                <div class="card-body">
                  <div id="riwayat-penambahan">
                    <div class="text-center text-muted">
                      <p>Belum ada penambahan hari ini</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Aksi -->
          <div class="row mt-3">
            <div class="col-12 text-center">
              <!-- Button Selesai & Print (akan muncul jika ada riwayat) -->
              <button id="btn-selesai-print" class="btn btn-success btn-lg me-3" onclick="selesaiDanPrint()" style="display: none;">
                <i class="fas fa-print"></i> Selesai & Print Receipt
              </button>

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
  let idToko = <?= $id_toko ?>;

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
    document.getElementById('qr-result').value = decodedText; // Update input text dengan hasil scan
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
  function processBarcode(code) {
    if (!code) {
      showAlert('Kode barcode tidak valid', 'warning');
      return;
    }

    currentBarcode = code;
    showLoading('Memeriksa barcode di database...');

    // Cek barcode di database
    fetch('cek-barcode-stok-toko.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'kode_barcode=' + encodeURIComponent(code) + '&id_toko=' + idToko
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();

        if (data.status === 'success') {
          showBarcodeInfo(data.data);
          showAlert('Barcode ditemukan!', 'success');
          fetch('get-riwayat-stok-toko.php?id_toko=' + idToko)
            .then(response => response.json())
            .then(data2 => {
              if (data2.data.length > 0) {
                for (let item of data2.data) {
                  if (item.nama_barang === data.data.nama_barang) {
                    if (confirm(item.nama_barang + ' Sudah ada dalam riwayat penambahan. Apakah Anda ingin menambahkan stok barang ini ke toko?')) {
                      showBarcodeInfo(data.data);
                    } else {
                      resetForm();
                    }
                  }
                }
              }
            })
        } else {
          hideBarcodeInfo();
          showAlert(data.message, 'danger');
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('Terjadi kesalahan saat memeriksa barcode', 'danger');
      });
  }

  // Tampilkan informasi barcode
  function showBarcodeInfo(data) {
    document.getElementById('nama-barang').textContent = data.nama_barang;
    document.getElementById('kode-barcode').textContent = data.kode_barcode;
    document.getElementById('stok-toko').textContent = data.stok_toko + ' pcs';

    // Update status badge
    const statusBadge = document.getElementById('status-badge');
    statusBadge.textContent = data.status;

    // Set warna badge berdasarkan status
    statusBadge.className = 'badge ';
    if (data.status.includes('di_spg')) {
      statusBadge.className += 'bg-primary';
    } else if (data.status === 'di_gudang') {
      statusBadge.className += 'bg-success';
    } else if (data.status.includes('di_toko')) {
      statusBadge.className += 'bg-info';
    } else {
      statusBadge.className += 'bg-secondary';
    }

    // Tampilkan tombol tambah hanya jika status sesuai kondisi
    const tambahBtn = document.getElementById('tambah-btn');

    // Cek apakah status adalah di_spg dengan username yang sesuai
    const isValidSpgStatus = data.status.includes('di_spg') &&
      data.status.includes('- ' + data.current_username);

    if (isValidSpgStatus) {
      tambahBtn.style.display = 'block';
      tambahBtn.disabled = false;
      tambahBtn.innerHTML = '<i class="fas fa-plus"></i> Tambah ke Toko';
      tambahBtn.className = 'btn btn-primary btn-lg';
    } else if (data.status.includes('di_toko')) {
      tambahBtn.style.display = 'none';
      showAlert('Barang sudah berada di toko. Status: ' + data.status, 'info');
    } else if (data.status.includes('di_spg')) {
      tambahBtn.style.display = 'none';
      showAlert('Barang sedang dengan SPG lain. Status: ' + data.status, 'warning');
    } else if (data.status === 'di_gudang') {
      tambahBtn.style.display = 'none';
      showAlert('Barang masih di gudang. Harus diambil dulu oleh SPG. Status: ' + data.status, 'info');
    } else {
      tambahBtn.style.display = 'none';
      showAlert('Barang tidak dapat ditambahkan. Status: ' + data.status, 'warning');
    }

    document.getElementById('scan-result').style.display = 'none';
    document.getElementById('barcode-info').style.display = 'block';
  }

  // Sembunyikan informasi barcode
  function hideBarcodeInfo() {
    document.getElementById('scan-result').style.display = 'block';
    document.getElementById('barcode-info').style.display = 'none';
  }

  function checkSameVariant(barcode) {
    // Cek apakah barcode sudah ada di riwayat penambahan
    const riwayatRows = document.querySelectorAll('#riwayat-penambahan tr');
    for (let row of riwayatRows) {
      const kodeBarcode = row.querySelector('code').textContent;
      if (kodeBarcode === barcode) {
        return true;
      }
    }
    return false;
  }

  // Tambah stok
  function tambahStok() {
    if (!currentBarcode) {
      showAlert('Tidak ada barcode yang dipilih', 'warning');
      return;
    }

    if (confirm('Apakah Anda yakin ingin menambah 1 pcs barang ke toko?')) {
      const tambahBtn = document.getElementById('tambah-btn');
      tambahBtn.disabled = true;
      tambahBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

      fetch('proses-tambah-stok-toko.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'kode_barcode=' + encodeURIComponent(currentBarcode) +
            '&id_toko=' + idToko +
            '&jumlah=1'
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            showAlert('1 pcs barang berhasil ditambahkan!', 'success');

            // Update stok toko yang ditampilkan
            document.getElementById('stok-toko').textContent = data.data.stok_baru + ' pcs';

            // Refresh riwayat
            loadRiwayatPenambahan();

            // Reset form setelah 1 detik
            setTimeout(() => {
              resetForm();
            }, 1000);
          } else {
            showAlert(data.message, 'danger');
          }

          tambahBtn.disabled = false;
          tambahBtn.innerHTML = '<i class="fas fa-plus"></i> Tambah 1 pcs ke Toko';
        })
        .catch(error => {
          console.error('Error:', error);
          showAlert('Terjadi kesalahan saat menambah stok', 'danger');
          tambahBtn.disabled = false;
          tambahBtn.innerHTML = '<i class="fas fa-plus"></i> Tambah 1 pcs ke Toko';
        });
    }
  }

  // Reset form
  function resetForm() {
    currentBarcode = null;
    hideBarcodeInfo();

    const tambahBtn = document.getElementById('tambah-btn');
    tambahBtn.disabled = false;
    tambahBtn.style.display = 'block';
    tambahBtn.innerHTML = '<i class="fas fa-plus"></i> Tambah 1 pcs ke Toko';

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

  // Load riwayat penambahan
  function loadRiwayatPenambahan() {
    fetch('get-riwayat-stok-toko.php?id_toko=' + idToko)
      .then(response => response.json())
      .then(data => {
        const container = document.getElementById('riwayat-penambahan');
        const btnSelesaiPrint = document.getElementById('btn-selesai-print');

        if (data.status === 'success' && data.data.length > 0) {
          let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
          html += '<thead><tr><th>Waktu</th><th>Nama Barang</th><th>Kode Barcode</th><th>Jumlah</th></tr></thead><tbody>';

          data.data.forEach(item => {
            html += `
                        <tr>
                            <td>${item.waktu}</td>
                            <td>${item.nama_barang}</td>
                            <td><code>${item.kode_barcode}</code></td>
                            <td><span class="badge bg-success">+${item.jumlah}</span></td>
                        </tr>
                    `;
          });

          html += '</tbody></table></div>';
          container.innerHTML = html;

          // Tampilkan button selesai & print jika ada data
          btnSelesaiPrint.style.display = 'inline-block';
        } else {
          container.innerHTML = '<div class="text-center text-muted"><p>Belum ada penambahan hari ini</p></div>';

          // Sembunyikan button selesai & print jika tidak ada data
          btnSelesaiPrint.style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error loading riwayat:', error);
      });
  }

  // Fungsi untuk selesai dan print
  function selesaiDanPrint() {
    if (confirm('Apakah Anda sudah selesai menambah stok dan ingin melakukan print receipt?')) {
      // Disable button untuk mencegah double click
      const btnSelesaiPrint = document.getElementById('btn-selesai-print');
      btnSelesaiPrint.disabled = true;
      btnSelesaiPrint.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

      // Kirim request untuk menyiapkan data print
      fetch('prepare-print-data.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'id_toko=' + idToko
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Redirect ke halaman print
            window.location.href = 'print-tambah-stok.php';
          } else {
            showAlert(data.message || 'Gagal menyiapkan data print', 'danger');
            // Reset button
            btnSelesaiPrint.disabled = false;
            btnSelesaiPrint.innerHTML = '<i class="fas fa-print"></i> Selesai & Print Receipt';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showAlert('Terjadi kesalahan saat menyiapkan print', 'danger');
          // Reset button
          btnSelesaiPrint.disabled = false;
          btnSelesaiPrint.innerHTML = '<i class="fas fa-print"></i> Selesai & Print Receipt';
        });
    }
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
    // Load riwayat penambahan
    loadRiwayatPenambahan();
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

  /* Styling khusus untuk button selesai & print */
  #btn-selesai-print {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    transition: all 0.3s ease;
    animation: pulse-green 2s infinite;
  }

  #btn-selesai-print:hover {
    background: linear-gradient(45deg, #1e7e34, #17a2b8);
    box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
    transform: translateY(-2px);
  }

  #btn-selesai-print:disabled {
    background: #6c757d;
    animation: none;
  }

  @keyframes pulse-green {
    0% {
      box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    50% {
      box-shadow: 0 6px 16px rgba(40, 167, 69, 0.5);
    }

    100% {
      box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }
  }
</style>

<?php include 'footer.php'; ?>