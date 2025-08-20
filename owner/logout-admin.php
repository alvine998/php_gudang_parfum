<?php
session_start();
session_unset();  // Menghapus semua variabel sesi
session_destroy();  // Mengakhiri sesi
setcookie('login_token', '', time() - 3600, '/');
// Redirect ke halaman login
header("Location: ../index.php");
exit;
