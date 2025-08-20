<?php
// Endpoint: get-user-by-role.php?role=spg atau collecting
include '../koneksi.php';
header('Content-Type: application/json');
$role = isset($_GET['role']) ? $_GET['role'] : '';
if ($role !== 'spg' && $role !== 'collecting') {
    echo json_encode(['status' => 'error', 'message' => 'Role tidak valid', 'data' => []]);
    exit;
}
$sql = "SELECT username FROM users WHERE role = ? ORDER BY username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $role);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = [
        'username' => $row['username'],

    ];
}
echo json_encode(['status' => 'success', 'data' => $data]);
