-- PERINGATAN: skrip ini menghapus (DROP) semua tabel. Hanya untuk instalasi baru.
-- Nama database harus sama dengan koneksi.php (ypkiv_db).
CREATE DATABASE IF NOT EXISTS ypkiv_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ypkiv_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS guru;
DROP TABLE IF EXISTS kriteria;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE kriteria (
  id_kriteria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL UNIQUE,
  nama_kriteria VARCHAR(100) NOT NULL,
  sifat ENUM('Benefit', 'Cost') NOT NULL DEFAULT 'Benefit',
  bobot DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE guru (
  id_guru INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_guru VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE nilai (
  id_nilai INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_guru INT UNSIGNED NOT NULL,
  id_kriteria INT UNSIGNED NOT NULL,
  nilai DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_guru_kriteria (id_guru, id_kriteria),
  CONSTRAINT fk_nilai_guru FOREIGN KEY (id_guru) REFERENCES guru (id_guru) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_nilai_kriteria FOREIGN KEY (id_kriteria) REFERENCES kriteria (id_kriteria) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
  id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO kriteria (kode, nama_kriteria, sifat, bobot) VALUES
('K1', 'Kompetensi Pedagogik', 'Benefit', 0.3000),
('K2', 'Kompetensi Profesional', 'Benefit', 0.2500),
('K3', 'Kedisiplinan', 'Benefit', 0.2000),
('K4', 'Kehadiran', 'Benefit', 0.1500),
('K5', 'Jumlah Pelanggaran', 'Cost', 0.1000);

INSERT INTO guru (nama_guru) VALUES
('Siti Aisyah, S.Pd'),
('Budi Santoso, S.Pd'),
('Ahmad Fauzi, M.Pd');

INSERT INTO nilai (id_guru, id_kriteria, nilai) VALUES
(1, 1, 90.0000), (1, 2, 88.0000), (1, 3, 92.0000), (1, 4, 95.0000), (1, 5, 1.0000),
(2, 1, 85.0000), (2, 2, 90.0000), (2, 3, 89.0000), (2, 4, 90.0000), (2, 5, 2.0000),
(3, 1, 92.0000), (3, 2, 84.0000), (3, 3, 95.0000), (3, 4, 88.0000), (3, 5, 0.0000);

INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$XuXaHUt/QAtQqBHk1Fc0PeNlGtJcmS/LHbA2JwxxJCkGzMRmd75CO', 'admin'),
('viewer', '$2y$10$xxnshG9vwFQlcP5kfGzgiur61wztq1JI/4hZJ8vuHJs9RnGOGHB82', 'viewer');
