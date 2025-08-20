<?php
require('fpdf/fpdf.php');
include '../koneksi.php';

// Ambil filter dari form POST
$tanggal_awal = $_POST['tanggal_awal'] ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? '';
$sort = ($_POST['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Bangun kondisi WHERE
$kondisi = "WHERE 1";
if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
    $kondisi .= " AND DATE(dibuat_pada) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
} elseif ($tanggal_awal !== '') {
    $kondisi .= " AND DATE(dibuat_pada) >= '$tanggal_awal'";
} elseif ($tanggal_akhir !== '') {
    $kondisi .= " AND DATE(dibuat_pada) <= '$tanggal_akhir'";
}

// Ambil data sesuai filter dan urutan
$query = mysqli_query($conn, "SELECT * FROM toko $kondisi ORDER BY dibuat_pada $sort");

// Inisialisasi PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(190, 10, 'Daftar Mitra XLPerfume', 0, 1, 'C');
$pdf->Ln(5);

// Header Tabel
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(10, 10, 'No', 1);
$pdf->Cell(55, 10, 'Nama Toko', 1);
$pdf->Cell(60, 10, 'Link Google Maps', 1);
$pdf->Cell(65, 10, 'Alamat Manual', 1);
$pdf->Ln();

// Isi Data
$pdf->SetFont('Arial', '', 10);
$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $pdf->Cell(10, 10, $no++, 1);
    $pdf->Cell(55, 10, $row['nama_toko'], 1);

    // Link Maps
    $lokasi = trim($row['lokasi_maps'] ?? '');
    if ($lokasi !== '') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Cell(60, 10, 'Lihat Lokasi', 1, 0, 'C', false, $lokasi);
    } else {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(60, 10, '-', 1, 0, 'C');
    }

    // Alamat Manual
    $alamat = trim($row['alamat_manual'] ?? '');
    if ($alamat !== '') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Cell(65, 10, 'Lihat Alamat', 1, 0, 'C', false, $alamat);
    } else {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(65, 10, '-', 1, 0, 'C');
    }

    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('D', 'daftar_mitra_xlperfume.pdf');
?>
