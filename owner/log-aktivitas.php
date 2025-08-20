<?php
include 'header.php';
include '../koneksi.php';

// Filter
$where = [];
if (!empty($_GET['tanggal_dari']) && !empty($_GET['tanggal_sampai'])) {
    $dari = $_GET['tanggal_dari'];
    $sampai = $_GET['tanggal_sampai'];
    $where[] = "waktu BETWEEN '$dari 00:00:00' AND '$sampai 23:59:59'";
}
if (!empty($_GET['username'])) {
    $username = $_GET['username'];
    $where[] = "username = '$username'";
}
if (!empty($_GET['tabel'])) {
    $tabel = $_GET['tabel'];
    $where[] = "tabel = '$tabel'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM log_aktivitas $where_sql");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = mysqli_query($conn, "SELECT * FROM log_aktivitas $where_sql ORDER BY waktu DESC LIMIT $offset, $limit");

// Get filter options
$user_list = mysqli_query($conn, "SELECT DISTINCT username FROM log_aktivitas");
$table_list = mysqli_query($conn, "SELECT DISTINCT tabel FROM log_aktivitas");
?>

<h2 style="text-align: center;">Log Aktivitas Sistem</h2>

<!-- FILTER -->
<form method="GET" style="text-align: center; margin-bottom: 20px;">
    <input type="date" name="tanggal_dari" value="<?= htmlspecialchars($_GET['tanggal_dari'] ?? '') ?>">
    <input type="date" name="tanggal_sampai" value="<?= htmlspecialchars($_GET['tanggal_sampai'] ?? '') ?>">

    <select name="username">
        <option value="">-- Username --</option>
        <?php while ($u = mysqli_fetch_assoc($user_list)): ?>
            <option value="<?= $u['username']; ?>" <?= ($_GET['username'] ?? '') == $u['username'] ? 'selected' : '' ?>>
                <?= $u['username']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="tabel">
        <option value="">-- Tabel --</option>
        <?php while ($t = mysqli_fetch_assoc($table_list)): ?>
            <option value="<?= $t['tabel']; ?>" <?= ($_GET['tabel'] ?? '') == $t['tabel'] ? 'selected' : '' ?>>
                <?= $t['tabel']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Filter</button>
</form>

<!-- TABEL -->
<div style="overflow-x: auto; margin: 0 auto; width: 95%;">
    <table border="1" cellpadding="8" cellspacing="0" style="margin: 0 auto; min-width: 900px;">
        <thead style="background-color: black; color: white;">
            <tr>
                <th>Waktu</th>
                <th>Username</th>
                <th>Aksi</th>
                <th>Tabel</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($data = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $data['waktu']; ?></td>
                    <td><?= htmlspecialchars($data['username']); ?></td>
                    <td><?= htmlspecialchars($data['aksi']); ?></td>
                    <td><?= htmlspecialchars($data['tabel']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- PAGINATION MIRIP DAFTAR TOKO -->
<div style="text-align: center; margin: 20px;">
    <?php
    $params = $_GET;

    // Tombol Prev
    if ($page > 1) {
        $params['page'] = $page - 1;
        echo "<a href='?" . http_build_query($params) . "' style='margin-right:10px;'>Prev</a>";
    } else {
        echo "<span style='color: grey; margin-right:10px;'>Prev</span>";
    }

    // Info halaman
    echo "Halaman $page dari $total_pages";

    // Tombol Next
    if ($page < $total_pages) {
        $params['page'] = $page + 1;
        echo "<a href='?" . http_build_query($params) . "' style='margin-left:10px;'>Next</a>";
    } else {
        echo "<span style='color: grey; margin-left:10px;'>Next</span>";
    }
    ?>
</div>



<?php include 'footer.php'; ?>
