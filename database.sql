-- =====================================================
-- Lab-Ventory | Database Schema
-- Amanda Damayanti_202432027
-- Laporan 4 - Pemrograman Web (PEMWEB)
-- =====================================================

CREATE DATABASE IF NOT EXISTS lab_ventory;
USE lab_ventory;

-- Tabel Users: menyimpan data login dan role (admin/user)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
);

-- Tabel Barang: master data inventaris lab
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    lokasi_lab VARCHAR(100) NOT NULL
);

-- Tabel Peminjaman: transaksi peminjaman (relasi user <-> barang)
CREATE TABLE IF NOT EXISTS peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    barang_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    status ENUM('pending', 'approved', 'ditolak', 'dikembalikan') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
);

-- =====================================================
-- Data Awal (Seeder)
-- Password asli untuk kedua akun: "password123"
-- (di-hash dengan password_hash() PHP / bcrypt)
-- =====================================================

INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Joshua', 'admin'),
('hanif', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hanif', 'user');

INSERT INTO barang (nama_barang, stok, lokasi_lab) VALUES
('Switch Hub 8 Port', 3, 'Software Engineering'),
('Monitor NOC', 15, 'Intelligent Computing'),
('Keyboard Logitech', 13, 'Software Engineering');
