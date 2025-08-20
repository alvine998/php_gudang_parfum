<?php
require_once __DIR__ . '/../vendor/autoload.php';
include '../koneksi.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Ambil filter

$tanggal_filter = $_GET['tanggal'] ?? '';
$varian_filter = $_GET['varian'] ?? '';
$id_dari = isset($_GET['id_dari']) ? (int)$_GET['id_dari'] : '';
$id_sampai = isset($_GET['id_sampai']) ? (int)$_GET['id_sampai'] : '';

$kondisi = "WHERE 1";
if ($tanggal_filter != '') $kondisi .= " AND DATE(updated_at) = '$tanggal_filter'";
if ($varian_filter != '') $kondisi .= " AND nama_barang = '$varian_filter'";
if ($id_dari !== '' && $id_sampai !== '' && $id_dari > 0 && $id_sampai >= $id_dari) {
    $kondisi .= " AND id BETWEEN $id_dari AND $id_sampai";
}

// Ambil data barcode
$query = mysqli_query($conn, "SELECT * FROM barcode_produk $kondisi ORDER BY id ASC");
$barcodes = mysqli_fetch_all($query, MYSQLI_ASSOC);

// QR options
$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel' => QRCode::ECC_L,
    'scale' => 1,
]);

// Buat folder QR
$temp_dir = __DIR__ . '/temp_qr/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}
?>

<!DOCTYPE html>
<html>

<head>

    <head>
        <title>Cetak Barcode Batch</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                margin: 10px;
                font-size: 12px;
                background: #f8f9fa;
            }

            .filter {
                margin-bottom: 20px;
            }

            .barcode-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0;
                align-items: flex-start;
                background: #fff;
                padding: 0;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            }

            .barcode {
                border: 1px solid #dee2e6;
                padding: 4px 2px 2px 2px;
                width: 90px;
                height: 160px;
                text-align: center;
                box-sizing: border-box;
                page-break-inside: avoid;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
                margin: 0;
            }

            .barcode img.qr {
                width: 1cm;
                height: 1cm;
                margin: 0 auto 6px auto;
                display: block;
            }

            .nama-barang {
                font-size: 11px;
                font-weight: 600;
                margin: 2px 0 0 0;
                padding: 0;
                line-height: 1.2;
                word-break: break-word;
                color: #212529;
            }

            .barcode .print-one {
                font-size: 11px;
                margin-top: 8px;
                padding: 3px 10px;
                border-radius: 5px;
                background: linear-gradient(45deg, #0d6efd, #0a58ca);
                color: #fff;
                border: none;
                cursor: pointer;
                transition: background 0.2s;
                box-shadow: 0 2px 6px rgba(13, 110, 253, 0.08);
            }

            .barcode .print-one:hover {
                background: linear-gradient(45deg, #0a58ca, #084298);
            }

            .no-print {
                margin-bottom: 14px;
            }

            @media print {

                .no-print,
                .filter,
                h2,
                .barcode .print-one {
                    display: none !important;
                }

                body {
                    margin: 0;
                    font-size: 11px;
                    background: #fff;
                }

                .barcode-list {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: flex-start;
                    padding: 0;
                    gap: 0;
                    box-shadow: none;
                    border-radius: 0;
                }

                .barcode {
                    margin: 0;
                    box-shadow: none;
                    border-radius: 0;
                    border: 1px solid #dee2e6;
                    page-break-inside: avoid;
                    width: 90px !important;
                    height: 120px !important;
                }
            }
        </style>
    </head>


    </style>
</head>

<body>

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
        </form>
    </div>

    <!-- Tombol Cetak -->
    <div class="no-print mb-3 container-fluid">
        <button onclick="window.print()" class="btn btn-success btn-sm">🖨️ Cetak Semua Barcode</button>
    </div>

    <!-- Daftar barcode -->
    <div class="barcode-list">
        <?php
        foreach ($barcodes as $item) {
            $filename = $temp_dir . $item['kode_barcode'] . '.png';
            $relativePath = 'temp_qr/' . $item['kode_barcode'] . '.png';

            if (!file_exists($filename)) {
                (new QRCode($options))->render($item['kode_barcode'], $filename);
            }

            echo "<div class='barcode'>";
            echo "<img src='$relativePath' alt='QR' class='qr'>";
            echo "<div class='nama-barang'>" . htmlspecialchars($item['nama_barang']) . "</div>";
            echo "<button class='print-one' onclick=\"printSingleBarcode('$relativePath','" . htmlspecialchars(addslashes($item['nama_barang'])) . "')\">Cetak</button>";
            echo "</div>";
        }
        ?>
    </div>

    <script>
        // Print satu barcode saja
        function printSingleBarcode(qrSrc, namaBarang) {
            const w = window.open('', '', 'width=400,height=400');
            w.document.write(`<!DOCTYPE html><html><head><title>Cetak QR</title><style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                .qr { width: 1cm; height: 1cm; margin: 10px 0 2px 0; display: block; }
                .nama-barang { font-size: 12px; font-weight: bold; margin: 2px 0 0 0; text-align: left; }
                @media print {
                    body { margin: 0; padding: 0; }
                    .qr, .nama-barang { box-shadow: none; border: none; }
                }
            </style></head><body>
                <img src='${qrSrc}' class='qr'><div class='nama-barang'>${namaBarang}</div>
                <script>window.onload = function(){window.print();window.onafterprint = function(){window.close();};};<\/script>
            </body></html>`);
            w.document.close();
        }
    </script>
</body>

</html>