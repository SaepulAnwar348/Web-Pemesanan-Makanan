<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'zifood';

// Matikan exception bawaan agar koneksi bisa ditangani dan dibuat otomatis jika database belum ada
mysqli_report(MYSQLI_REPORT_OFF);

// Coba hubungkan langsung ke database zifood
$conn = @mysqli_connect($host, $user, $pass, $db);

// Jika database belum ada, buat otomatis
if (!$conn) {
    // Hubungkan ke MySQL Server
    $server_conn = @mysqli_connect($host, $user, $pass);

    if (!$server_conn) {
        die('Koneksi ke MySQL gagal! Pastikan MySQL di Laragon sudah di-Start. Error: ' . mysqli_connect_error());
    }

    // 1. Buat database zifood
    mysqli_query($server_conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    mysqli_select_db($server_conn, $db);
    $conn = $server_conn;

    // 2. Jalankan skrip SQL untuk membuat semua tabel dan data default secara otomatis
    $sqlFile = __DIR__ . '/database_zifood.sql';
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        if (mysqli_multi_query($conn, $sqlContent)) {
            do {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        }
    }
}

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
} else {
    // Pastikan akun admin & user default tersedia jika belum ada
    @mysqli_query($conn, "INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES (1, 'admin', 'admin123') ON DUPLICATE KEY UPDATE `id_admin`=`id_admin`");
    @mysqli_query($conn, "INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `alamat`, `no_hp`) VALUES (1, 'User Pelanggan', 'user1', 'user1234', 'Jl. Merdeka No. 12', '081234567890') ON DUPLICATE KEY UPDATE `id_user`=`id_user`");
}
?>
