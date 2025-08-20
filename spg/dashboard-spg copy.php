<?php
session_start();
if ($_SESSION['role'] != 'spg') {
  header("Location: ../index.php");
  exit;
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SPG Panel</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f3f3f3;
      margin: 0;
      padding: 20px;
      color: #333;
    }

    h2 {
      text-align: center;
      margin-top: 10px;
      margin-bottom: 20px;
    }

    .container {
      background-color: white;
      max-width: 600px;
      margin: auto;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    label {
      font-weight: bold;
    }

    input[type="text"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    button {
      padding: 10px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    .btn-lokasi {
      background-color: #007bff;
      color: white;
      width: 100%;
      margin-top: 5px;
      margin-bottom: 20px;
    }

    .btn-submit {
      background-color: green;
      color: white;
      width: 100%;
    }

    .alert {
      background-color: #ffdddd;
      border-left: 6px solid #f44336;
      padding: 10px;
      margin-bottom: 20px;
    }

    @media (max-width: 480px) {
      body {
        padding: 10px;
      }

      h2 {
        font-size: 20px;
      }

      button {
        font-size: 15px;
      }
    }
  </style>
</head>

<body>

  <h2>Hi <?= htmlspecialchars($username) ?></h2>
  <h2>Tambah Toko Baru</h2>

  <div class="container">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
      <div class="alert">⚠️ Nama toko sudah terdaftar. Silakan gunakan nama lain.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'lokasi_kosong'): ?>
      <div class="alert">⚠️ Lokasi belum diambil! Silakan klik "Ambil Lokasi Sekarang".</div>
    <?php endif; ?>

    <form action="proses-tambah-toko.php" method="POST" onsubmit="return validateLocation();">
      <label>Nama Toko:</label>
      <input type="text" name="nama_toko" required />

      <label>Alamat Manual (opsional):</label>
      <input type="text" id="alamat_manual" name="alamat_manual" placeholder="Contoh: Jl. Raya No. 10, Tigaraksa" />

      <label>Lokasi Otomatis (Google Maps):</label>
      <input type="text" id="lokasi_maps" name="lokasi_maps" readonly />

      <button type="button" class="btn-lokasi" onclick="getLocation()">📍 Ambil Lokasi Sekarang</button>
      <button type="submit" class="btn-submit">Simpan</button>
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
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      const link = "https://maps.google.com/?q=" + lat + "," + lon;
      document.getElementById("lokasi_maps").value = link;
    }

    function showError(error) {
      alert("Gagal mendapatkan lokasi: " + error.message);
    }

    function validateLocation() {
      const lokasi = document.getElementById("lokasi_maps").value.trim();
      if (lokasi === "") {
        alert("Silakan klik 'Ambil Lokasi Sekarang' sebelum menyimpan!");
        return false;
      }
      return true;
    }
  </script>

</body>

</html>