<?php
require_once '../koneksi.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT nama_toko FROM toko ORDER BY nama_toko");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = ['nama_toko' => $row['nama_toko']];
}
echo json_encode(['status' => 'success', 'data' => $data]);
