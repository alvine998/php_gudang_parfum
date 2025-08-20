<?php
// PASTIKAN TIDAK ADA SPASI/BARIS KOSONG SEBELUM INI!
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// ob_start();
session_start();
header('Content-Type: application/json');
require_once '../koneksi.php';



$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$kode_barcode = isset($_POST['kode_barcode']) ? trim($_POST['kode_barcode']) : '';

if ($id <= 0 || $kode_barcode == '') {
    $msg = 'ID atau barcode tidak valid';
    $json_status = 'error';
    goto output_json;
}

global $conn;
$stmt = $conn->prepare('SELECT * FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
if (!$stmt) {
    $msg = 'Query error: ' . $conn->error;
    $json_status = 'error';
    goto output_json;
}
$stmt->bind_param('is', $id, $kode_barcode);
$stmt->execute();
$res = $stmt->get_result();
$produk = $res->fetch_assoc();
$stmt->close();

if (!$produk) {
    $msg = 'Produk tidak ditemukan';
    $json_status = 'error';
    goto output_json;
}

$status = $produk['status'];
$nama_barang = $produk['nama_barang'];

if (strpos($status, 'di_toko - ') === 0) {
    $nama_toko = trim(substr($status, strlen('di_toko - ')));
    $stmtToko = $conn->prepare('SELECT id FROM toko WHERE nama_toko = ? LIMIT 1');
    $stmtToko->bind_param('s', $nama_toko);
    $stmtToko->execute();
    $resToko = $stmtToko->get_result();
    $rowToko = $resToko->fetch_assoc();
    $stmtToko->close();
    if ($rowToko) {
        $id_toko = $rowToko['id'];
        $stmtStok = $conn->prepare('UPDATE stok_toko SET jumlah = jumlah - 1 WHERE id_toko = ? AND nama_barang = ? AND jumlah > 0');
        $stmtStok->bind_param('is', $id_toko, $nama_barang);
        $stmtStok->execute();
        $stmtStok->close();
    }
    $stmtDel = $conn->prepare('DELETE FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
    $stmtDel->bind_param('is', $id, $kode_barcode);
    $stmtDel->execute();
    $stmtDel->close();
    $msg = 'Produk dihapus dan stok toko dikurangi.';
    $json_status = 'success';
} elseif ($status === 'di_gudang') {
    $stmtStok = $conn->prepare('UPDATE stok_gudang SET jumlah = jumlah - 1 WHERE nama_barang = ? AND jumlah > 0');
    $stmtStok->bind_param('s', $nama_barang);
    $stmtStok->execute();
    $stmtStok->close();
    $stmtDel = $conn->prepare('DELETE FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
    $stmtDel->bind_param('is', $id, $kode_barcode);
    $stmtDel->execute();
    $stmtDel->close();
    $msg = 'Produk dihapus dan stok gudang dikurangi.';
    $json_status = 'success';
} elseif (strpos($status, 'di_spg') === 0 || strpos($status, 'di_collecting') === 0) {
    $stmtDel = $conn->prepare('DELETE FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
    $stmtDel->bind_param('is', $id, $kode_barcode);
    $stmtDel->execute();
    $stmtDel->close();
    $msg = 'Produk dihapus (SPG/Collecting).';
    $json_status = 'success';
} else if ($status === 'terjual') {
    // Hapus laporan stok toko berdasarkan id barcode_produk
    $stmtLap = $conn->prepare('DELETE FROM laporan_stok_toko WHERE id_barcode_produk = ?');
    $stmtLap->bind_param('i', $id);
    $stmtLap->execute();
    $stmtLap->close();
    $stmtDel = $conn->prepare('DELETE FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
    $stmtDel->bind_param('is', $id, $kode_barcode);
    $stmtDel->execute();
    $stmtDel->close();
    $msg = 'Produk terjual dihapus beserta laporan stok toko.';
    $json_status = 'success';
} else {
    $stmtDel = $conn->prepare('DELETE FROM barcode_produk WHERE id = ? AND kode_barcode = ?');
    $stmtDel->bind_param('is', $id, $kode_barcode);
    $stmtDel->execute();
    $stmtDel->close();
    $msg = 'Produk dihapus.';
    $json_status = 'success';
}

$user = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin';
$log = $conn->prepare('INSERT INTO log_aktivitas (username, aksi, waktu) VALUES (?, ?, NOW())');
if ($log) {
    $aktivitas = 'Hapus produk ID: ' . $id . ', Barcode: ' . $kode_barcode . ', Status: ' . $status;
    $log->bind_param('ss', $user, $aktivitas);
    $log->execute();
    $log->close();
}

output_json:
echo json_encode(['status' => $json_status ?? 'error', 'message' => $msg ?? 'Terjadi error']);
