<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'collecting') {
    header("Location: ../index.php");
    exit;
}

// Cek apakah ada data struk
if (!isset($_SESSION['struk_collecting_data'])) {
    header("Location: dashboard-collecting.php");
    exit;
}

$struk_data = $_SESSION['struk_collecting_data'];
$toko = $struk_data['toko'];
$tanggal = $struk_data['tanggal'];
$username = $struk_data['username'];
$barang_terjual = $struk_data['barang_terjual'];
$barang_ditarik = $struk_data['barang_ditarik'];
$barang_dimasukan = $struk_data['barang_dimasukan'];
$summary = $struk_data['summary'];

// Clear session data setelah digunakan
unset($_SESSION['struk_collecting_data']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Collecting - <?= htmlspecialchars($toko['nama']) ?></title>
    <style>
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
            font-weight: 900;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
        }

        .header h2 {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 2mm;
        }

        .header p {
            font-size: 12px;
            font-weight: 900;
            margin: 1mm 0;
        }

        .info-section {
            margin-bottom: 4mm;
            font-size: 12px;
            font-weight: 900;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }

        .section {
            margin-bottom: 4mm;
        }

        .section-title {
            font-size: 13px;
            font-weight: 900;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 2mm;
            text-align: center;
        }

        .item {
            font-size: 11px;
            font-weight: 900;
            margin: 1mm 0;
            padding: 1mm 0;
            border-bottom: 1px dotted #ccc;
        }

        .item-name {
            font-weight: 900;
            margin-bottom: 0.5mm;
        }

        .item-code {
            color: #666;
            font-size: 8px;
        }

        .item-time {
            float: right;
            color: #666;
            font-size: 8px;
        }

        .item-price {
            float: right;
            font-weight: 900;
            color: #000;
        }

        .summary {
            border-top: 2px solid #000;
            padding-top: 3mm;
            margin-top: 4mm;
            font-size: 12px;
            font-weight: 900;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }

        .summary-total {
            font-weight: 900;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 2mm;
            margin-top: 2mm;
        }

        .footer {
            text-align: center;
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
            font-size: 10px;
            font-weight: 900;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }

        .print-controls {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="smartPrint()">
            <i class="fas fa-print"></i> Print Struk
        </button>
        <a href="dashboard-collecting.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="thermal-container">
        <!-- Header -->
        <div class="header">
            <h2>STRUK COLLECTING</h2>

        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <span>Toko:</span>
                <span><?= htmlspecialchars($toko['nama']) ?></span>
            </div>
            <?php if (!empty($toko['alamat'])): ?>
                <div class="info-row">
                    <span>Alamat:</span>
                    <span><?= htmlspecialchars($toko['alamat']) ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <span>Collecting:</span>
                <span><?= htmlspecialchars($username) ?></span>
            </div>
            <div class="info-row">
                <span>Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($tanggal)) ?></span>
            </div>
        </div>

        <!-- Barang Terjual -->
        <?php if (!empty($barang_terjual)): ?>
            <div class="section">
                <div class="section-title">BARANG TERJUAL (<?= count($barang_terjual) ?> item)</div>
                <?php foreach ($barang_terjual as $item): ?>
                    <div class="item clearfix">
                        <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                        <div class="item-code"><?= htmlspecialchars($item['kode_barcode']) ?></div>

                        <div class="item-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Barang Ditarik -->
        <?php if (!empty($barang_ditarik)): ?>
            <div class="section">
                <div class="section-title">BARANG DITARIK (<?= count($barang_ditarik) ?> item)</div>
                <?php foreach ($barang_ditarik as $item): ?>
                    <div class="item clearfix">
                        <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                        <div class="item-code"><?= htmlspecialchars($item['kode_barcode']) ?></div>
                        <div class="item-time"><?= date('H:i', strtotime($item['waktu'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Barang Dimasukan -->
        <?php if (!empty($barang_dimasukan)): ?>
            <div class="section">
                <div class="section-title">BARANG DIMASUKAN (<?= count($barang_dimasukan) ?> item)</div>
                <?php foreach ($barang_dimasukan as $item): ?>
                    <div class="item clearfix">
                        <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                        <div class="item-code"><?= htmlspecialchars($item['kode_barcode']) ?></div>
                        <div class="item-time"><?= date('H:i', strtotime($item['waktu'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Total Barang Terjual:</span>
                <span><?= $summary['total_terjual'] ?> item</span>
            </div>
            <div class="summary-row">
                <span>Total Barang Ditarik:</span>
                <span><?= $summary['total_ditarik'] ?> item</span>
            </div>
            <div class="summary-row">
                <span>Total Barang Dimasukan:</span>
                <span><?= $summary['total_dimasukan'] ?> item</span>
            </div>
            <div class="summary-total summary-row">
                <span>TOTAL Yang Dibayar:</span>
                <span>Rp <?= number_format($summary['total_pendapatan'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>*** STRUK COLLECTING ***</p>
            <p>Terima kasih</p>

        </div>
    </div>

    <script>
        // Auto print jika diperlukan
        document.addEventListener('DOMContentLoaded', function() {
            // Uncomment baris di bawah untuk auto print
            // window.print();
        });
    </script>

    <script>
        function isAndroidWebView() {
            // Deteksi user agent Android dan Website2APK
            var ua = navigator.userAgent || navigator.vendor || window.opera;
            return (/android/i.test(ua) && (window.Website2APK !== undefined));
        }

        function smartPrint() {
            if (isAndroidWebView()) {
                Website2APK.printPage();
            } else if (window.innerWidth >= 1024) {
                window.print();
            } else {
                // fallback: window.print untuk tablet
                window.print();
            }
        }

        // Close window after print (opsional)
        window.onafterprint = function() {
            // window.close();
        }
    </script>
</body>

</html>