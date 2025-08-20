<?php
include '../koneksi.php';
include 'header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
?>

<h2 style="text-align:center;">Edit User</h2>

<div style="max-width: 500px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px;">
    <form method="POST" action="proses-user.php">
        <input type="hidden" name="aksi" value="edit">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Username:</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($data['username']) ?>" required style="width: 100%; padding: 8px;"><br><br>

        <label>Password (biarkan kosong jika tidak diubah):</label><br>
        <input type="password" name="password" style="width: 100%; padding: 8px;"><br><br>

        <label>Role:</label><br>
        <select name="role" required style="width: 100%; padding: 8px;">
            <option value="admin" <?= $data['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="spg" <?= $data['role'] == 'spg' ? 'selected' : '' ?>>SPG</option>
            <option value="collecting" <?= $data['role'] == 'collecting' ? 'selected' : '' ?>>Collecting</option>
        </select><br><br>

        <div style="text-align:center;">
            <button type="submit" style="background-color: orange; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Simpan Perubahan</button>
            <a href="manajemen-user.php" style="margin-left: 10px; text-decoration: none;">Batal</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
