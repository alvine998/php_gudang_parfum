<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'collecting') {
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

try {
    // Ambil nama toko
    $q_toko = mysqli_query($conn, "SELECT nama_toko, alamat_manual FROM toko WHERE id = $id_toko");
    if (!$q_toko || mysqli_num_rows($q_toko) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Toko tidak ditemukan']);
        exit;
    }
    $d_toko = mysqli_fetch_assoc($q_toko);
    $nama_toko = $d_toko['nama_toko'];
    $alamat_toko = $d_toko['alamat_manual'];

    // Ambil data aktivitas hari ini dari log_aktivitas untuk toko ini
    $barang_terjual = [];
    $barang_ditarik = [];
    $barang_dimasukan = [];

    // 1. Barang Terjual - dari log individual
    $query_terjual = "SELECT aksi, waktu 
                      FROM log_aktivitas 
                      WHERE username = '$username' 
                        AND tabel = 'barcode_produk'
                        AND DATE(waktu) = '$tanggal_hari_ini'
                        AND aksi LIKE 'Barang terjual di $nama_toko:%'
                      ORDER BY waktu ASC";

    $result_terjual = mysqli_query($conn, $query_terjual);
    while ($row = mysqli_fetch_assoc($result_terjual)) {
        // Parse: "Barang terjual di [Toko]: [Nama Barang] (Barcode: [Kode])"
        if (preg_match('/Barang terjual di .+: (.+?) \(Barcode: (.+?)\)/', $row['aksi'], $matches)) {
            $barang_terjual[] = [
                'nama_barang' => trim($matches[1]),
                'kode_barcode' => trim($matches[2]),
                'waktu' => $row['waktu'],
                'harga' => $id_toko > 333 ? 15000 : 13000
            ];
        }
    }

    // 2. Barang Ditarik - dari log individual dengan format "Menarik stok"
    $query_tarik = "SELECT aksi, waktu 
                    FROM log_aktivitas 
                    WHERE username = '$username' 
                      AND tabel = 'stok_toko'
                      AND DATE(waktu) = '$tanggal_hari_ini'
                      AND aksi LIKE 'Menarik stok Toko $nama_toko:%'
                    ORDER BY waktu ASC";

    $result_tarik = mysqli_query($conn, $query_tarik);
    while ($row = mysqli_fetch_assoc($result_tarik)) {
        // Parse: "Menarik stok Toko [Nama Toko]: [Nama Barang] (Barcode: [Kode]) sebanyak 1 pcs"
        if (preg_match('/Menarik stok Toko .+: (.+?) \(Barcode: (.+?)\) sebanyak (\d+) pcs/', $row['aksi'], $matches)) {
            $barang_ditarik[] = [
                'nama_barang' => trim($matches[1]),
                'kode_barcode' => trim($matches[2]),
                'waktu' => $row['waktu']
            ];
        }
    }

    // 3. Barang Dimasukan - dari log dengan format "Menambah stok"
    // Sekarang menggunakan format yang sama dengan SPG, jadi perlu filter berdasarkan username collecting
    $query_masukan = "SELECT aksi, waktu 
                      FROM log_aktivitas 
                      WHERE username = '$username' 
                        AND tabel = 'stok_toko'
                        AND DATE(waktu) = '$tanggal_hari_ini'
                        AND aksi LIKE 'Menambah stok $nama_toko:%'
                      ORDER BY waktu ASC";

    $result_masukan = mysqli_query($conn, $query_masukan);
    while ($row = mysqli_fetch_assoc($result_masukan)) {
        // Parse: "Menambah stok [Toko]: [Nama Barang] (Barcode: [Kode]) sebanyak 1 pcs"
        if (preg_match('/Menambah stok .+: (.+?) \(Barcode: (.+?)\) sebanyak (\d+) pcs/', $row['aksi'], $matches)) {
            $barang_dimasukan[] = [
                'nama_barang' => trim($matches[1]),
                'kode_barcode' => trim($matches[2]),
                'waktu' => $row['waktu']
            ];
        }
    }

    // Hitung total
    $total_terjual = count($barang_terjual);
    $total_ditarik = count($barang_ditarik);
    $total_dimasukan = count($barang_dimasukan);
    $total_pendapatan = $total_terjual * ($id_toko > 333 ? 15000 : 13000);

    // Simpan data untuk print dalam session
    $_SESSION['struk_collecting_data'] = [
        'toko' => [
            'nama' => $nama_toko,
            'alamat' => $alamat_toko
        ],
        'tanggal' => date('Y-m-d H:i:s'),
        'username' => $username,
        'barang_terjual' => $barang_terjual,
        'barang_ditarik' => $barang_ditarik,
        'barang_dimasukan' => $barang_dimasukan,
        'summary' => [
            'total_terjual' => $total_terjual,
            'total_ditarik' => $total_ditarik,
            'total_dimasukan' => $total_dimasukan,
            'total_pendapatan' => $total_pendapatan
        ]
    ];

    echo json_encode([
        'status' => 'success',
        'message' => 'Data struk berhasil disiapkan',
        'data' => [
            'total_terjual' => $total_terjual,
            'total_ditarik' => $total_ditarik,
            'total_dimasukan' => $total_dimasukan,
            'total_pendapatan' => $total_pendapatan
        ]
    ]);
} catch (Exception $e) {
    error_log("Error prepare struk collecting: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat menyiapkan struk: ' . $e->getMessage()
    ]);
}
