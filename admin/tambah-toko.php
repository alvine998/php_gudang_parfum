<?php include 'header.php'; ?>

<h2 style="text-align:center;">Tambah Toko Baru</h2>

<div style="max-width: 600px; margin: 0 auto;">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div style="background-color: #ffdddd; border-left: 6px solid #f44336; padding: 10px; margin-bottom: 15px;">
            ⚠️ Nama toko sudah terdaftar. Silakan gunakan nama lain.
        </div>
    <?php endif; ?>

    <form action="proses-tambah-toko.php" method="POST">
        <label>Nama Toko:</label><br>
        <input type="text" name="nama_toko" required style="width: 100%; padding: 8px;"><br><br>

        <label>Alamat Manual (opsional):</label><br>
        <input type="text" id="alamat_manual" name="alamat_manual" placeholder="Contoh: Jl. Raya No. 10, Tigaraksa" style="width: 100%; padding: 8px;"><br><br>

        <label>Lokasi Otomatis (Google Maps):</label><br>
        <input type="text" id="lokasi_maps" name="lokasi_maps" readonly style="width: 100%; padding: 8px;"><br>
        <button type="button" onclick="getLocation()" style="margin-top: 10px;">📍 Ambil Lokasi Sekarang</button><br><br>

        <button type="submit" style="background-color: green; color: white; padding: 10px 20px;">Simpan</button>
    </form>
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
    var latitude = position.coords.latitude;
    var longitude = position.coords.longitude;
    var link = "https://maps.google.com/?q=" + latitude + "," + longitude;
    document.getElementById("lokasi_maps").value = link;
}

function showError(error) {
    alert("Gagal mendapatkan lokasi: " + error.message);
}
</script>
