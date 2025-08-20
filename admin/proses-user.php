<?php
include '../koneksi.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

$aksi = $_POST['aksi'] ?? $_GET['aksi'];
$username_session = $_SESSION['username'];

if ($aksi == 'tambah') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Cek duplikat username
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location.href='manajemen-user.php';</script>";
        exit;
    }

    // Tambah user
    mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");

    // Simpan log
    $aksi_log = "Menambahkan user baru: $username ($role)";
    $waktu = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu)
                         VALUES ('$username_session', '$aksi_log', 'users', '$waktu')");

    header("Location: manajemen-user.php?status=success");
    exit;
}

if ($aksi == 'edit') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']); // catatan: pertimbangkan pakai password_hash juga
        mysqli_query($conn, "UPDATE users SET username='$username', password='$password', role='$role' WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE users SET username='$username', role='$role' WHERE id=$id");
    }

    // Simpan log
    $aksi_log = "Mengedit user: $username ($role)";
    $waktu = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu)
                         VALUES ('$username_session', '$aksi_log', 'users', '$waktu')");

    header("Location: manajemen-user.php");
    exit;
}

if ($aksi == 'hapus') {
    $id = $_GET['id'];
    $get = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, role FROM users WHERE id=$id"));
    $username_target = $get['username'];
    $role_target = $get['role'];

    // Simpan log
    $aksi_log = "Menghapus user: $username_target ($role_target)";
    $waktu = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO log_aktivitas (username, aksi, tabel, waktu)
                         VALUES ('$username_session', '$aksi_log', 'users', '$waktu')");

    mysqli_query($conn, "DELETE FROM users WHERE id=$id");

    header("Location: manajemen-user.php");
    exit;
}
?>
