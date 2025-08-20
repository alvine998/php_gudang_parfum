<?php
header('Content-Type: application/json');
require_once '../koneksi.php'; // sesuaikan path bila perlu

$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$toko  = $_GET['toko']  ?? ''; // opsional: filter per toko (kolom 'asal_toko')

/*
  Sumber: barcode_produk
  Kolom minimal: nama_barang, status, created_at, updated_at, asal_toko (opsional)
  Aturan:
  - Hanya hitung baris dengan LOWER(status) = 'terjual'
  - Jika start/end dikirim, FILTER HARUS DITAATI (strict). Tidak ada fallback semua waktu.
  - Filter tanggal diterapkan ke (DATE(updated_at) OR DATE(created_at)).
    > Jika kamu ingin SATU kolom saja, ganti bagian filter di bawah.
*/

function esc($c, $s){ return mysqli_real_escape_string($c, $s); }

$where = "WHERE LOWER(status) = 'terjual'";

// (opsional) filter toko
if ($toko !== '') {
  $where .= " AND asal_toko = '" . esc($conn, $toko) . "'";
}

// FILTER TANGGAL — STRICT
if ($start !== '' || $end !== '') {
  if ($start !== '' && $end !== '') {
    $s = esc($conn, $start); $e = esc($conn, $end);
    // Pakai salah satu baris di bawah:
    // 1) Kedua kolom (updated_at ATAU created_at) — default:
    $where .= " AND ( (updated_at IS NOT NULL AND DATE(updated_at) BETWEEN '$s' AND '$e')
                   OR (created_at IS NOT NULL AND DATE(created_at) BETWEEN '$s' AND '$e') )";

    // 2) HANYA updated_at (kalau mau, uncomment & hapus yang di atas)
    // $where .= " AND DATE(updated_at) BETWEEN '$s' AND '$e'";

    // 3) HANYA created_at (kalau mau, uncomment & hapus yang di atas)
    // $where .= " AND DATE(created_at) BETWEEN '$s' AND '$e'";

  } elseif ($start !== '') {
    $s = esc($conn, $start);
    $where .= " AND ( (updated_at IS NOT NULL AND DATE(updated_at) >= '$s')
                   OR (created_at IS NOT NULL AND DATE(created_at) >= '$s') )";
    // Atau ganti ke satu kolom saja seperti contoh di atas.
  } else { // only end
    $e = esc($conn, $end);
    $where .= " AND ( (updated_at IS NOT NULL AND DATE(updated_at) <= '$e')
                   OR (created_at IS NOT NULL AND DATE(created_at) <= '$e') )";
  }
}

// Query agregasi STRICT sesuai filter
$sql = "
  SELECT nama_barang, COUNT(*) AS total_qty
  FROM barcode_produk
  $where
  GROUP BY nama_barang
  ORDER BY total_qty DESC, nama_barang ASC
";

$res = mysqli_query($conn, $sql);
if (!$res) {
  echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
  exit;
}

$labels = [];
$data   = [];
$rows   = [];
$total  = 0;

while ($r = mysqli_fetch_assoc($res)) {
  $qty = (int)$r['total_qty'];
  $labels[] = $r['nama_barang'];
  $data[]   = $qty;
  $rows[]   = ['nama_barang' => $r['nama_barang'], 'total_qty' => $qty];
  $total   += $qty;
}

// Range label hanya untuk info tampilan
$rangeLabel = '';
if ($start || $end) {
  $rangeLabel = sprintf('( %s s/d %s )', $start ?: '-', $end ?: '-');
}
if ($toko !== '') {
  $rangeLabel .= ($rangeLabel ? ' · ' : '') . 'Toko: ' . $toko;
}

// Keluarkan hasil—jika tidak ada data di rentang itu, array kosong & total=0 (sesuai permintaan)
echo json_encode([
  'status'     => 'success',
  'labels'     => $labels,
  'data'       => $data,
  'rows'       => $rows,
  'total'      => $total,
  'rangeLabel' => $rangeLabel
]);
