<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../koneksi.php';

header('Content-Type: application/json');

$username = $_SESSION['username'];
$id_toko = (int)$_POST['id_toko'];
$tanggal_hari_ini = date('Y-m-d');

if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}

// Ambil nama toko
$q_toko = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id = $id_toko");
if (!$q_toko || mysqli_num_rows($q_toko) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Toko tidak ditemukan']);
    exit;
}
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko_target = $d_toko['nama_toko']; // Nama toko yang kita cari

// Ambil data riwayat penambahan hari ini dari log_aktivitas dengan filter nama toko
$query_riwayat = "SELECT la.aksi, la.waktu
                  FROM log_aktivitas la 
                  WHERE la.username = '$username' 
                    AND la.tabel = 'stok_toko'
                    AND DATE(la.waktu) = '$tanggal_hari_ini'
                    AND la.aksi LIKE 'Menambah stok $nama_toko_target:%'
                  ORDER BY la.waktu ASC";

$result = mysqli_query($conn, $query_riwayat);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Error mengambil data: ' . mysqli_error($conn)]);
    exit;
}

$items_added = [];
$processed_items = []; // Untuk menghindari duplikasi

while ($row = mysqli_fetch_assoc($result)) {
    // Parse aksi untuk mendapatkan nama barang dan jumlah
    // Format: "Menambah stok $nama_toko: {$nama_barang} (Barcode: {$kode_barcode}) sebanyak {$jumlah} pcs"
    $aksi = $row['aksi'];

    // Extract nama toko, nama barang dan jumlah - updated regex
    if (
        preg_match('/Menambah stok (.+?): (.+?) \(Barcode: (.+?)\) sebanyak (\d+) pcs/', $aksi, $matches)
    ) {

        $nama_toko_log = trim($matches[1]);
        $nama_barang = trim($matches[2]);
        $kode_barcode = trim($matches[3]);
        $jumlah = (int)$matches[4];

        // Pastikan nama toko di log sesuai dengan toko yang dipilih
        if ($nama_toko_log === $nama_toko_target) {
            // Group by nama barang untuk menghindari duplikasi
            if (isset($processed_items[$nama_barang])) {
                $processed_items[$nama_barang]['jumlah'] += $jumlah;
            } else {
                $processed_items[$nama_barang] = [
                    'nama_barang' => $nama_barang,
                    'jumlah' => $jumlah,
                    'keterangan' => '',
                    'harga' => 15000,
                    'waktu_pertama' => $row['waktu']
                ];
            }
        }
    }
}

// Convert processed items ke array
foreach ($processed_items as $item) {
    $items_added[] = $item;
}

if (empty($items_added)) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data penambahan stok hari ini untuk print']);
    exit;
}

// Simpan data untuk print dalam session
$_SESSION['print_data'] = [
    'toko' => $d_toko['nama_toko'], // Gunakan nama toko dari database
    'tanggal' => date('Y-m-d H:i:s'),
    'items' => $items_added,
    'username' => $username
];

echo json_encode([
    'status' => 'success',
    'message' => 'Data print berhasil disiapkan',
    'total_items' => count($items_added)
]);
