<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// Pakai zona WIB agar filter "hari ini" akurat
date_default_timezone_set('Asia/Jakarta');
@$conn->query("SET time_zone = '+07:00'");

// Cek login minimal
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_toko = isset($_GET['id_toko']) ? (int)$_GET['id_toko'] : 0;
if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}


// Ambil nama toko
$qToko = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko");
if (!$qToko || mysqli_num_rows($qToko) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Toko tidak ditemukan', 'data' => []]);
    exit;
}
$nama_toko = mysqli_fetch_assoc($qToko)['nama_toko'];

// Ambil log aktivitas hari ini yang menambah stok ke toko ini
$tgl = date('Y-m-d');
$sql = "SELECT * FROM log_aktivitas WHERE aksi LIKE ? AND tabel = 'stok_toko' AND DATE(waktu) = ? ORDER BY waktu DESC";
$like = "%Menambah stok $nama_toko:%";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $like, $tgl);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    // Ekstrak info dari aksi
    $aksi = $row['aksi'];
    // Contoh aksi: Menambah stok NamaToko: NamaBarang (Barcode: 123456) sebanyak 1 pcs
    if (preg_match('/Menambah stok (.*?): (.*?) \(Barcode: (.*?)\) sebanyak (\d+) pcs/', $aksi, $m)) {
        $data[] = [
            'waktu' => date('H:i', strtotime($row['waktu'])),
            'nama_barang' => $m[2],
            'kode_barcode' => $m[3],
            'jumlah' => $m[4]
        ];
    }
}

if (count($data) > 0) {
    echo json_encode(['status' => 'success', 'data' => $data]);
} else {
    echo json_encode(['status' => 'success', 'data' => []]);
}
