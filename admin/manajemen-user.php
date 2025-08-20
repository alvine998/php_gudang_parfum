<?php
include '../koneksi.php';
include 'header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

$data = mysqli_query($conn, "SELECT * FROM users ORDER BY username ASC");
?>

<h2 style="text-align:center;">Manajemen User</h2>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <script>alert("User berhasil ditambahkan!");</script>
<?php elseif (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
    <script>alert("User berhasil dihapus!");</script>
<?php endif; ?>

<!-- FORM TAMBAH USER -->
<div style="max-width: 500px; margin: 20px auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px;">
    <form method="POST" action="proses-user.php">
        <input type="hidden" name="aksi" value="tambah">

        <label>Username:</label><br>
        <input type="text" name="username" required style="width: 100%; padding: 8px;"><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required style="width: 100%; padding: 8px;"><br><br>

        <label>Role:</label><br>
        <select name="role" required style="width: 100%; padding: 8px;">
            <option value="">-- Pilih Role --</option>
            <option value="admin">Admin</option>
            <option value="spg">SPG</option>
            <option value="collecting">Collecting</option>
            <option value="gudang">Gudang</option>
            <option value="owner">Owner</option>
        </select><br><br>

        <button type="submit" style="background-color: green; color: white; padding: 10px 20px;">Tambah User</button>
        <button type="reset" style="padding: 10px 20px; margin-left: 10px;">Reset</button>
    </form>
</div>

<!-- SEARCH BAR -->
<div style="width: 80%; margin: 0 auto;">
    <input type="text" id="searchInput" placeholder="Cari Username..." style="width: 100%; padding: 8px; margin-bottom: 10px;">
</div>

<!-- TABEL USER -->
<div style="width: 90%; margin: 0 auto;">
    <h3 style="text-align:center;">Daftar User</h3>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse; text-align:center;" id="userTable">
        <thead style="background-color: black; color: white;">
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($u = mysqli_fetch_assoc($data)) : ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td>
                        <a href="edit-user.php?id=<?= $u['id'] ?>" style="color: blue;">Edit</a> |
                        <a href="proses-user.php?aksi=hapus&id=<?= $u['id'] ?>" style="color: red;" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 10px;">
        <button onclick="changePage(-1)">Prev</button>
        <span id="pageInfo" style="margin: 0 10px;"></span>
        <button onclick="changePage(1)">Next</button>
    </div>
</div>

<script>
// SEARCH
document.getElementById("searchInput").addEventListener("keyup", function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#userTable tbody tr");
    rows.forEach(row => {
        const text = row.cells[0].innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
    currentPage = 1;
    showPage(currentPage);
});

// PAGINATION
let currentPage = 1;
const rowsPerPage = 5;
const rows = document.querySelectorAll("#userTable tbody tr");

function showPage(page) {
    const totalRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(totalRows.length / rowsPerPage);
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentPage = page;

    let visibleIndex = 0;
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            visibleIndex++;
            row.style.display = (visibleIndex > (page - 1) * rowsPerPage && visibleIndex <= page * rowsPerPage) ? "" : "none";
        }
    });

    document.getElementById("pageInfo").innerText = `Halaman ${page} dari ${totalPages}`;
}

function changePage(delta) {
    showPage(currentPage + delta);
}

document.addEventListener("DOMContentLoaded", function () {
    showPage(currentPage);
});
</script>

<?php include 'footer.php'; ?>
