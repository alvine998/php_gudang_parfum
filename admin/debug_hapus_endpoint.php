<?php
// Debug endpoint untuk cek output hapus-produk.php
header('Content-Type: text/plain');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/appstokNew/admin/hapus-produk.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'id' => isset($_GET['id']) ? $_GET['id'] : '',
    'kode_barcode' => isset($_GET['kode_barcode']) ? $_GET['kode_barcode'] : ''
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);
echo "HTTP CODE: " . $info['http_code'] . "\n";
echo "RESPONSE:\n" . $response . "\n";
echo "CURL ERROR: " . $error;
