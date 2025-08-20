<?php
require_once '../koneksi.php';

echo "Checking stok_gudang table structure...\n";

try {
    // Check if stok_gudang table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'stok_gudang'");
    if ($check_table->num_rows > 0) {
        echo "stok_gudang table exists\n";

        // Show structure
        $structure = $conn->query("DESCRIBE stok_gudang");
        echo "Table structure:\n";
        while ($col = $structure->fetch_assoc()) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }

        // Show sample data
        $sample = $conn->query("SELECT * FROM stok_gudang LIMIT 5");
        echo "\nSample data:\n";
        while ($row = $sample->fetch_assoc()) {
            echo "ID: " . $row['id'] . " | ";
            foreach ($row as $key => $value) {
                if ($key !== 'id') {
                    echo $key . ": " . $value . " | ";
                }
            }
            echo "\n";
        }
    } else {
        echo "stok_gudang table does NOT exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
