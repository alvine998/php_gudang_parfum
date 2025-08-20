<?php
include '../koneksi.php';

// Set header JSON
header('Content-Type: application/json');

// Cek parameter
$id_toko = isset($_GET['id_toko']) ? intval($_GET['id_toko']) : 0;
$nama_toko = isset($_GET['nama_toko']) ? trim($_GET['nama_toko']) : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? trim($_GET['tanggal_awal']) : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? trim($_GET['tanggal_akhir']) : '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';

if ($id_toko <= 0 || empty($nama_toko)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']);
    exit;
}

try {
    // Escape nama toko untuk query
    $nama_toko_escaped = mysqli_real_escape_string($conn, $nama_toko);

    // Bangun kondisi WHERE untuk log aktivitas
    $kondisi_log = "WHERE (aksi LIKE '%$nama_toko_escaped%' OR aksi LIKE '%{$nama_toko_escaped}:%')";

    if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
        $kondisi_log .= " AND DATE(waktu) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    } elseif ($tanggal_awal !== '') {
        $kondisi_log .= " AND DATE(waktu) >= '$tanggal_awal'";
    } elseif ($tanggal_akhir !== '') {
        $kondisi_log .= " AND DATE(waktu) <= '$tanggal_akhir'";
    }

    // Ambil log aktivitas
    $query_log = "SELECT username, aksi, tabel, DATE_FORMAT(waktu, '%d-%m-%Y %H:%i:%s') as waktu 
                  FROM log_aktivitas 
                  $kondisi_log 
                  ORDER BY waktu $sort";

    $result_log = mysqli_query($conn, $query_log);

    if (!$result_log) {
        throw new Exception('Error query log: ' . mysqli_error($conn));
    }

    $logs = [];
    while ($row = mysqli_fetch_assoc($result_log)) {
        $logs[] = [
            'username' => htmlspecialchars($row['username']),
            'aksi' => htmlspecialchars($row['aksi']),
            'tabel' => htmlspecialchars($row['tabel']),
            'waktu' => $row['waktu']
        ];
    }

    // Ambil statistik stok toko
    $query_stok = "SELECT 
                    COUNT(*) as total_produk,
                    SUM(jumlah) as total_stok
                    FROM stok_toko 
                    WHERE id_toko = ?";
    $stmt_stok = $conn->prepare($query_stok);
    $stmt_stok->bind_param("i", $id_toko);
    $stmt_stok->execute();
    $result_stok = $stmt_stok->get_result();
    $data_stok = $result_stok->fetch_assoc();

    // Hitung total barang aktual dari barcode_produk
    $status_toko = "di_toko - " . $nama_toko;
    $query_barcode = "SELECT COUNT(*) as total_barang_aktual FROM barcode_produk WHERE status = ?";
    $stmt_barcode = $conn->prepare($query_barcode);
    $stmt_barcode->bind_param("s", $status_toko);
    $stmt_barcode->execute();
    $result_barcode = $stmt_barcode->get_result();
    $data_barcode = $result_barcode->fetch_assoc();

    // Statistik
    $stats = [
        'total' => count($logs),
        'stok' => intval($data_stok['total_stok'] ?? 0),
        'barang_aktual' => intval($data_barcode['total_barang_aktual'] ?? 0)
    ];

    echo json_encode([
        'status' => 'success',
        'logs' => $logs,
        'stats' => $stats,
        'message' => 'Data berhasil dimuat'
    ]);
} catch (Exception $e) {
    error_log("Error get log aktivitas toko: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memuat data: ' . $e->getMessage()
    ]);
}
