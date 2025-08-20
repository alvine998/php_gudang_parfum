<?php
require('fpdf/fpdf.php');
include '../koneksi.php';

$id_toko       = $_POST['id_toko']       ?? '';
$tanggal_awal  = $_POST['tanggal_awal']  ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? '';

// ---------- Filter ----------
$where = "WHERE 1";
if ($id_toko) {
    $where .= " AND lst.id_toko = " . intval($id_toko);
}
if ($tanggal_awal && $tanggal_akhir) {
    $where .= " AND DATE(lst.created_at) BETWEEN '" . mysqli_real_escape_string($conn, $tanggal_awal) . "' AND '" . mysqli_real_escape_string($conn, $tanggal_akhir) . "'";
} elseif ($tanggal_awal) {
    $where .= " AND DATE(lst.created_at) >= '" . mysqli_real_escape_string($conn, $tanggal_awal) . "'";
} elseif ($tanggal_akhir) {
    $where .= " AND DATE(lst.created_at) <= '" . mysqli_real_escape_string($conn, $tanggal_akhir) . "'";
}

// ---------- Query data ----------
$sql = "SELECT lst.*, t.nama_toko, bp.nama_barang, bp.kode_barcode, st.jumlah AS stok_terakhir
        FROM laporan_stok_toko lst
        JOIN toko t ON lst.id_toko = t.id
        JOIN barcode_produk bp ON lst.id_barcode_produk = bp.id
        JOIN stok_toko st ON lst.id_stok_toko = st.id
        $where
        ORDER BY lst.created_at DESC";
$result = mysqli_query($conn, $sql);

// ---------- PDF ----------
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Header judul
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 12, 'Laporan Barang Terjual di Toko', 0, 1, 'C');
$pdf->SetDrawColor(180, 180, 180);
$pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
$pdf->Ln(2);

// Info filter
$pdf->SetFont('Arial', '', 11);
if ($id_toko) {
    $toko_nama = '-';
    $toko_q = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = " . intval($id_toko));
    if ($rowT = mysqli_fetch_assoc($toko_q)) {
        $toko_nama = $rowT['nama_toko'];
    }
    $pdf->Cell(0, 8, 'Toko: ' . $toko_nama, 0, 1);
}
if ($tanggal_awal || $tanggal_akhir) {
    $pdf->Cell(0, 8, 'Periode: ' . ($tanggal_awal ?: '-') . ' s/d ' . ($tanggal_akhir ?: '-'), 0, 1);
}
$pdf->Ln(2);

// ---------- Header tabel ----------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(40, 40, 40);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(12, 9, 'No',            1, 0, 'C', true);
$pdf->Cell(40, 9, 'Toko',          1, 0, 'C', true);
$pdf->Cell(60, 9, 'Nama Barang',   1, 0, 'C', true);
$pdf->Cell(35, 9, 'Kode Barcode',  1, 0, 'C', true);
$pdf->Cell(48, 9, 'Waktu Terjual', 1, 1, 'C', true);

// ---------- Isi tabel ----------
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$no = 1;
$total = 0;

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Warna selang-seling
        $pdf->SetFillColor(($no % 2 == 0) ? 230 : 255, ($no % 2 == 0) ? 230 : 255, ($no % 2 == 0) ? 230 : 255);

        $pdf->Cell(12, 8, $no,                1, 0, 'C', true);
        $pdf->Cell(40, 8, $row['nama_toko'],  1, 0, 'C', true);
        $pdf->Cell(60, 8, $row['nama_barang'],1, 0, 'C', true);

        // Potong barcode jika kepanjangan
        $barcode = $row['kode_barcode'];
        if (strlen($barcode) > 18) {
            $barcode = substr($barcode, 0, 15) . '...';
        }
        $pdf->Cell(35, 8, $barcode, 1, 0, 'C', true);
        $pdf->Cell(48, 8, date('d-m-Y H:i', strtotime($row['created_at'])), 1, 1, 'C', true);

        $no++;
        $total++; // 1 baris = 1 item terjual
    }
} else {
    // Baris keterangan jika kosong
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell(195, 8, 'Tidak ada data', 1, 1, 'C', true);
}

// ---------- Baris Total ----------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(12 + 40 + 60 + 35, 9, 'Total Terjual', 1, 0, 'R', true); // colspan 4 kolom pertama (147mm)
$pdf->Cell(48, 9, number_format($total, 0, ',', '.'), 1, 1, 'C', true);

$pdf->Output('I', 'laporan-barang-terjual.pdf');
