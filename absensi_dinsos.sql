-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Des 2025 pada 09.46
-- Versi server: 10.4.24-MariaDB
-- Versi PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_dinsos`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `ijin`
--

CREATE TABLE `ijin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') DEFAULT NULL,
  `keterangan_admin` text DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `ijin`
--

INSERT INTO `ijin` (`id`, `user_id`, `tanggal`, `keterangan`, `status`, `keterangan_admin`, `validated_by`, `validated_at`, `created_at`) VALUES
(31, 23, '2025-12-25', 'aku sakitt', 'ditolak', 'kamu kebanyakan ijin', 2, '2025-12-25 19:32:05', '2025-12-25 19:31:39'),
(33, 24, '2025-12-25', 'sakit kepala', 'disetujui', 'ndang mari', 2, '2025-12-25 20:19:01', '2025-12-25 20:18:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi`
--

CREATE TABLE `presensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `keterangan` enum('hadir','ijin','alpha') COLLATE utf8mb4_unicode_ci NOT NULL,
  `waktu` datetime NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `lokasi` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `presensi`
--

INSERT INTO `presensi` (`id`, `user_id`, `keterangan`, `waktu`, `latitude`, `longitude`, `lokasi`) VALUES
(85, 22, 'hadir', '2025-12-25 19:10:19', '-7.42427820', '109.23963660', 'SD Negeri 1-2-3-9 Kranji, Jalan Adyaksa, Jatiwinangun, Kranji, Banyumas, Jawa Tengah, Jawa, 53116, Indonesia'),
(86, 23, 'alpha', '2025-12-25 19:30:00', NULL, NULL, 'Auto Alpha - Tidak Presensi'),
(88, 24, 'ijin', '2025-12-25 20:19:01', NULL, NULL, 'Ijin Disetujui - sakit kepala');

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_code`
--

CREATE TABLE `qr_code` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `token` varchar(5) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `qr_code`
--

INSERT INTO `qr_code` (`id`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `token`, `status`, `created_at`) VALUES
(1, '2025-12-18', '07:00:00', '15:00:00', '3KNWU', 'nonaktif', '2025-12-18 14:40:06'),
(2, '2025-12-19', '07:00:00', '14:30:00', '581TL', 'nonaktif', '2025-12-19 14:14:10'),
(4, '2025-12-25', '07:00:00', '19:30:00', 'W9F82', 'nonaktif', '2025-12-25 19:09:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_code_backup`
--

CREATE TABLE `qr_code_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `token` varchar(255) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `qr_code_backup`
--

INSERT INTO `qr_code_backup` (`id`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `token`, `status`, `created_at`) VALUES
(5, '2025-12-07', '07:00:00', '10:00:00', 'f2b1cd02077492c440977549a195860b8e286d510561b8537bc6e3e6a5c7e45a', 'nonaktif', '2025-12-07 10:33:27'),
(6, '2025-12-07', '07:00:00', '11:00:00', '54d43ee9f51f2c79aee7e0407c5a54b422389213e757c15edd0f74f78a3da946', 'nonaktif', '2025-12-07 10:33:47'),
(8, '2025-12-02', '07:00:00', '10:00:00', 'token002bbccddee1122', 'nonaktif', '2025-12-02 06:55:00'),
(9, '2025-12-03', '07:00:00', '10:00:00', 'token003ccddeeff2233', 'nonaktif', '2025-12-03 06:55:00'),
(10, '2025-12-04', '07:00:00', '10:00:00', 'token004ddeeff3344', 'nonaktif', '2025-12-04 06:55:00'),
(11, '2025-12-05', '07:00:00', '10:00:00', 'token005eeff334455', 'nonaktif', '2025-12-05 06:55:00'),
(12, '2025-12-06', '07:00:00', '10:00:00', 'token006ff11223344aa', 'nonaktif', '2025-12-06 06:55:00'),
(13, '2025-12-07', '07:00:00', '10:00:00', 'token007aa11224455bb', 'nonaktif', '2025-12-07 06:55:00'),
(14, '2025-12-08', '07:00:00', '10:00:00', 'token008cc228899aa33', 'nonaktif', '2025-12-08 06:55:00'),
(15, '2025-12-09', '07:00:00', '10:00:00', 'token009dd33557722cc', 'nonaktif', '2025-12-09 06:55:00'),
(16, '2025-12-10', '07:00:00', '10:00:00', 'token010ee44778866dd', 'nonaktif', '2025-12-10 06:55:00'),
(17, '2025-12-07', '07:00:00', '14:00:00', '1a7ca3f349e005af463d9e96b82ffb59b51e3b00d03a5e5af8e0d00054b5e302', 'nonaktif', '2025-12-07 13:12:08'),
(18, '2025-12-07', '07:00:00', '17:00:00', '2d02a3484b58cf102a7009681515893abafc792eed411885a4bddf4c85627959', 'nonaktif', '2025-12-07 13:12:19'),
(19, '2025-12-07', '07:00:00', '17:00:00', '5f5fbbd2abbd612dd2d188dbf32fdf5a6eb251d19a1c706a0255ac991363b6c2', 'nonaktif', '2025-12-07 13:12:36'),
(20, '2025-12-07', '07:00:00', '17:00:00', '0c9dcc063ad66ae3b5b61173c8ad1d5e2bba0ac6a0e14060caf73e8b082ff05e', 'nonaktif', '2025-12-07 17:10:09'),
(21, '2025-12-07', '07:00:00', '18:00:00', 'd541b51ee54c5f6aae69ce8c03300b8d9e60559376675b9a92c124438a492148', 'nonaktif', '2025-12-07 17:10:51'),
(22, '2025-12-07', '07:00:00', '17:00:00', 'a5cde70d968711c887fefdd21c1a1eb4b2d9940044ffb40760e29c730bd2600b', 'nonaktif', '2025-12-07 18:11:03'),
(23, '2025-12-07', '07:00:00', '21:00:00', '153d02bdd49576349b31de2d733ffbca281b6bfdde520d31e7011eb5a6aeee96', 'nonaktif', '2025-12-07 20:04:45'),
(24, '2025-12-07', '07:00:00', '20:10:00', 'a41e40bd1d25c100929760ab4d300a6f904cba46862f7586b598842d709b6729', 'nonaktif', '2025-12-07 20:08:46'),
(25, '2025-12-11', '07:00:00', '07:30:00', 'aba3207a1a581bac03279f6a5ca8f1f1e7baf8a0e962fa76068460f98c8509d6', 'nonaktif', '2025-12-11 07:27:49'),
(28, '2025-12-11', '07:00:00', '17:00:00', '69ffb32279cc14e7bea18044749088d18f8fc6644f0002882de6039423d19fac', 'nonaktif', '2025-12-11 13:46:29'),
(30, '2025-12-17', '07:00:00', '18:20:00', '451189cb2f2269b566ab81487c0835c7379d48e93b0ac81d89eb0e118ec53f07', 'nonaktif', '2025-12-17 18:16:49'),
(31, '2025-12-18', '07:00:00', '09:00:00', '286b5eb78ce6f0a87ddf01dada9f625870cb7f08813b492f5b97dff27587b420', 'aktif', '2025-12-18 07:50:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','pegawai') DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nip`, `username`, `nama`, `password`, `role`, `status`, `foto`, `created_at`, `updated_at`) VALUES
(1, '100001', 'amin', 'Amin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'aktif', NULL, '2025-12-06 10:25:54', '2025-12-06 10:25:54'),
(2, '123456', 'admin', 'Administrator', '$2y$10$m4Kvb33vAWKGo0zZWKOFAeZ4WZiXyQQ0q36t0JiLJc7Miu0vR5lqa', 'admin', 'aktif', NULL, '2025-12-06 04:13:57', NULL),
(22, '999999999999999999', 'jule', 'jul', '$2y$10$PXhZeQXkKw348Va3nrIJHeYruXF3mpKoDQ937ay/bEH0aNqOkSi4C', 'pegawai', 'aktif', NULL, '2025-12-25 16:43:28', '2025-12-25 18:20:29'),
(23, '000000000000000001', 'selvi', 'selvi', '$2y$10$xJ2XdfYaQj0Q2KsN2pX54.Nc2w.yIQUbey8S0y0od03Npo21.d61q', 'pegawai', 'aktif', NULL, '2025-12-25 18:20:18', '2025-12-25 19:08:50'),
(24, '000000000000000002', 'susi', 'Susi', '$2y$10$xcwWgVN4/wYEriIE4hG6cOQT2u5BBSVhix2DwNI4NhmUi92LzXrce', 'pegawai', 'aktif', NULL, '2025-12-25 19:39:08', '2025-12-25 19:50:56');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `ijin`
--
ALTER TABLE `ijin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_waktu` (`waktu`),
  ADD KEY `idx_keterangan` (`keterangan`),
  ADD KEY `idx_user_waktu` (`user_id`,`waktu`);

--
-- Indeks untuk tabel `qr_code`
--
ALTER TABLE `qr_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_token_status` (`token`,`status`),
  ADD KEY `idx_tanggal_status` (`tanggal`,`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `ijin`
--
ALTER TABLE `ijin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT untuk tabel `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `ijin`
--
ALTER TABLE `ijin`
  ADD CONSTRAINT `ijin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `fk_presensi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
