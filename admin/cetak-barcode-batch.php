<?php
require_once __DIR__ . '/../vendor/autoload.php';
include '../koneksi.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Ambil filter
$tanggal_filter = $_GET['tanggal'] ?? '';
$varian_filter  = $_GET['varian'] ?? '';
$id_dari        = isset($_GET['id_dari']) ? (int)$_GET['id_dari'] : '';
$id_sampai      = isset($_GET['id_sampai']) ? (int)$_GET['id_sampai'] : '';

$kondisi = "WHERE 1";
if ($tanggal_filter != '') $kondisi .= " AND DATE(updated_at) = '$tanggal_filter'";
if ($varian_filter  != '') $kondisi .= " AND nama_barang = '$varian_filter'";
if ($id_dari !== '' && $id_sampai !== '' && $id_dari > 0 && $id_sampai >= $id_dari) {
    $kondisi .= " AND id BETWEEN $id_dari AND $id_sampai";
}

// Ambil data barcode
$query    = mysqli_query($conn, "SELECT * FROM barcode_produk $kondisi ORDER BY id DESC");
$barcodes = mysqli_fetch_all($query, MYSQLI_ASSOC);

// QR options
$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'   => QRCode::ECC_L,
    'scale'      => 1,
]);

// Buat folder QR
$temp_dir = __DIR__ . '/temp_qr/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Flag mode cutting
$mode_cutting = isset($_GET['mode']) && $_GET['mode'] === 'cutting';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Barcode Batch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{ --qr-size: 2cm; }

        body{
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 10px;
            font-size: 12px;
            background: #f8f9fa;
        }

        .filter{ margin-bottom: 20px; }

        .barcode-list{
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            align-items: flex-start;
            background: #fff;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }

        /* Kartu qr: border pas konten, tanpa width/height fixed */
        .barcode{
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            box-sizing: border-box;
            border: 1px solid #dee2e6;   /* border fit konten */
            border-radius: 0;
            background: #fff;
            padding: 2px;                /* tipis, pas konten */
            margin: 0;
            page-break-inside: avoid;
        }

        .barcode img.qr{
            width: var(--qr-size);
            height: var(--qr-size);
            display: block;
            margin: 0;                   /* mepet ke nama */
        }

        /* Nama varian lebih kecil & mepet QR */
        .nama-barang{
            font-size: 9px;
            font-weight: 600;
            margin: 0;                   /* rapat ke QR */
            padding: 0;
            line-height: 1.05;
            word-break: break-word;
            color: #212529;
            text-align: center;
        }

        .barcode .print-one{
            font-size: 11px;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 5px;
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background .2s;
            box-shadow: 0 2px 6px rgba(13,110,253,.08);
        }
        .barcode .print-one:hover{ background: linear-gradient(45deg,#0a58ca,#084298); }

        .no-print{ margin-bottom: 14px; }

        /* === MODE CUTTING: sembunyikan isi tapi jaga ukuran kartu === */
        .cutting .qr,
        .cutting .nama-barang,
        .cutting .print-one{
            visibility: hidden;      /* tetap ambil ruang */
        }
        /* Biar tombol tidak bisa diklik saat cutting */
        .cutting .print-one{ pointer-events: none; }

        @media print{
            .no-print, .filter, h2, .barcode .print-one{ display: none !important; }
            body{
                margin: 0;
                font-size: 11px;
                background: #fff;
            }
            .barcode-list{
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                padding: 0;
                gap: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .barcode{
                border: 1px solid #000;   /* border lebih tegas saat print */
                border-radius: 0;
                box-shadow: none;
                padding: 2px;
                margin: 0;
            }
        }
    </style>
</head>
<body class="<?= $mode_cutting ? 'cutting' : '' ?>">

<h2 class="no-print">Daftar Barcode Siap Cetak</h2>

<!-- Filter -->
<div class="filter no-print container-fluid">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label mb-0">Tanggal
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" class="form-control form-control-sm">
            </label>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">Varian
                <select name="varian" class="form-select form-select-sm">
                    <option value="">-- Semua --</option>
                    <?php
                    $varian_query = mysqli_query($conn, "SELECT DISTINCT nama_barang FROM barcode_produk ORDER BY nama_barang");
                    while ($v = mysqli_fetch_assoc($varian_query)) {
                        $selected = ($v['nama_barang'] == $varian_filter) ? 'selected' : '';
                        echo "<option $selected>" . htmlspecialchars($v['nama_barang']) . "</option>";
                    }
                    ?>
                </select>
            </label>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">ID dari
                <input type="number" name="id_dari" min="1" value="<?= htmlspecialchars($id_dari) ?>" class="form-control form-control-sm" style="width:80px;">
            </label>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">sampai
                <input type="number" name="id_sampai" min="1" value="<?= htmlspecialchars($id_sampai) ?>" class="form-control form-control-sm" style="width:80px;">
            </label>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.href=window.location.pathname">Clear</button>
        </div>
        <!-- Tambah toggle cepat -->
        <div class="col-auto">
            <?php if(!$mode_cutting): ?>
                <a class="btn btn-outline-dark btn-sm" href="?mode=cutting">Mode Cutting</a>
            <?php else: ?>
                <a class="btn btn-outline-secondary btn-sm" href="./cetak-barcode-batch.php">Mode Normal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tombol Cetak -->
<div class="no-print mb-3 container-fluid">
    <button onclick="smartPrint()" class="btn btn-success btn-sm">🖨️ Cetak Semua Barcode</button>
</div>

<!-- Daftar barcode -->
<div class="barcode-list">
    <?php
    foreach ($barcodes as $item) {
        $filename     = $temp_dir . $item['kode_barcode'] . '.png';
        $relativePath = 'temp_qr/' . $item['kode_barcode'] . '.png';

        if (!file_exists($filename)) {
            (new QRCode($options))->render($item['kode_barcode'], $filename);
        }

        echo "<div class='barcode'>";
        echo "<img src='$relativePath' alt='QR' class='qr'>";
        echo "<div class='nama-barang'>" . htmlspecialchars($item['nama_barang']) . "</div>";
        echo "<button class='print-one' onclick=\"printSingleBarcode('$relativePath','" . htmlspecialchars(addslashes($item['nama_barang'])) . "','ID: " . htmlspecialchars($item['id']) . "')\">Cetak</button>";
        echo "</div>";
    }
    ?>
</div>

<script>
function printSingleBarcode(qrSrc, namaBarang, idBarang){
    const w = window.open();
    w.document.write(`<!DOCTYPE html><html><head><title>Cetak QR</title><style>
        :root{ --qr-size: 2cm; }
        *{ box-sizing: border-box; }
        body{ font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
        .barcode{
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border: 1px solid #000;
            border-radius: 0;
            background: #fff;
            padding: 2px;
            margin: 0;
        }
        .qr{
            width: var(--qr-size);
            height: var(--qr-size);
            display: block;
            margin: 0;
        }
        .nama-barang{
            font-size: 9px;
            font-weight: 600;
            margin: 0;
            padding: 0;
            line-height: 1.05;
            color: #212529;
            text-align: center;
            word-break: break-word;
        }
        @media print{
            body{ margin: 0; padding: 0; background: #fff; }
            .barcode{ border: 1px solid #000; border-radius: 0; padding: 2px; }
        }
    </style></head><body>
        <div class="barcode">
            <img src="${qrSrc}" class="qr" />
            <div class="nama-barang">${namaBarang}</div>
        </div>
        <script>window.onload = function(){ window.print(); window.onafterprint = function(){ window.close(); }; };<\/script>
    </body></html>`);
    w.document.close();
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
