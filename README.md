# YPKIV — SPK Penilaian Kinerja Guru (Metode SAW)

Sistem Pendukung Keputusan untuk menilai dan merangking kinerja guru di
Yayasan Pendidikan Kristen Immanuel Viktori, menggunakan metode
**Simple Additive Weighting (SAW)**.

Aplikasi berjalan sebagai *single page* tanpa reload: seluruh data diambil
lewat `api.php` dan dirender di sisi klien.

## Fitur

- **Kriteria** — CRUD kriteria beserta sifat (Benefit/Cost) dan bobot, dengan
  validasi total bobot mendekati 1.0000
- **Data Guru** — CRUD guru sekaligus input nilai per kriteria; form nilai
  mengikuti kriteria yang ada secara dinamis
- **Hasil SAW** — perangkingan otomatis, lengkap dengan rincian tiap tahap
  perhitungan (nilai mentah, pembanding, rumus normalisasi, kontribusi)
- **Laporan** — 4 jenis laporan (hasil akhir, data kriteria, data guru, rekap
  nilai mentah), siap cetak dan bisa diekspor ke PDF
- **Hak akses** — `admin` (CRUD penuh) dan `viewer` (read only), divalidasi di
  sisi server, bukan hanya disembunyikan di UI
- **PWA** — bisa dipasang di HP lewat `manifest.json` + service worker
- Tampilan responsif untuk HP, tablet, dan desktop

## Metode SAW

Normalisasi matriks keputusan:

| Sifat kriteria | Rumus normalisasi |
|---|---|
| Benefit (makin besar makin baik) | `r = nilai / max(kolom)` |
| Cost (makin kecil makin baik) | `r = min(kolom) / nilai` |

Nilai preferensi tiap guru:

```
V = Σ (bobot_j × r_ij)
```

Guru diurutkan menurun berdasarkan `V`, ranking 1 adalah nilai tertinggi.
Seluruh perhitungan dilakukan di server (`api_calculate_saw()` pada
[api.php](api.php)) agar hasilnya konsisten.

## Kebutuhan

- PHP 8.0 atau lebih baru (diuji pada PHP 8.2) dengan ekstensi `pdo_mysql`
- MySQL / MariaDB
- Apache — paling gampang pakai [XAMPP](https://www.apachefriends.org/)
- Koneksi internet saat aplikasi dibuka, karena Tailwind, jsPDF, dan Lucide
  dimuat dari CDN

## Instalasi

**1. Salin ke htdocs**

```bash
cd C:/xampp/htdocs
git clone https://github.com/AnoAnggoro/YPKIV.git
```

**2. Nyalakan Apache dan MySQL** lewat XAMPP Control Panel.

**3. Import database**

> Skrip ini menjalankan `DROP TABLE`. Jangan dijalankan di database yang sudah
> berisi data yang masih dipakai.

Lewat phpMyAdmin: buka http://localhost/phpmyadmin → tab **Import** → pilih
`database.sql` → **Go**. Atau lewat terminal:

```bash
C:/xampp/mysql/bin/mysql -u root < database.sql
```

Skrip akan membuat database `ypkiv_db`, empat tabel (`kriteria`, `guru`,
`nilai`, `users`), dan mengisi data contoh.

**4. Sesuaikan koneksi bila perlu**

Setelan default di [koneksi.php](koneksi.php) sudah cocok dengan XAMPP standar
(`root`, password kosong). Ubah bila MySQL Anda memakai kredensial lain:

```php
'host'     => '127.0.0.1',
'dbname'   => 'ypkiv_db',
'username' => 'root',
'password' => '',
```

**5. Buka aplikasi** di http://localhost/YPKIV/

## Akun demo

| Username | Password | Peran |
|---|---|---|
| `admin` | `admin123` | CRUD penuh |
| `viewer` | `viewer123` | Hanya melihat hasil dan laporan |

Pendaftaran lewat tombol "Daftar sekarang" selalu menghasilkan akun `viewer`.
Untuk menaikkan seseorang jadi admin, ubah langsung di database:

```sql
UPDATE users SET role = 'admin' WHERE username = 'namauser';
```

**Ganti password akun demo sebelum dipakai sungguhan.**

## Struktur berkas

```
index.php       Seluruh tampilan + logika frontend (HTML, CSS, JavaScript)
api.php         Endpoint JSON: autentikasi, CRUD, perhitungan SAW
koneksi.php     Konfigurasi dan koneksi PDO ke MySQL
database.sql    Skema tabel + data contoh
manifest.json   Metadata PWA
sw.js           Service worker (cache offline)
assets/         Ikon aplikasi
```

## API

Semua permintaan ditujukan ke `api.php?action=<nama>` dan membalas JSON dengan
bentuk `{ success, message, data }`.

| Action | Akses | Keterangan |
|---|---|---|
| `login`, `register`, `logout`, `me` | publik | Autentikasi sesi |
| `bootstrap` | login | Ambil seluruh data awal sekaligus |
| `list_kriteria`, `list_guru` | login | Ambil data per bagian |
| `saw` / `hasil` / `report_data` | login | Hitung ulang perangkingan |
| `save_kriteria`, `delete_kriteria` | admin | CRUD kriteria |
| `save_guru`, `delete_guru` | admin | CRUD guru beserta nilainya |

## Catatan

- Konsol browser menampilkan peringatan bahwa `cdn.tailwindcss.com` tidak
  disarankan untuk produksi. Itu hanya peringatan, bukan error; menghilangkannya
  perlu proses build dengan Tailwind CLI atau PostCSS.
- Saat terjadi kesalahan, `api.php` masih mengirim pesan asli dari server.
  Praktis untuk pengembangan lokal, tapi sebaiknya diganti pesan umum dan
  dicatat lewat `error_log()` bila di-hosting publik.
- Kredensial di `koneksi.php` adalah bawaan XAMPP untuk localhost. Jangan
  dipakai lagi di server produksi.
