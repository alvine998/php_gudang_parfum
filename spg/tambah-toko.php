<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fas fa-store"></i> Tambah Toko Baru</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Nama toko sudah terdaftar. Silakan gunakan nama lain.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'lokasi_kosong'): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-map-marker-alt"></i> Lokasi belum diambil! Silakan klik "Ambil Lokasi Sekarang".
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="proses-tambah-toko.php" method="POST" onsubmit="return validateLocation();">
                        <div class="mb-3">
                            <label for="nama_toko" class="form-label fw-bold">
                                <i class="fas fa-store"></i> Nama Toko <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama_toko" name="nama_toko" required
                                placeholder="Masukkan nama toko">
                        </div>

                        <div class="mb-3">
                            <label for="alamat_manual" class="form-label fw-bold">
                                <i class="fas fa-home"></i> Alamat Manual (opsional)
                            </label>
                            <input type="text" class="form-control" id="alamat_manual" name="alamat_manual"
                                placeholder="Contoh: Jl. Raya No. 10, Tigaraksa">
                        </div>

                        <div class="mb-4">
                            <label for="lokasi_maps" class="form-label fw-bold">
                                <i class="fas fa-map-marker-alt"></i> Lokasi Otomatis (Google Maps)
                            </label>
                            <input type="text" class="form-control" id="lokasi_maps" name="lokasi_maps" readonly
                                placeholder="Klik tombol di bawah untuk mengambil lokasi">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg text-white" onclick="getLocation()">
                                <i class="fas fa-crosshairs"></i> 📍 Ambil Lokasi Sekarang
                            </button>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Simpan Toko
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition, showError, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        } else {
            alert("Geolocation tidak didukung oleh browser ini.");
        }
    }

    function showPosition(position) {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        const link = "https://maps.google.com/?q=" + lat + "," + lon;
        document.getElementById("lokasi_maps").value = link;

        // Tampilkan notifikasi sukses
        const lokasi_input = document.getElementById("lokasi_maps");
        lokasi_input.classList.add("is-valid");

        // Tampilkan toast sukses
        showToast("Lokasi berhasil diambil!", "success");
    }

    function showError(error) {
        alert("Gagal mendapatkan lokasi: " + error.message);
        showToast("Gagal mendapatkan lokasi: " + error.message, "error");
    }

    function validateLocation() {
        const lokasi = document.getElementById("lokasi_maps").value.trim();
        if (lokasi === "") {
            alert("Silakan klik 'Ambil Lokasi Sekarang' sebelum menyimpan!");
            document.getElementById("lokasi_maps").classList.add("is-invalid");
            return false;
        }
        return true;
    }

    function showToast(message, type) {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1050';
        toast.innerHTML = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
</script>

<style>
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        border-radius: 15px 15px 0 0 !important;
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-label {
        color: #495057;
        margin-bottom: 8px;
    }

    .btn {
        border-radius: 10px;
        padding: 12px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .alert {
        border-radius: 10px;
        border: none;
    }
</style>

<?php include 'footer.php'; ?>