-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 30, 2025 at 09:50 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL
  `password` varchar(100) NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'Guru Kepala Sekolah', '$2y$10$9OspP1EDeLTvY01eR0Nih.7TPfPId7vVldfYaO.4appJIMm6aTHbq');

-- --------------------------------------------------------

--
-- Table structure for table `kandidat`
--

CREATE TABLE `kandidat` (
  `id_kandidat` int NOT NULL,
  `nama_ketua` varchar(50) NOT NULL,
  `nama_wakil` varchar(100) NOT NULL,
  `kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `visi` text NOT NULL,
  `misi` text NOT NULL,
  `gambar` varchar(500) NOT NULL,
  `nomor_urut` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kandidat`
--

INSERT INTO `kandidat` (`id_kandidat`, `nama_ketua`, `nama_wakil`, `kelas`, `visi`, `misi`, `gambar`, `nomor_urut`) VALUES
(11, 'Yuda Satria Wicaksono', 'Firza Aftan Hidayat', 'XII RPL 4', 'Menjadikan OSIS sebagai wadah pengembangan kreativitas, kepemimpinan, dan solidaritas siswa untuk menciptakan lingkungan sekolah yang aktif, berprestasi, dan berkarakter.', '1. Meningkatkan kualitas kegiatan akademik maupun non-akademik melalui program kerja yang inovatif dan bermanfaat bagi seluruh siswa.\r\n\r\n2. Menjadi jembatan komunikasi yang efektif antara siswa, guru, dan pihak sekolah.\r\n\r\n3. Menumbuhkan rasa kebersamaan, kepedulian sosial, serta disiplin di kalangan siswa.\r\n\r\n4. Mendukung siswa dalam mengembangkan bakat dan minat melalui ekstrakurikuler serta kegiatan positif lainnya.\r\n\r\n5. Membentuk budaya sekolah yang ramah, tertib, dan inspiratif dengan menegakkan nilai-nilai kejujuran, tanggung jawab, dan kerja sama.', 'kandidat_1757852947.jpg', 0),
(12, 'Haffiz Tio', 'Dimas Erlansyah', 'XII RPL 3', 'Membangun budaya membaca di sekolah', 'menjadi contoh yang baik bagi para siswa di sekolah', 'kandidat_1758245983.jpg', 0),
(13, 'Bintang Prasetya', 'Wildan Bagus', 'XII DKV 1', 'Membangun sekolah yang lebih bersih dan asri', '1. menjadikan sekolah sebagai sarana membangun kreativitas dan hobi\r\n\r\n2. membangun budaya membaca di sekolah\r\n\r\n3. membuat sekolah menjadi tempat membangun prestasi bagi para siswa\r\n', 'kandidat_1758245857.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pemilih`
--

CREATE TABLE `pemilih` (
  `id` int NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_voting` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemilih`
--

INSERT INTO `pemilih` (`id`, `nama`, `kelas`, `nis`, `username`, `password`, `status_voting`, `created_at`) VALUES
(1, 'Ali Raditya Al Haq', 'XII RPL 4', '232410087', '232410087', '232410087', '', '2025-09-18 06:38:09'),
(2, 'Firza Arka Alfarizky', 'XII TKJ 1', '232410088', '232410088', '232410088', '', '2025-09-18 06:54:24'),
(3, 'Azril Naufal Amzari', 'XII RPL 3', '232410090', '232410090', '232410090', '', '2025-09-19 01:24:29'),
(4, 'Reza Habibie', 'XI RPL 1', '232410060', '232410060', '232410060', '', '2025-09-19 01:25:17'),
(5, 'Rusdi Setiawan', 'XII TKJ 2', '232410050', '232410050', '232410050', '', '2025-09-19 01:26:50'),
(6, 'Syarief Putra', 'X DKV 1', '232410051', '232410051', '232410051', '', '2025-09-19 01:34:24'),
(7, 'Agus Hermawan', 'X TKJ 1', '232410052', '232410052', '232410052', '', '2025-09-19 01:35:35'),
(8, 'Siti Halimah', 'XII RPL 3', '232410055', '232410055', '232410055', '', '2025-09-19 01:41:02'),
(9, 'Abdul Aziz', 'XII TKJ 2', '232410086', '232410086', '232410086', '', '2025-09-19 01:57:26'),
(10, 'Rafi Maulana', 'XII TKJ 1', '232410085', '232410085', '232410085', '', '2025-09-19 02:05:29'),
(12, 'Faiz Rizky Alif', 'X RPL 2', '232410053', '232410053', '232410053', '', '2025-09-19 03:05:04'),
(13, 'Bagas Adi Nugroho', 'XII RPL 3', '232410100', '232410100', '232410100', '', '2025-09-19 03:06:52'),
(14, 'Syifa Putri Rahma', 'XI DKV 1', '232410095', '232410095', '232410095', '', '2025-09-19 03:18:01'),
(15, 'Aisyah Putri', 'X TKJ 2', '232410030', '232410030', '232410030', '', '2025-09-19 05:26:46'),
(16, 'Adeline Wijaya', 'XII IPA 1', '232410041', '232410041', '232410041', '', '2025-09-21 01:48:12'),
(17, 'Catherine Vallencia', 'XII IPA 1', '232410042', '232410042', '232410042', '', '2025-09-21 02:33:39'),
(18, 'Abigail Rachel', 'XII IPS 1', '232410043', '232410043', '232410043', '', '2025-09-21 08:04:10'),
(19, 'Satria Mahatir', 'XII TKJ 1', '232410072', '232410072', '232410072', '', '2025-09-21 08:06:39'),
(20, 'Oline Manuel', 'XII IPS 1', '232410054', '232410054', '232410054', '', '2025-09-21 08:32:47'),
(21, 'Fahreza Febiyanto', 'XI TKJ 2', '232410040', '232410040', '232410040', '', '2025-09-21 08:34:51'),
(22, 'Dicky Yufa', 'XI TKJ 1', '232410044', '232410044', '232410044', '', '2025-09-21 08:53:10'),
(23, 'Abdul Rizal', 'X RPL 1', '232410031', '232410031', '232410031', '', '2025-09-21 08:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `suara`
--

CREATE TABLE `suara` (
  `id` int NOT NULL,
  `pemilih_id` int NOT NULL,
  `kandidat_id` int NOT NULL,
  `waktu_voting` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting_config`
--

CREATE TABLE `voting_config` (
  `id` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `voting_config`
--

INSERT INTO `voting_config` (`id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 0, '2025-09-21 04:45:28', '2025-09-28 07:23:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kandidat`
--
ALTER TABLE `kandidat`
  ADD PRIMARY KEY (`id_kandidat`);

--
-- Indexes for table `pemilih`
--
ALTER TABLE `pemilih`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- Indexes for table `suara`
--
ALTER TABLE `suara`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`pemilih_id`),
  ADD KEY `kandidat_id` (`kandidat_id`);

--
-- Indexes for table `voting_config`
--
ALTER TABLE `voting_config`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kandidat`
--
ALTER TABLE `kandidat`
  MODIFY `id_kandidat` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pemilih`
--
ALTER TABLE `pemilih`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `suara`
--
ALTER TABLE `suara`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `suara`
--
ALTER TABLE `suara`
  ADD CONSTRAINT `suara_ibfk_1` FOREIGN KEY (`pemilih_id`) REFERENCES `pemilih` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suara_ibfk_2` FOREIGN KEY (`kandidat_id`) REFERENCES `kandidat` (`id_kandidat`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
