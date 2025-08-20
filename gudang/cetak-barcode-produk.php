<?php
session_start();

// Cek apakah user sudah login dan memiliki role yang tepat
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'gudang') {
    header('Location: ../index.php');
    exit();
}

include '../koneksi.php';

// Ambil ID toko dari parameter
$id_toko = isset($_GET['id_toko']) ? (int)$_GET['id_toko'] : 0;

if ($id_toko <= 0) {
    echo "<script>alert('ID toko tidak valid'); window.close();</script>";
    exit();
}

// Ambil data toko
$username = $_SESSION['username'];

$query = "SELECT id, nama_toko, lokasi_maps, alamat_manual FROM toko WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_toko);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Toko tidak ditemukan'); window.close();</script>";
    exit();
}

$toko = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code - <?= htmlspecialchars($toko['nama_toko']) ?></title>
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: white;
            color: black;
        }

        /* Ukuran khusus untuk printer thermal 80mm */
        @page {
            size: 80mm auto;
            margin: 2mm;
        }

        .thermal-container {
            width: 76mm;
            min-height: auto;
            padding: 3mm;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
        }

        .store-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2mm;
            word-wrap: break-word;
            line-height: 1.2;
        }

        .qr-section {
            text-align: center;
            margin: 4mm 0;
        }

        .qr-code {
            width: 50mm;
            height: 50mm;
            margin: 0 auto;
            display: block;
            border: 1px solid #ddd;
            padding: 2mm;
        }

        .info-section {
            font-size: 10px;
            line-height: 1.3;
            margin-top: 3mm;
        }

        .info-row {
            margin-bottom: 1.5mm;
            word-wrap: break-word;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 15mm;
        }

        .footer {
            text-align: center;
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
            font-size: 9px;
        }

        .print-actions {
            text-align: center;
            margin: 10mm 0;
            background: #f8f9fa;
            padding: 10mm;
            border-radius: 5mm;
            border: 1px solid #dee2e6;
        }

        .btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 0 8px;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }

        .btn-secondary:hover {
            background: linear-gradient(45deg, #5a6268, #495057);
            box-shadow: 0 6px 12px rgba(108, 117, 125, 0.4);
        }

        /* Print styles */
        @media print {
            .print-actions {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .thermal-container {
                width: 100%;
                max-width: none;
                margin: 0;
                page-break-inside: avoid;
                box-shadow: none;
            }

            .qr-code {
                border: none;
                padding: 0;
            }
        }

        /* Responsive untuk preview di browser */
        @media screen and (max-width: 768px) {
            .thermal-container {
                width: 95%;
                max-width: 300px;
            }

            .print-actions {
                margin: 5mm;
                padding: 5mm;
            }
        }
    </style>
</head>

<body>
    <!-- Print Actions (hidden saat print) -->
    <div class="print-actions">
        <h3>Preview QR Code Toko</h3>
        <p><strong>Printer Thermal 80mm</strong></p>
        <p>Pastikan printer thermal sudah siap dan kertas thermal sudah terpasang</p>
        <hr style="margin: 15px 0; border: 1px dashed #ccc;">
        <button class="btn" onclick="window.print()">
            🖨️ Cetak Sekarang
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            ❌ Tutup
        </button>
    </div>

    <!-- Thermal Print Layout -->
    <div class="thermal-container">
        <!-- Header -->
        <div class="header">
            <div class="store-name"><?= htmlspecialchars($toko['nama_toko']) ?></div>
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($toko['nama_toko']) ?>"
                alt="QR Code"
                class="qr-code">
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">ID:</span><?= $toko['id'] ?>
            </div>
            <div class="info-row">
                <span class="label">Toko:</span><?= htmlspecialchars($toko['nama_toko']) ?>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer">

            <div><?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <script>
        // Auto print saat halaman dimuat (opsional)
        // window.onload = function() {
        //     window.print();
        // }

        // Close window after print
        window.onafterprint = function() {
            // window.close();
        }
    </script>
</body>

</html>