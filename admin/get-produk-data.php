<?php
// Suppress notices and warnings that could break JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

session_start();
include '../koneksi.php';

// Cek akses admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Clear any output buffer and set headers
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Cek apakah ini request export
    $isExport = isset($_GET['export']) && $_GET['export'] == '1';

    // Parameter filtering
    $page = (int)($_GET['page'] ?? 1);
    $limit = $isExport ? 10000 : 10; // Jika export, ambil banyak data
    $offset = ($page - 1) * $limit;

    $filterStatus = $_GET['status'] ?? '';
    $filterNama = $_GET['nama'] ?? '';
    $filterTanggal = $_GET['tanggal'] ?? '';
    $filterUsername = $_GET['username'] ?? '';

    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    $types = '';

    if (!empty($filterStatus)) {
        if ($filterStatus === 'pending') {
            // Untuk pending, cari yang status kosong atau NULL
            $whereConditions[] = "(status = '' OR status IS NULL)";
        } elseif (in_array($filterStatus, ['di_spg', 'di_collecting'])) {
            // Jika ada filter username, status harus persis: di_spg - username
            if (!empty($filterUsername)) {
                $whereConditions[] = "status = ?";
                $params[] = $filterStatus . ' - ' . $filterUsername;
                $types .= 's';
            } else {
                $whereConditions[] = "status LIKE ?";
                $params[] = "$filterStatus%";
                $types .= 's';
            }
        } elseif ($filterStatus === 'di_toko') {
            $whereConditions[] = "status LIKE ?";
            $params[] = "$filterStatus%";
            $types .= 's';
        } else {
            // Untuk status lainnya, gunakan exact match
            $whereConditions[] = "status = ?";
            $params[] = $filterStatus;
            $types .= 's';
        }
    }

    if (!empty($filterNama)) {
        $whereConditions[] = "(nama_barang LIKE ? OR kode_barcode LIKE ?)";
        $params[] = "%$filterNama%";
        $params[] = "%$filterNama%";
        $types .= 'ss';
    }
    // Filter nama toko jika ada
    if (!empty($_GET['toko'])) {
        $whereConditions[] = "status = ?";
        $params[] = "di_toko - " . $_GET['toko'];
        $types .= 's';
    }

    if (!empty($filterTanggal)) {
        $whereConditions[] = "DATE(updated_at) = ?";
        $params[] = $filterTanggal;
        $types .= 's';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM barcode_produk $whereClause";
    if (!empty($params)) {
        $countStmt = mysqli_prepare($conn, $countQuery);
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
    } else {
        $countResult = mysqli_query($conn, $countQuery);
    }

    $totalRecords = mysqli_fetch_assoc($countResult)['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get paginated data
    $dataQuery = "SELECT * FROM barcode_produk $whereClause ORDER BY updated_at DESC LIMIT ? OFFSET ?";
    $finalParams = array_merge($params, [$limit, $offset]);
    $finalTypes = $types . 'ii';

    $dataStmt = mysqli_prepare($conn, $dataQuery);
    mysqli_stmt_bind_param($dataStmt, $finalTypes, ...$finalParams);
    mysqli_stmt_execute($dataStmt);
    $dataResult = mysqli_stmt_get_result($dataStmt);

    $products = [];
    while ($row = mysqli_fetch_assoc($dataResult)) {
        $products[] = $row;
    }

    // Jika ini request export, return CSV
    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="daftar_produk_' . date('Y-m-d_H-i-s') . '.csv"');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "No,Nama Barang,Kode Barcode,Status,Tanggal Update\n";

        foreach ($products as $index => $product) {
            echo ($index + 1) . ",";
            echo '"' . str_replace('"', '""', $product['nama_barang']) . '",';
            echo '"' . $product['kode_barcode'] . '",';
            echo '"' . ucfirst($product['status']) . '",';
            echo '"' . date('d/m/Y H:i', strtotime($product['updated_at'])) . '"';
            echo "\n";
        }
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get-produk-data.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat mengambil data produk: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
