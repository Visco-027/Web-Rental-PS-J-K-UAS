-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Jun 2025 pada 12.32
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `playstation_jk1`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_booking`
--

CREATE TABLE `tb_booking` (
  `id_booking` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `durasi_sewa` int(11) NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','canceled') DEFAULT 'pending',
  `tgl_booking` datetime DEFAULT current_timestamp(),
  `sewa_ps3` tinyint(1) DEFAULT 0,
  `sewa_tv32` tinyint(1) DEFAULT 0,
  `tipe_transaksi` varchar(20) DEFAULT NULL,
  `opsi_sewa` varchar(50) DEFAULT NULL,
  `total_harga` int(11) DEFAULT 0,
  `tgl_selesai` datetime DEFAULT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `id_chanel` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_booking`
--

INSERT INTO `tb_booking` (`id_booking`, `id_user`, `durasi_sewa`, `bukti_transfer`, `status`, `tgl_booking`, `sewa_ps3`, `sewa_tv32`, `tipe_transaksi`, `opsi_sewa`, `total_harga`, `tgl_selesai`, `waktu_mulai`, `id_chanel`) VALUES
(21, 8, 1, NULL, 'canceled', '2025-06-07 07:21:11', 0, 0, 'main_di_tempat', 'PS NO 1', 5000, NULL, '2025-06-07 13:00:00', 1),
(22, 8, 1, NULL, 'canceled', '2025-06-08 18:18:51', 0, 0, 'main_di_tempat', 'PS NO 3', 5000, NULL, '2025-06-09 00:00:00', 3),
(23, 8, 2, NULL, 'canceled', '2025-06-08 18:56:37', 0, 0, 'main_di_tempat', 'PS NO 1', 10000, NULL, '2025-06-09 00:00:00', 1),
(24, 8, 2, '1749403430_WhatsApp Image 2024-04-28 at 13.42.29_139393e9.jpg', 'confirmed', '2025-06-08 19:23:30', 0, 0, 'main_di_tempat', 'PS NO 12', 10000, NULL, '2025-06-09 00:33:16', 12),
(25, 8, 12, '1749427856_WhatsApp Image 2024-04-28 at 13.42.29_139393e9.jpg', 'canceled', '2025-06-09 07:10:44', 1, 0, 'takeaway', 'PS3', 30000, NULL, NULL, NULL),
(26, 8, 1, NULL, 'confirmed', '2025-06-09 05:00:29', 0, 0, 'main_di_tempat', 'PS NO 1', 5000, NULL, '2025-06-09 10:10:59', 1),
(27, 8, 1, NULL, 'canceled', '2025-06-09 16:36:11', 0, 0, 'main_di_tempat', 'PS NO 2', 5000, NULL, '2025-06-09 23:18:00', 2),
(28, 8, 1, '1749486222_WhatsApp Image 2024-04-28 at 13.42.29_139393e9.jpg', 'confirmed', '2025-06-09 18:23:05', 0, 0, 'main_di_tempat', 'PS NO 2', 5000, NULL, '2025-06-09 23:47:13', 2),
(29, 8, 1, '1749487600_WhatsApp Image 2024-04-28 at 13.42.29_139393e9.jpg', 'confirmed', '2025-06-09 18:46:15', 0, 0, 'main_di_tempat', 'PS NO 4', 5000, NULL, '2025-06-09 23:47:10', 4),
(30, 8, 12, NULL, 'canceled', '2025-06-11 10:20:55', 1, 0, 'takeaway', 'PS3', 30000, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_chanel`
--

CREATE TABLE `tb_chanel` (
  `id_chanel` int(11) NOT NULL,
  `nama_chanel` varchar(200) NOT NULL,
  `jenis_ps` varchar(10) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'unavailable',
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_chanel`
--

INSERT INTO `tb_chanel` (`id_chanel`, `nama_chanel`, `jenis_ps`, `status`, `last_updated`) VALUES
(1, 'PS NO 1', 'PS3', 'available', '2025-06-06 15:00:00'),
(2, 'PS NO 2', 'PS3', 'available', '2025-06-06 15:00:00'),
(3, 'PS NO 3', 'PS3', 'available', '2025-06-06 15:00:00'),
(4, 'PS NO 4', 'PS3', 'available', '2025-06-06 15:00:00'),
(5, 'PS NO 5', 'PS3', 'available', '2025-06-06 15:00:00'),
(6, 'PS NO 6', 'PS3', 'available', '2025-06-06 15:00:00'),
(7, 'PS NO 7', 'PS3', 'available', '2025-06-06 15:00:00'),
(8, 'PS NO 8', 'PS3', 'available', '2025-06-06 15:00:00'),
(9, 'PS NO 9', 'PS3', 'available', '2025-06-06 15:00:00'),
(10, 'PS NO 10', 'PS3', 'available', '2025-06-06 15:00:00'),
(11, 'PS NO 11', 'PS3', 'available', '2025-06-06 15:00:00'),
(12, 'PS NO 12', 'PS3', 'available', '2025-06-06 15:00:00'),
(13, 'PS NO 13', 'PS3', 'available', '2025-06-06 15:00:00'),
(14, 'PS NO 14', 'PS3', 'available', '2025-06-06 15:00:00'),
(15, 'PS NO 15', 'PS4', 'available', '2025-06-06 15:00:00'),
(16, 'PS NO 16', 'PS4', 'available', '2025-06-06 15:00:00'),
(17, 'PS NO 17', 'PS4', 'available', '2025-06-06 15:00:00'),
(18, 'PS NO 18', 'PS4', 'available', '2025-06-06 15:00:00'),
(19, 'PS NO 19', 'PS4', 'available', '2025-06-06 15:00:00'),
(20, 'PS NO 20', 'PS4', 'available', '2025-06-06 15:00:00'),
(21, 'PS NO 21', 'PS4', 'available', '2025-06-06 15:00:00'),
(22, 'PS NO 22', 'PS4', 'available', '2025-06-06 15:00:00'),
(23, 'PS NO 23', 'PS4', 'available', '2025-06-06 15:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_game`
--

CREATE TABLE `tb_game` (
  `id` int(11) NOT NULL,
  `nama_game` varchar(100) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `cover` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_game`
--

INSERT INTO `tb_game` (`id`, `nama_game`, `genre`, `cover`) VALUES
(19, 'EA Sports FC 25', 'Sports', 'ea_sports_fc_25.jpg'),
(20, 'Need for Speed Unbound: Ultimate Collection', 'Racing', 'nfs_unbound_ultimate.jpg'),
(21, 'Naruto X Boruto: Ultimate Ninja Storm Connections', 'Fighting', 'naruto_boruto_connections.jpg'),
(22, 'WWE 2K25', 'Fighting, Wrestling', 'wwe_2k25.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_harga`
--

CREATE TABLE `tb_harga` (
  `id_harga` int(11) NOT NULL,
  `menit` int(11) NOT NULL,
  `harga` int(11) NOT NULL,
  `jenis_ps` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_harga`
--

INSERT INTO `tb_harga` (`id_harga`, `menit`, `harga`, `jenis_ps`) VALUES
(2, 60, 5000, 'PS3'),
(3, 60, 8000, 'PS4');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_takeaway_inventory`
--

CREATE TABLE `tb_takeaway_inventory` (
  `id_inventory` int(11) NOT NULL,
  `jenis_item` enum('PS3','TV32') NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `status` enum('available','unavailable') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_takeaway_inventory`
--

INSERT INTO `tb_takeaway_inventory` (`id_inventory`, `jenis_item`, `stok`, `status`) VALUES
(1, 'PS3', 7, 'available'),
(2, 'TV32', 6, 'available');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `username` varchar(200) NOT NULL,
  `password` varchar(350) NOT NULL,
  `level` enum('admin','konsumen') NOT NULL DEFAULT 'konsumen',
  `nomor_telepon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nama_lengkap`, `username`, `password`, `level`, `nomor_telepon`) VALUES
(8, 'Apel', 'apel', 'apel', 'konsumen', '081234567890'),
(9, 'Asep', 'asep', 'asep', 'konsumen', '081234567891'),
(10, 'Administrator', 'admin', 'admin', 'admin', '081234567892'),
(12, 'ayam', 'ayam', 'ayam', 'konsumen', NULL),
(13, 'ayam', 'ayam', 'ayam', 'konsumen', '085672739475'),
(14, 'ayam', 'ayam', 'ayam', 'konsumen', '0857685732');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_booking`
--
ALTER TABLE `tb_booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_chanel` (`id_chanel`);

--
-- Indeks untuk tabel `tb_chanel`
--
ALTER TABLE `tb_chanel`
  ADD PRIMARY KEY (`id_chanel`);

--
-- Indeks untuk tabel `tb_game`
--
ALTER TABLE `tb_game`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_harga`
--
ALTER TABLE `tb_harga`
  ADD PRIMARY KEY (`id_harga`);

--
-- Indeks untuk tabel `tb_takeaway_inventory`
--
ALTER TABLE `tb_takeaway_inventory`
  ADD PRIMARY KEY (`id_inventory`);

--
-- Indeks untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_booking`
--
ALTER TABLE `tb_booking`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `tb_chanel`
--
ALTER TABLE `tb_chanel`
  MODIFY `id_chanel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `tb_game`
--
ALTER TABLE `tb_game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `tb_harga`
--
ALTER TABLE `tb_harga`
  MODIFY `id_harga` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_takeaway_inventory`
--
ALTER TABLE `tb_takeaway_inventory`
  MODIFY `id_inventory` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_booking`
--
ALTER TABLE `tb_booking`
  ADD CONSTRAINT `tb_booking_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`),
  ADD CONSTRAINT `tb_booking_ibfk_2` FOREIGN KEY (`id_chanel`) REFERENCES `tb_chanel` (`id_chanel`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
