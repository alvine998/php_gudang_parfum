<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
    header("Location: ../index.php");
    exit;
}

// Cek apakah ada data print
if (!isset($_SESSION['print_data'])) {
    header("Location: dashboard-spg.php");
    exit;
}

$print_data = $_SESSION['print_data'];
$toko = $print_data['toko'];
$tanggal = $print_data['tanggal'];
$items = $print_data['items'];
$username = $print_data['username'];

// Hitung total
$total_items = 0;
$total_harga = 0;
foreach ($items as $item) {
    $total_items += $item['jumlah'];
    $total_harga += $item['jumlah'] * $item['harga'];
}

// Clear session data setelah digunakan
unset($_SESSION['print_data']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Tambah Stok - <?= htmlspecialchars($toko) ?></title>
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
            font-size: 16px;
            font-weight: 900;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
        }

        .header h2 {
            font-size: 20px;
            font-weight: 900;
            margin-bottom: 2mm;
        }

        .header p {
            font-size: 14px;
            font-weight: 900;
            margin: 1mm 0;
        }

        .info-section {
            margin-bottom: 4mm;
            font-size: 14px;
            font-weight: 900;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }

        .items-section {
            margin-bottom: 4mm;
        }

        .items-header {
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
            font-size: 14px;
            font-weight: 900;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 1.5mm 0;
            font-size: 13px;
            font-weight: 900;
            padding: 1mm 0;
        }

        .item-name {
            flex: 1;
            margin-right: 2mm;
        }

        .item-qty {
            width: 8mm;
            text-align: center;
        }

        .item-price {
            width: 15mm;
            text-align: right;
        }

        .item-total {
            width: 15mm;
            text-align: right;
        }

        .total-section {
            border-top: 1px dashed #000;
            padding-top: 3mm;
            margin-top: 3mm;
            font-size: 15px;
            font-weight: 900;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
            font-size: 13px;
            font-weight: 900;
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

        .btn-success {
            background: linear-gradient(45deg, #28a745, #1e7e34);
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #1e7e34, #155724);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
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
                padding: 0;
                margin: 0;
            }

            @page {
                margin: 0;
            }
        }

        @media screen and (max-width: 480px) {
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
        <h3>Receipt Tambah Stok</h3>
        <p><strong>Printer Thermal 80mm</strong></p>
        <p>Pastikan printer thermal sudah siap dan kertas thermal sudah terpasang</p>
        <hr style="margin: 15px 0; border: 1px dashed #ccc;">
     <button class="btn" onclick="smartPrint();">
            🖨️ Cetak Sekarang
        </button>

        <a href="lihat-toko.php" class="btn btn-secondary">
            ❌ Tutup
        </a>
    </div>

    <!-- Thermal Receipt Content -->
    <div class="thermal-container">
        <!-- Header -->
        <div class="header">
            <h2>TAMBAH STOK TOKO</h2>

            <p>================================</p>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <span>Toko:</span>
                <span><?= htmlspecialchars($toko) ?></span>
            </div>
            <div class="info-row">
                <span>Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($tanggal)) ?></span>
            </div>
            <div class="info-row">
                <span>SPG:</span>
                <span><?= htmlspecialchars($username) ?></span>
            </div>
        </div>

        <!-- Items Header -->
        <div class="items-header">
            <div style="display: flex; justify-content: space-between;">
                <span style="flex: 1;">BARANG</span>
                <span style="width: 8mm; text-align: center;">QTY</span>
                <span style="width: 15mm; text-align: right;">HARGA</span>
                <span style="width: 15mm; text-align: right;">TOTAL</span>
            </div>
        </div>

        <!-- Items List -->
        <div class="items-section">
            <?php foreach ($items as $item): ?>
                <div class="item-row">
                    <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                    <div class="item-qty"><?= $item['jumlah'] ?></div>
                    <div class="item-price"><?= number_format($item['harga'], 0, ',', '.') ?></div>
                    <div class="item-total"><?= number_format($item['jumlah'] * $item['harga'], 0, ',', '.') ?></div>
                </div>
                <?php if (!empty($item['keterangan'])): ?>
                    <div style="font-size: 8px; color: #666; margin-left: 2mm; margin-bottom: 1mm;">
                        Ket: <?= htmlspecialchars($item['keterangan']) ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span>TOTAL ITEMS:</span>
                <span><?= $total_items ?> pcs</span>
            </div>
            <div class="total-row" style="font-size: 12px; margin-top: 2mm;">
                <span>TOTAL HARGA:</span>
                <span>Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>================================</p>
            <p>Terima kasih</p>


        </div>
    </div>

    <script>
        // Auto print jika ada parameter print=1
        if (window.location.search.includes('print=1')) {
            window.print();
        }
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