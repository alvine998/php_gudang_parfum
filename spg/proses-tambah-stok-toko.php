<?php
session_start();
require_once '../koneksi.php';

// ===== Timezone (penting agar CURDATE() cocok) =====
date_default_timezone_set('Asia/Jakarta');
@$conn->query("SET time_zone = '+07:00'");

// Cek login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'spg') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$kode_barcode = $_POST['kode_barcode'] ?? '';
$id_toko      = isset($_POST['id_toko']) ? (int)$_POST['id_toko'] : 0;
$jumlah       = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0; // biasanya = 1
$username     = $_SESSION['username'];

if ($kode_barcode === '') {
    echo json_encode(['status' => 'error', 'message' => 'Kode barcode tidak boleh kosong']);
    exit;
}
if ($id_toko <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Toko tidak valid']);
    exit;
}
if ($jumlah <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Jumlah harus lebih dari 0']);
    exit;
}

try {
    // Mulai transaksi
    $conn->begin_transaction();

    // 1) Ambil data barcode (lock barisnya)
    $sql = "SELECT bp.id, bp.nama_barang, bp.status, bp.kode_barcode
            FROM barcode_produk bp
            WHERE bp.kode_barcode = ? FOR UPDATE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode_barcode);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Barcode tidak ditemukan']);
        exit;
    }
    $barcode = $res->fetch_assoc();
    $id_barcode_produk = (int)$barcode['id'];
    $nama_barang       = $barcode['nama_barang'];
    $status_sekarang   = $barcode['status'];

    // Cegah barcode dipakai ulang
    if (stripos($status_sekarang, 'di_toko') === 0 || stripos($status_sekarang, 'terjual') === 0) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Barcode sudah digunakan: ' . $status_sekarang]);
        exit;
    }

    // 2) Ambil nama toko
    $toko_stmt = $conn->prepare("SELECT nama_toko FROM toko WHERE id = ?");
    $toko_stmt->bind_param("i", $id_toko);
    $toko_stmt->execute();
    $toko_res = $toko_stmt->get_result();
    if ($toko_res->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Toko tidak ditemukan']);
        exit;
    }
    $nama_toko = $toko_res->fetch_assoc()['nama_toko'];

    // 3) Update / Insert stok_toko
    $check_stmt = $conn->prepare("SELECT id, jumlah FROM stok_toko WHERE id_toko = ? AND nama_barang = ?");
    $check_stmt->bind_param("is", $id_toko, $nama_barang);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    $stok_baru = 0;
    $id_stok_toko = 0;

    if ($check_res->num_rows > 0) {
        $row = $check_res->fetch_assoc();
        $id_stok_toko = (int)$row['id'];
        $stok_baru    = (int)$row['jumlah'] + $jumlah;

        $upd = $conn->prepare("UPDATE stok_toko SET jumlah = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param("ii", $stok_baru, $id_stok_toko);
        if (!$upd->execute()) {
            throw new Exception("Gagal update stok toko: " . $upd->error);
        }
    } else {
        $stok_baru = $jumlah;
        $ins = $conn->prepare("INSERT INTO stok_toko (id_toko, nama_barang, jumlah, updated_at) VALUES (?, ?, ?, NOW())");
        $ins->bind_param("isi", $id_toko, $nama_barang, $stok_baru);
        if (!$ins->execute()) {
            throw new Exception("Gagal menambah stok toko: " . $ins->error);
        }
        $id_stok_toko = (int)$ins->insert_id; // penting untuk riwayat
    }

    // 4) Update status barcode -> di_toko - NamaToko
    $new_status = "di_toko - " . $nama_toko;
    $status_stmt = $conn->prepare("UPDATE barcode_produk SET status = ?, updated_at = NOW() WHERE id = ?");
    $status_stmt->bind_param("si", $new_status, $id_barcode_produk);
    if (!$status_stmt->execute()) {
        throw new Exception("Gagal update status barcode: " . $status_stmt->error);
    }

    // 5) Tidak ada insert ke laporan_stok_toko, riwayat hanya dari log_aktivitas

    // 6) (Opsional) Update last_visit toko
    // @$conn->query("UPDATE toko SET last_visit = NOW() WHERE id = ".(int)$id_toko);

    // 7) Log aktivitas (jika tabel ada)
    $check_log = $conn->query("SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log && $check_log->num_rows > 0) {
        $aksi = "Menambah stok $nama_toko: {$nama_barang} (Barcode: {$kode_barcode}) sebanyak {$jumlah} pcs";
        $log = $conn->prepare("INSERT INTO log_aktivitas (username, aksi, tabel, waktu) VALUES (?, ?, 'stok_toko', NOW())");
        $log->bind_param("ss", $username, $aksi);
        $log->execute();
    }

    // Commit
    $conn->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Stok berhasil ditambahkan ke toko',
        'data'    => [
            'nama_barang'   => $nama_barang,
            'kode_barcode'  => $kode_barcode,
            'jumlah_tambah' => $jumlah,
            'stok_baru'     => $stok_baru,
            'toko'          => $nama_toko
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("[Tambah Stok Toko] " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}

$conn->close();
