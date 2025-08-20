<?php
include 'koneksi.php';
echo "=== SAMPLE LOG AKTIVITAS ===\n";
$q = mysqli_query($conn, "SELECT username, aksi, waktu FROM log_aktivitas WHERE aksi LIKE '%tarik%' OR aksi LIKE '%masukan%' OR aksi LIKE '%Update stok%' ORDER BY waktu DESC LIMIT 15");
while ($row = mysqli_fetch_assoc($q)) {
    echo "User: " . $row['username'] . "\n";
    echo "Aksi: " . $row['aksi'] . "\n";
    echo "Waktu: " . $row['waktu'] . "\n";
    echo "---\n";
}
