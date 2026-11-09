<?php
/* Amanda Damayanti_202432027 */

// =====================================================
// config.php
// Koneksi ke database MySQL menggunakan ekstensi PDO.
// PDO dipakai (bukan fungsi mysql_* lama) supaya koneksi
// stabil, mendukung prepared statement (anti SQL Injection),
// dan bisa ganti driver database tanpa ubah banyak kode.
// =====================================================

$host = "localhost";
$dbname = "lab_ventory";
$dbuser = "root";
$dbpass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    // Supaya error query langsung ketahuan (lempar exception), bukan diam-diam gagal
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Mulai session di semua halaman yang meng-include config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
