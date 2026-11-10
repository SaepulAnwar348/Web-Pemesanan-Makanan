-- Database: zifood
CREATE DATABASE IF NOT EXISTS `zifood`;
USE `zifood`;

-- 1. Tabel Admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Users (Pelanggan)
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(150) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `alamat` TEXT NULL,
  `no_hp` VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Menu
CREATE TABLE IF NOT EXISTS `menu` (
  `id_menu` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_menu` VARCHAR(150) NOT NULL,
  `kategori` VARCHAR(100) NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deskripsi` TEXT NULL,
  `foto` VARCHAR(255) NULL,
  `rating_rata` DECIMAL(3,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Pesanan
CREATE TABLE IF NOT EXISTS `pesanan` (
  `id_pesanan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `id_menu` INT NULL,
  `jumlah` INT DEFAULT 1,
  `alamat` TEXT NULL,
  `catatan` TEXT NULL,
  `metode` VARCHAR(100) DEFAULT 'Bayar di Kasir',
  `ongkir` INT DEFAULT 0,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Menunggu','Dikonfirmasi','Ditolak','Selesai') DEFAULT 'Menunggu',
  `meja` VARCHAR(50) DEFAULT '0',
  `tanggal` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Notifikasi
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id_notifikasi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `id_pesanan` INT NOT NULL,
  `pesan` TEXT NOT NULL,
  `status_baca` ENUM('Belum','Sudah') DEFAULT 'Belum',
  `waktu` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Rating
CREATE TABLE IF NOT EXISTS `rating` (
  `id_rating` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `id_menu` INT NOT NULL,
  `nilai` INT NOT NULL,
  `waktu` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DATA AWAL (DEFAULT SEED)
-- ==========================================

-- Akun Admin Default (Password minimal 8 karakter sesuai validasi)
-- Username: admin
-- Password: admin123
INSERT INTO `admin` (`id_admin`, `username`, `password`) 
VALUES (1, 'admin', 'admin123')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Akun User Default
-- Username: user1
-- Password: user1234
INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `alamat`, `no_hp`) 
VALUES (1, 'User Pelanggan', 'user1', 'user1234', 'Jl. Merdeka No. 12, Bandung', '081234567890')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Menu Contoh
INSERT INTO `menu` (`id_menu`, `nama_menu`, `kategori`, `harga`, `deskripsi`, `foto`, `rating_rata`) VALUES
(1, 'Mie Ayam Noodle', 'Makanan', 15000.00, 'Mie ayam lezat dengan bumbu khas dan pangsit renyah.', '1763382412_Mie ayam ??__ noodle.jpg', 4.8),
(2, 'Nasi Goreng Spesial', 'Makanan', 20000.00, 'Nasi goreng dengan telur, sosis, dan bumbu rempah pilihan.', '1763382431_Nasi Goreng (Indonesian Fried Rice).jpg', 4.7),
(3, 'Es Jeruk Segar', 'Minuman', 6000.00, 'Perasan jeruk asli yang manis dan menyegarkan.', '1763382378_Es jeruk.jpg', 4.5),
(4, 'Tiramisu Dessert', 'Dessert', 25000.00, 'Dessert manis beraroma kopi lembut.', '1763551144_Tiramisu.jpg', 4.9)
ON DUPLICATE KEY UPDATE `id_menu`=`id_menu`;

