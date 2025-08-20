<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php");
  exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 230px;
      background-color: #111;
      color: white;
      min-height: 100vh;
      padding: 20px;
      box-sizing: border-box;
      overflow-y: auto;
      transition: transform 0.3s ease;
    }

    .sidebar h2 {
      margin-top: 0;
      font-size: 20px;
      margin-bottom: 20px;
    }

    .sidebar a {
      display: block;
      margin: 12px 0;
      color: white;
      text-decoration: none;
      font-size: 14px;
      padding: 10px;
      border-radius: 4px;
    }

    .sidebar a:hover {
      background-color: #333;
      color: #00bfff;
    }

    .sidebar a.active {
      background-color: #00bfff;
      color: black;
      font-weight: bold;
    }

    .toggle-btn {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      background-color: #00bfff;
      color: white;
      padding: 8px 12px;
      font-size: 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      z-index: 1001;
    }

    .content {
      flex-grow: 1;
      padding: 30px;
      overflow-x: auto;
    }

    .table-wrapper {
      overflow-x: auto;
      max-width: 100%;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      min-width: 900px;
    }

    table th,
    table td {
      border: 1px solid #000;
      padding: 8px 12px;
      text-align: left;
      white-space: nowrap;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 230px;
        transform: translateX(-100%);
        background-color: #111;
        z-index: 1000;
        transition: transform 0.3s ease;
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .toggle-btn {
        display: block;
      }

      .content {
        width: 100%;
        padding: 20px;
      }
    }
  </style>
</head>

<body>

  <button class="toggle-btn" onclick="toggleSidebar()">☰ Menu</button>

  <div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard-admin.php" class="<?= $current_page == 'dashboard-admin.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="stok-gudang.php" class="<?= $current_page == 'stok-gudang.php' ? 'active' : '' ?>">📦 Stok Gudang</a>
    <a href="stok-toko.php" class="<?= $current_page == 'stok-toko.php' ? 'active' : '' ?>">🏪 Stok Toko</a>
    <a href="daftar-toko.php" class="<?= $current_page == 'daftar-toko.php' ? 'active' : '' ?>">📋 Daftar Toko</a>
    <a href="daftar-produk.php" class="<?= $current_page == 'daftar-produk.php' ? 'active' : '' ?>">📦 Daftar Produk</a>
    <a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : '' ?>">📊 Laporan</a>
    <a href="manajemen-user.php" class="<?= $current_page == 'manajemen-user.php' ? 'active' : '' ?>">👤 Manajemen User</a>
    <a href="log-aktivitas.php" class="<?= $current_page == 'log-aktivitas.php' ? 'active' : '' ?>">📝 Log Aktivitas</a>
    <a href="cetak-barcode-batch.php" class="<?= $current_page == 'cetak-barcode-batch.php' ? 'active' : '' ?>">Daftar Barcode Produk</a>
    <a href="generate-barcode-produk.php" class="<?= $current_page == 'generate-barcode-produk.php' ? 'active' : '' ?>">Generate Barcode Produk</a>

    <a href="logout-admin.php">📕 Logout</a>
  </div>

  <div class="content">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
      }
    </script>