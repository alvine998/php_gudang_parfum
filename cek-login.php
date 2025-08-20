<?php
include 'koneksi.php';
// Set session cookie lifetime 1 hari (86400 detik)
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false, // true jika pakai HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
// Restore session dari cookie login_token jika belum ada session
if (!isset($_SESSION['username']) && isset($_COOKIE['login_token'])) {
    $token = base64_decode($_COOKIE['login_token']);
    if ($token && strpos($token, '|') !== false) {
        list($username, $role, $hash) = explode('|', $token);
        // Validasi ulang ke database
        $q = mysqli_query($conn, "SELECT * FROM users WHERE username='" . mysqli_real_escape_string($conn, $username) . "'");
        if ($row = mysqli_fetch_assoc($q)) {
            if ($role === $row['role'] && $hash === md5($row['password'])) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }
        }
    }
}

$username = $_POST['username'];
$password_input = $_POST['password'];

$query = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);

    if (password_verify($password_input, $data['password'])) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['role']     = $data['role'];

        // Set cookie login_token valid 1 hari
        $token = base64_encode($data['username'] . '|' . $data['role'] . '|' . md5($data['password']));
        setcookie('login_token', $token, time() + 86400, '/');

        if ($data['role'] == "admin") {
            header("Location: admin/dashboard-admin.php");
        } else if ($data['role'] == "spg") {
            header("Location: spg/dashboard-spg.php");
        } else if ($data['role'] == "collecting") {
            header("Location: collecting/dashboard-collecting.php");
        } else if ($data['role'] == "gudang") {
            header("Location: gudang/dashboard-admin.php");
        } else if ($data['role'] == "owner") {
            header("Location: owner/dashboard-admin.php");
        }
        exit;
    }
}

echo "<script>alert('Login gagal. Cek username atau password!'); window.location='index.php';</script>";
