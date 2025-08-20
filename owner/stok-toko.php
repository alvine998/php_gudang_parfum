<?php
include '../koneksi.php';
include 'header.php';

if ($_SESSION['role'] != 'owner') {
    header("Location: ../index.php");
    exit;
}

$username = $_SESSION['username'];
$sort = ($_GET['sort'] ?? 'last_desc') === 'last_asc' ? 'last_asc' : 'last_desc';
$search = $_GET['search'] ?? '';
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

// Sorting logic
switch ($sort) {
    case 'last_asc':
        $order_sql = "ORDER BY last_update ASC";
        break;
    case 'last_desc':
    default:
        $order_sql = "ORDER BY last_update DESC";
        break;
}

// Build WHERE clause
$where_sql = "WHERE 1";
if ($search !== '') {
    $safe = mysqli_real_escape_string($conn, $search);
    $where_sql .= " AND toko.nama_toko LIKE '%$safe%'";
}
if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
    $where_sql .= " AND DATE(stok_toko.updated_at) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
} elseif ($tanggal_awal !== '') {
    $where_sql .= " AND DATE(stok_toko.updated_at) >= '$tanggal_awal'";
} elseif ($tanggal_akhir !== '') {
    $where_sql .= " AND DATE(stok_toko.updated_at) <= '$tanggal_akhir'";
}

// Hitung total data
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM (
        SELECT toko.id FROM toko
        LEFT JOIN stok_toko ON toko.id = stok_toko.id_toko
        $where_sql
        GROUP BY toko.id
    ) AS temp
");
$total_data = mysqli_fetch_assoc($count_query)['total'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_data / $limit);

// Ambil data stok
$stok_query = mysqli_query($conn, "
    SELECT toko.id, toko.nama_toko, 
           IFNULL(SUM(stok_toko.jumlah), 0) AS total_stok,
           MAX(stok_toko.updated_at) AS last_update
    FROM toko
    LEFT JOIN stok_toko ON toko.id = stok_toko.id_toko
    $where_sql
    GROUP BY toko.id
    $order_sql
    LIMIT $limit OFFSET $offset
");

$data_toko = [];
while ($row = mysqli_fetch_assoc($stok_query)) {
    $data_toko[] = $row;
}
?>

<h2 style="text-align: center; margin-bottom: 20px;">🏪 Daftar Stok Toko</h2>

<!-- Filter + Search + Sort -->
<div style="width: 95%; margin: 0 auto 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;">
    <form method="GET" id="searchForm" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex-grow: 1;">
        <input type="text" name="search" id="searchInput" placeholder="Cari Toko..." style="padding: 8px;" value="<?= htmlspecialchars($search) ?>">

        <label>Dari:</label>
        <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
        <label>Sampai:</label>
        <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">

        <label>Urutkan:</label>
        <select name="sort" style="padding: 5px;">
            <option value="last_desc" <?= $sort === 'last_desc' ? 'selected' : '' ?>>Terbaru</option>
            <option value="last_asc" <?= $sort === 'last_asc' ? 'selected' : '' ?>>Terlama</option>
        </select>

        <input type="hidden" name="page" value="1">
        <button type="submit">🔍</button>
    </form>
</div>

<!-- Tabel -->
<div class="table-wrapper" style="width: 95%; margin: 0 auto;">
  <table id="tokoTable">
    <thead style="background-color: black; color: white;">
      <tr>
        <th>Nama Toko</th>
        <th>Total Stok</th>
        <th>Last Update</th>
        <!-- <th>Aksi</th> -->
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data_toko as $toko): 
        $last_update = $toko['last_update'];
        $last_update_text = '-';
        $warnaclass = '';

        if ($last_update) {
            $last_time = strtotime($last_update);
            $now = time();
            $selisih_hari = floor(($now - $last_time) / (60 * 60 * 24));
            $last_update_text = date("d-m-Y H:i", $last_time);
            if ($selisih_hari >= 7) {
                $warnaclass = 'style="color:red;"';
            }
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($toko['nama_toko']) ?></td>
        <td>
          <a href="detail-stok-toko.php?id_toko=<?= $toko['id'] ?>">
            <?= $toko['total_stok'] ?> pcs
          </a>
        </td>
        <td <?= $warnaclass ?>><?= $last_update_text ?></td>
        <!-- <td>
          <a href="tambah-stok-toko.php?id_toko=<?= $toko['id'] ?>" title="Tambah Stok">
            <i style="color:blue;">➕</i>
          </a>
          &nbsp;
          <a href="kurang-stok-toko.php?id_toko=<?= $toko['id'] ?>" title="Kurang Stok">
            <i style="color:orange;">➖</i>
          </a>
        </td> -->
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div style="text-align: center; margin-top: 20px;">
    <?php
    $params = $_GET;
    if ($page > 1) {
        $params['page'] = $page - 1;
        echo "<a href='?" . http_build_query($params) . "' style='margin-right:10px;'>Prev</a>";
    } else {
        echo "<span style='color: grey; margin-right:10px;'>Prev</span>";
    }

    echo "Halaman $page dari $total_pages";

    if ($page < $total_pages) {
        $params['page'] = $page + 1;
        echo "<a href='?" . http_build_query($params) . "' style='margin-left:10px;'>Next</a>";
    } else {
        echo "<span style='color: grey; margin-left:10px;'>Next</span>";
    }
    ?>
</div>

<!-- Auto Search Timer -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchInput");
    const form = document.getElementById("searchForm");

    input.focus();
    input.addEventListener("input", function () {
        clearTimeout(input.timer);
        input.timer = setTimeout(() => {
            form.submit();
        }, 400); // debounce 400ms
    });
});
</script>

<?php include 'footer.php'; ?>
