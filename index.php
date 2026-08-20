<?php
declare(strict_types=1);
session_start();
$sessionUser = $_SESSION['auth_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#2563eb">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>SPK SAW Penilaian Kinerja Guru</title>
  <link rel="manifest" href="manifest.json">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
  <script src="https://unpkg.com/lucide@1.33.0/dist/umd/lucide.min.js"></script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
              400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
              800: '#1e40af', 900: '#1e3a8a'
            }
          },
          boxShadow: {
            soft: '0 14px 45px rgba(15, 23, 42, 0.12)'
          }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { color-scheme: light; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background:
        radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 30%),
        radial-gradient(circle at top right, rgba(14, 165, 233, 0.10), transparent 24%),
        linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: fadeIn 220ms ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .auth-hidden { display: none !important; }
    .report-sheet { background: #fff; }
    
    /* CSS Khusus Print */
    @media print {
      body { background: #fff !important; }
      .no-print, #toast-container, .tab-pane:not(#tab-laporan) { display: none !important; }
      #tab-laporan { display: block !important; }
      .report-sheet.hidden { display: none !important; } /* Sembunyikan laporan yang tidak aktif saat di-print */
      .print-card { box-shadow: none !important; border: 0 !important; }
      .print-break { break-inside: avoid; }
    }

    /* Tidak ada scroll horizontal di layar kecil.
       Pakai clip, bukan hidden, supaya nav position:sticky tetap jalan. */
    html { overflow-x: hidden; }
    @supports (overflow: clip) { html { overflow-x: clip; } }
    .overflow-x-auto { -webkit-overflow-scrolling: touch; }

    /* ===== Mobile & Tablet ===== */
    @media screen and (max-width: 1023px) {
      /* tabel detail SAW bersarang di dalam <td>: batasi lebar agar bisa di-scroll,
         kalau tidak <td> ikut melebar dan merusak layout halaman */
      #saw-table-wrap td .overflow-x-auto,
      #saw-table-wrap td > div { max-width: calc(100vw - 5rem); }
    }

    /* ===== Mobile Responsive ===== */
    @media screen and (max-width: 639px) {
      .nav-scroll-container {
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
      }
      .nav-scroll-container::-webkit-scrollbar { display: none; }
      table th, table td {
        padding: 0.4rem 0.5rem !important;
        font-size: 0.75rem;
      }
      table td button {
        padding: 0.25rem 0.4rem !important;
        font-size: 0.7rem !important;
        border-radius: 0.375rem !important;
      }
      table td .flex {
        gap: 0.25rem !important;
      }
      .menu-laporan-btn {
        font-size: 0.75rem !important;
        padding: 0.625rem 0.75rem !important;
      }
      .report-sheet h3 {
        font-size: 1.125rem !important;
      }
      #saw-table-wrap td .overflow-x-auto,
      #saw-table-wrap td > div { max-width: calc(100vw - 3rem); }
      .report-sheet .mt-16 { margin-top: 3rem; }
      .report-sheet .mt-10 .max-w-sm { max-width: 100%; }
      /* judul panjang jangan memaksa halaman melebar */
      h1, h2, h3 { overflow-wrap: anywhere; }
      #toast-container { right: 0.75rem; left: 0.75rem; width: auto; }
    }
  </style>
</head>
<body class="min-h-screen text-slate-800">

  <div id="login-screen" class="<?= $sessionUser ? 'auth-hidden' : '' ?> min-h-screen px-4 py-8">
    <div class="mx-auto flex min-h-[calc(100svh-4rem)] max-w-5xl items-center justify-center">
      <div class="grid w-full overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-soft lg:grid-cols-2">
        <div class="bg-gradient-to-br from-primary-700 via-primary-600 to-sky-500 p-5 text-white sm:p-10">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-white/90">
            <i data-lucide="shield-check" class="h-4 w-4"></i>
          Admin Login
          </div>
          <h1 class="mt-4 text-xl font-extrabold leading-tight sm:text-3xl md:text-4xl">Yayasan Pendidikan Kristen Immanuel Viktori</h1>
          <p class="mt-4 max-w-xl text-sm leading-7 text-white/85 sm:text-base">Masuk untuk mengelola kriteria, guru, nilai, dan menghasilkan laporan. Viewer hanya dapat melihat hasil dan laporan.</p>
          <div class="mt-8 grid gap-3 text-sm text-white/90 sm:grid-cols-2">
            <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
              <div class="text-xs uppercase tracking-[0.2em] text-white/75">Admin</div>
              <div class="mt-1 font-bold">CRUD penuh</div>
            </div>
            <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
              <div class="text-xs uppercase tracking-[0.2em] text-white/75">Viewer</div>
              <div class="mt-1 font-bold">Read only</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-5 sm:p-10 relative">
          <div class="max-w-md mx-auto">
            
            <div id="login-container">
              <h2 class="text-2xl font-extrabold text-slate-900">Masuk ke sistem</h2>
              <p class="mt-2 text-sm text-slate-500">Gunakan akun Anda untuk masuk.</p>
              <div id="login-alert" class="mt-5 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
              
              <form id="login-form" class="mt-6 space-y-4">
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Username</label>
                  <input id="login-username" name="username" type="text" autocomplete="username" required minlength="3" maxlength="50" class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="admin">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Password</label>
                  <input id="login-password" name="password" type="password" autocomplete="current-password" required minlength="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="••••••••">
                </div>
                
                <div class="pt-2 text-center text-sm text-slate-600">
                  Belum punya akun? <button type="button" id="btn-show-register" class="font-semibold text-primary-600 hover:underline">Daftar sekarang</button>
                </div>

                <button id="login-submit" type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                  <i data-lucide="log-in" class="h-4 w-4"></i> Masuk
                </button>
              </form>
              
              <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                <p class="font-semibold text-slate-900">Akun Demo:</p>
                <p class="mt-1">Admin: <span class="font-mono font-semibold">admin / admin123</span></p>
                <p>Viewer: <span class="font-mono font-semibold">viewer / viewer123</span></p>
              </div>
            </div>

            <div id="register-container" class="hidden">
              <h2 class="text-2xl font-extrabold text-slate-900">Buat Akun Baru</h2>
              <form id="register-form" class="mt-6 space-y-4">
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Username</label>
                  <input name="username" type="text" required minlength="3" maxlength="50" class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Password</label>
                  <input name="password" type="password" required minlength="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                  <input name="confirm_password" type="password" required minlength="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                
                <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                  Daftar Sekarang
                </button>
                <button type="button" id="btn-cancel-register" class="w-full text-center text-sm font-semibold text-slate-500 hover:text-slate-800 transition pt-2">
                  Kembali ke Login
                </button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="app-shell" class="<?= $sessionUser ? '' : 'auth-hidden' ?>">
    <div class="no-print absolute inset-0 -z-10 overflow-hidden">
      <div class="absolute -left-20 top-12 h-72 w-72 rounded-full bg-primary-200/40 blur-3xl"></div>
      <div class="absolute right-0 top-24 h-72 w-72 rounded-full bg-sky-200/40 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <header class="no-print mb-3 sm:mb-6 overflow-hidden rounded-2xl sm:rounded-3xl border border-white/70 bg-gradient-to-r from-primary-700 via-primary-600 to-sky-500 p-4 sm:p-6 text-white shadow-soft">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white/90">
              <i data-lucide="graduation-cap" class="h-4 w-4"></i> Sistem Penilaian Kinerja Guru
            </div>
            <h1 class="mt-3 text-xl font-extrabold leading-tight sm:text-3xl md:text-4xl">Yayasan Pendidikan Kristen Immanuel Viktori</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base">Kelola kriteria, input nilai guru secara dinamis, dan hitung perangkingan metode Simple Additive Weighting tanpa reload halaman.</p>
          </div>
          
          <div class="flex flex-col gap-3 lg:items-end">
            <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-sm">
              <div class="text-xs uppercase tracking-[0.18em] text-white/75">Login Aktif</div>
              <div id="header-user" class="mt-1 text-lg font-bold"><?= $sessionUser ? htmlspecialchars($sessionUser['username']) : '-' ?></div>
              <div id="header-role" class="text-sm text-white/85"><?= $sessionUser ? strtoupper($sessionUser['role']) : '' ?></div>
            </div>
            <button id="btn-logout" class="inline-flex items-center gap-2 rounded-2xl border border-white/25 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
              <i data-lucide="log-out" class="h-4 w-4"></i> Logout
            </button>
          </div>
        </div>
      </header>

      <nav class="no-print sticky top-0 z-30 mb-3 sm:mb-6 rounded-xl sm:rounded-2xl border border-slate-200/70 bg-white/90 p-1.5 sm:p-2 shadow-soft backdrop-blur">
        <div class="nav-scroll-container flex gap-1.5 overflow-x-auto sm:grid sm:grid-cols-3 sm:gap-2 md:grid-cols-5 sm:overflow-visible">
          <button class="tab-btn flex-shrink-0 whitespace-nowrap rounded-xl px-3 py-2 text-xs sm:text-sm sm:px-4 sm:py-3 font-semibold transition hover:bg-primary-50 hover:text-primary-700" data-tab="dashboard">
            <span class="flex items-center justify-center gap-2"><i data-lucide="layout-grid"></i> Dashboard</span>
          </button>
          <button class="tab-btn flex-shrink-0 whitespace-nowrap rounded-xl px-3 py-2 text-xs sm:text-sm sm:px-4 sm:py-3 font-semibold transition hover:bg-primary-50 hover:text-primary-700" data-tab="kriteria">
            <span class="flex items-center justify-center gap-2"><i data-lucide="sliders-horizontal"></i> Kriteria</span>
          </button>
          <button class="tab-btn flex-shrink-0 whitespace-nowrap rounded-xl px-3 py-2 text-xs sm:text-sm sm:px-4 sm:py-3 font-semibold transition hover:bg-primary-50 hover:text-primary-700" data-tab="guru">
            <span class="flex items-center justify-center gap-2"><i data-lucide="users"></i> Data Guru</span>
          </button>
          <button class="tab-btn flex-shrink-0 whitespace-nowrap rounded-xl px-3 py-2 text-xs sm:text-sm sm:px-4 sm:py-3 font-semibold transition hover:bg-primary-50 hover:text-primary-700" data-tab="hasil">
            <span class="flex items-center justify-center gap-2"><i data-lucide="bar-chart-3"></i> Hasil</span>
          </button>
          <button class="tab-btn flex-shrink-0 whitespace-nowrap rounded-xl px-3 py-2 text-xs sm:text-sm sm:px-4 sm:py-3 font-semibold transition hover:bg-primary-50 hover:text-primary-700" data-tab="laporan">
            <span class="flex items-center justify-center gap-2"><i data-lucide="file-text"></i> Laporan</span>
          </button>
        </div>
      </nav>

      <section id="tab-dashboard" class="tab-pane active space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-start justify-between">
              <div><p class="text-sm text-slate-500">Total Kriteria</p><h3 id="stat-kriteria" class="mt-2 text-3xl font-extrabold text-slate-900">0</h3></div>
              <div class="rounded-2xl bg-primary-50 p-3 text-primary-700"><i data-lucide="sliders-horizontal"></i></div>
            </div>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-start justify-between">
              <div><p class="text-sm text-slate-500">Total Guru</p><h3 id="stat-guru" class="mt-2 text-3xl font-extrabold text-slate-900">0</h3></div>
              <div class="rounded-2xl bg-sky-50 p-3 text-sky-700"><i data-lucide="users"></i></div>
            </div>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-start justify-between">
              <div><p class="text-sm text-slate-500">Total Bobot</p><h3 id="stat-bobot" class="mt-2 text-3xl font-extrabold text-slate-900">0.0000</h3></div>
              <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-700"><i data-lucide="scale"></i></div>
            </div>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-start justify-between">
              <div><p class="text-sm text-slate-500">Ranking Terbaik</p><h3 id="stat-top" class="mt-2 text-xl font-extrabold text-slate-900">-</h3></div>
              <div class="rounded-2xl bg-amber-50 p-3 text-amber-700"><i data-lucide="trophy"></i></div>
            </div>
          </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft lg:col-span-2 w-full min-w-0 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-900">Ringkasan Perhitungan</h2>
                <p class="mt-1 text-sm text-slate-500">Data diperbarui secara real-time dari endpoint PHP.</p>
              </div>
              <button id="btn-refresh" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                <i data-lucide="rotate-cw" class="h-4 w-4"></i> Muat Ulang Data
              </button>
            </div>
            <div id="dashboard-warning" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>
            <div id="top-3-preview" class="mt-5 grid gap-3"></div>
          </article>

          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
            <h2 class="text-lg font-bold text-slate-900">Panduan Singkat</h2>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
              <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-primary-600"></span>Tahap Persiapan:<span class="font-semibold text-slate-800"> Atur Kriteria & Bobot</span></li>
              <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-primary-600"></span>Data Master:<span class="font-semibold text-slate-800"> Input Data Alternatif</span></li>
              <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-primary-600"></span>Proses Evaluasi:<span class="font-semibold text-slate-800"> Lihat Hasil Perhitungan</span></li>
              <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-primary-600"></span>Hasil Akhir:<span class="font-semibold text-slate-800"> Cetak Laporan</span></li>
            </ul>
          </article>
        </div>
      </section>

      <section id="tab-kriteria" class="tab-pane space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-900">Data Kriteria</h2>
                <p class="mt-1 text-sm text-slate-500">CRUD kriteria dan validasi total bobot.</p>
              </div>
              <button id="btn-kriteria-baru" data-admin-only="true" class="hidden inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah
              </button>
            </div>
            <div id="criteria-validation" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>
            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-100">
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                  <tr>
                    <th class="px-4 py-3 font-semibold">Kode</th>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Sifat</th>
                    <th class="px-4 py-3 font-semibold">Bobot</th>
                    <th class="px-4 py-3 font-semibold">Aksi</th>
                  </tr>
                </thead>
                <tbody id="criteria-table" class="divide-y divide-slate-100 bg-white"></tbody>
              </table>
            </div>
          </article>

          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
            <h2 id="criteria-form-title" class="text-lg font-bold text-slate-900">Tambah Kriteria</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan bobot desimal dan pastikan total mendekati 1.00.</p>
            <form id="criteria-form" class="mt-5 space-y-4">
              <input type="hidden" name="id_kriteria" id="criteria-id">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kode</label>
                <input type="text" name="kode" id="criteria-kode" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="C1">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama Kriteria</label>
                <input type="text" name="nama_kriteria" id="criteria-nama" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="Contoh: Kedisiplinan">
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Sifat</label>
                  <select name="sifat" id="criteria-sifat" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="Benefit">Benefit</option>
                    <option value="Cost">Cost</option>
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Bobot</label>
                  <input type="number" step="0.0001" min="0" max="1" name="bobot" id="criteria-bobot" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="0.3000">
                </div>
              </div>
              <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                  <i data-lucide="save" class="h-4 w-4"></i> Simpan
                </button>
                <button type="button" id="criteria-cancel" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                  Batal
                </button>
              </div>
            </form>
          </article>
        </div>
      </section>

      <section id="tab-guru" class="tab-pane space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-900">Data Guru</h2>
                <p class="mt-1 text-sm text-slate-500">Input nilai akan mengikuti kriteria yang ada.</p>
              </div>
              <button id="btn-guru-baru" data-admin-only="true" class="hidden inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah
              </button>
            </div>
            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-100">
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                  <tr>
                    <th class="px-4 py-3 font-semibold">Nama Guru</th>
                    <th class="px-4 py-3 font-semibold">Nilai Kriteria</th>
                    <th class="px-4 py-3 font-semibold">Aksi</th>
                  </tr>
                </thead>
                <tbody id="guru-table" class="divide-y divide-slate-100 bg-white"></tbody>
              </table>
            </div>
          </article>

          <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
            <h2 id="guru-form-title" class="text-lg font-bold text-slate-900">Tambah Data Guru</h2>
            <form id="guru-form" class="mt-5 space-y-4">
              <input type="hidden" name="id_guru" id="guru-id">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama Guru</label>
                <input type="text" name="nama_guru" id="guru-nama" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="Contoh: Siti Rahma">
              </div>
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label class="block text-sm font-medium text-slate-700">Nilai per Kriteria</label>
                  <span class="text-xs text-slate-500">Gunakan angka desimal bila perlu</span>
                </div>
                <div id="guru-value-fields" class="grid gap-3"></div>
              </div>
              <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                  <i data-lucide="save" class="h-4 w-4"></i> Simpan
                </button>
                <button type="button" id="guru-cancel" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                  Batal
                </button>
              </div>
            </form>
          </article>
        </div>
      </section>

      <section id="tab-hasil" class="tab-pane space-y-6">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-soft w-full min-w-0 overflow-hidden">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-bold text-slate-900">Hasil Perhitungan SAW</h2>
              <p class="mt-1 text-sm text-slate-500">Nilai preferensi diurutkan dari yang terbaik.</p>
            </div>
            <button id="btn-refresh-saw" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
              <i data-lucide="calculator" class="h-4 w-4"></i> Hitung Ulang
            </button>
          </div>
          <div id="saw-table-wrap" class="mt-5 overflow-x-auto rounded-2xl border border-slate-100"></div>
        </article>
      </section>

      <section id="tab-laporan" class="tab-pane space-y-6">
        
        <div class="no-print grid grid-cols-2 gap-3 md:grid-cols-4">
          <button class="menu-laporan-btn active rounded-2xl border border-primary-200 bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700 shadow-sm" data-target="laporan-hasil">
            Hasil Akhir SAW
          </button>
          <button class="menu-laporan-btn rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" data-target="laporan-kriteria">
            Data Kriteria
          </button>
          <button class="menu-laporan-btn rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" data-target="laporan-guru">
            Data Guru
          </button>
          <button class="menu-laporan-btn rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" data-target="laporan-mentah">
            Rekap Nilai Guru
          </button>
        </div>

        <div class="no-print flex flex-wrap items-center justify-between gap-3">
          <p class="text-sm text-slate-500">Pilih jenis laporan di atas sebelum melakukan cetak.</p>
          <div class="flex flex-wrap gap-2">
            <button id="btn-download-pdf" class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100">
              <i data-lucide="file-down" class="h-4 w-4"></i> Download PDF
            </button>
            <button id="btn-print" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
              <i data-lucide="printer" class="h-4 w-4"></i> Cetak Laporan
            </button>
          </div>
        </div>

        <article id="laporan-hasil" class="report-sheet print-card rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-4 sm:p-8 shadow-soft block">
          <div class="print-break border-b-4 border-primary-600 pb-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-primary-600">Yayasan Pendidikan Kristen Immanuel Viktori</p>
            <h3 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">Laporan Hasil Akhir Penilaian Kinerja Guru</h3>
          </div>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Tanggal Cetak</div>
              <div class="report-date mt-1 font-semibold text-slate-800"></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Total Guru Dievaluasi</div>
              <div class="report-total-guru mt-1 font-semibold text-slate-800"></div>
            </div>
          </div>
          <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                  <th class="px-4 py-3 font-semibold">Ranking</th>
                  <th class="px-4 py-3 font-semibold">Nama Guru</th>
                  <th class="px-4 py-3 font-semibold">Nilai Preferensi</th>
                  <th class="px-4 py-3 font-semibold">Keterangan</th>
                </tr>
              </thead>
              <tbody id="report-table-hasil" class="divide-y divide-slate-100 bg-white"></tbody>
            </table>
          </div>
         <div class="mt-10 flex items-end justify-end">
            <div class="w-full max-w-sm text-center text-sm text-slate-700">
              <p class="mb-1 report-city-date"></p>
              <p>Mengetahui,</p>
              <div class="mt-16 border-t border-slate-400 pt-3 font-semibold text-slate-900">Muhammad Yaman</div>
            </div>
          </div>
        </article>

        <article id="laporan-kriteria" class="report-sheet print-card rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-4 sm:p-8 shadow-soft hidden">
          <div class="print-break border-b-4 border-primary-600 pb-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-primary-600">Yayasan Pendidikan Kristen Immanuel Viktori</p>
            <h3 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">Laporan Data Kriteria Penilaian</h3>
          </div>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Tanggal Cetak</div>
              <div class="report-date mt-1 font-semibold text-slate-800"></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Total Kriteria</div>
              <div class="report-total-kriteria mt-1 font-semibold text-slate-800"></div>
            </div>
          </div>
          <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                  <th class="px-4 py-3 font-semibold">No</th>
                  <th class="px-4 py-3 font-semibold">Kode</th>
                  <th class="px-4 py-3 font-semibold">Nama Kriteria</th>
                  <th class="px-4 py-3 font-semibold">Sifat</th>
                  <th class="px-4 py-3 font-semibold">Bobot</th>
                </tr>
              </thead>
              <tbody id="report-table-kriteria" class="divide-y divide-slate-100 bg-white"></tbody>
            </table>
          </div>
          <div class="mt-10 flex items-end justify-end">
            <div class="w-full max-w-sm text-center text-sm text-slate-700">
                 <p class="mb-1 report-city-date"></p>
              <p>Mengetahui,</p>
              <div class="mt-16 border-t border-slate-400 pt-3 font-semibold text-slate-900">Muhammad Yaman</div>
            </div>
          </div>
        </article>

        <article id="laporan-guru" class="report-sheet print-card rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-4 sm:p-8 shadow-soft hidden">
          <div class="print-break border-b-4 border-primary-600 pb-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-primary-600">Yayasan Pendidikan Kristen Immanuel Viktori</p>
            <h3 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">Laporan Data Master Guru</h3>
          </div>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Tanggal Cetak</div>
              <div class="report-date mt-1 font-semibold text-slate-800"></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Total Guru</div>
              <div class="report-total-guru mt-1 font-semibold text-slate-800"></div>
            </div>
          </div>
          <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                  <th class="px-4 py-3 font-semibold">No</th>
                  <th class="px-4 py-3 font-semibold">Nama Guru</th>
                </tr>
              </thead>
              <tbody id="report-table-guru" class="divide-y divide-slate-100 bg-white"></tbody>
            </table>
          </div>
          <div class="mt-10 flex items-end justify-end">
            <div class="w-full max-w-sm text-center text-sm text-slate-700">
                 <p class="mb-1 report-city-date"></p>
              <p>Mengetahui,</p>
              <div class="mt-16 border-t border-slate-400 pt-3 font-semibold text-slate-900">Muhammad Yaman</div>
            </div>
          </div>
        </article>

        <article id="laporan-mentah" class="report-sheet print-card rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-4 sm:p-8 shadow-soft hidden">
          <div class="print-break border-b-4 border-primary-600 pb-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-primary-600">Yayasan Pendidikan Kristen Immanuel Viktori</p>
            <h3 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">Laporan Rekapitulasi Nilai Mentah</h3>
          </div>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Tanggal Cetak</div>
              <div class="report-date mt-1 font-semibold text-slate-800"></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Total Guru & Kriteria</div>
              <div class="mt-1 font-semibold text-slate-800"><span class="report-total-guru"></span> Guru, <span class="report-total-kriteria"></span> Kriteria</div>
            </div>
          </div>
          <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead id="report-head-mentah" class="bg-slate-50 text-left text-slate-600">
                </thead>
              <tbody id="report-table-mentah" class="divide-y divide-slate-100 bg-white"></tbody>
            </table>
          </div>
          <div class="mt-10 flex items-end justify-end">
            <div class="w-full max-w-sm text-center text-sm text-slate-700">
                 <p class="mb-1 report-city-date"></p>
              <p>Mengetahui,</p>
              <div class="mt-16 border-t border-slate-400 pt-3 font-semibold text-slate-900">Muhammad Yaman</div>
            </div>
          </div>
        </article>

      </section>
    </div>
  </div>

  <div id="toast-container" class="pointer-events-none fixed right-4 top-4 z-50 flex w-[calc(100%-2rem)] max-w-md flex-col gap-3 sm:right-6 sm:top-6"></div>

  <script>
    const appState = {
      authenticated: <?= $sessionUser ? 'true' : 'false' ?>,
      user: <?= $sessionUser ? json_encode($sessionUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' ?>,
      role: <?= $sessionUser ? json_encode($sessionUser['role'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' ?>,
      kriteria: [], guru: [], hasil: [],
      totalBobot: 0, bobotValid: true,
      activeTab: 'dashboard', 
      activeReport: 'laporan-hasil', // State untuk tipe laporan
    };

    const apiUrl = 'api.php';
    const number4 = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 4, maximumFractionDigits: 4 });

    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const loginScreen = document.getElementById('login-screen');
    const loginContainer = document.getElementById('login-container');
    const registerContainer = document.getElementById('register-container');
    const appShell = document.getElementById('app-shell');

    function isAdmin() { return appState.role === 'admin'; }

    function escapeHtml(value) {
      return String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    }

    function showToast(type, message) {
      const container = document.getElementById('toast-container');
      const colors = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        error: 'border-rose-200 bg-rose-50 text-rose-800',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        info: 'border-slate-200 bg-white text-slate-700',
      };
      const icons = { success: 'check-circle-2', error: 'triangle-alert', warning: 'alert-triangle', info: 'info' };

      const toast = document.createElement('div');
      toast.className = `pointer-events-auto flex items-start gap-3 rounded-2xl border p-4 shadow-soft transition duration-300 ${colors[type] || colors.info}`;
      toast.innerHTML = `
        <i data-lucide="${icons[type] || icons.info}" class="mt-0.5 h-5 w-5 shrink-0"></i>
        <div class="flex-1 text-sm font-medium">${escapeHtml(message)}</div>
      `;
      container.appendChild(toast);
      lucide.createIcons({ nodes: [toast] });

      setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
      }, 3200);
    }

    async function apiRequest(action, formData = null, method = 'POST') {
      const options = formData ? { method, body: formData } : { method };
      const response = await fetch(`${apiUrl}?action=${encodeURIComponent(action)}`, options);
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.message || 'Permintaan gagal.');
      return payload.data;
    }

    function setAuthState(user) {
      appState.authenticated = Boolean(user);
      appState.user = user;
      appState.role = user?.role ?? null;
      loginScreen.classList.toggle('auth-hidden', Boolean(user));
      appShell.classList.toggle('auth-hidden', !user);
      applyPermissions();
    }

    function applyPermissions() {
      document.querySelectorAll('[data-admin-only="true"]').forEach((node) => {
        node.classList.toggle('hidden', !isAdmin());
        if ('disabled' in node) node.disabled = !isAdmin();
      });

      document.querySelectorAll('#criteria-form input, #criteria-form select, #guru-form input, #criteria-form button[type="submit"], #guru-form button[type="submit"]').forEach((input) => {
        input.disabled = !isAdmin();
      });
    }

   // Fungsi Render PDF Dinamis berdasar Laporan Aktif
function buildPdfReport() {
  if (!window.jspdf || !window.jspdf.jsPDF) throw new Error('Library PDF belum termuat.');

  // 1. INISIALISASI VARIABEL HARUS DI ATAS
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p', 'mm', 'a4');
  const margin = 14;

  // 2. SETTING HEADER
  doc.setFillColor(37, 99, 235);
  doc.rect(0, 0, 210, 30, 'F');
  doc.setTextColor(255, 255, 255);
  doc.setFontSize(16);
  doc.setFont('helvetica', 'bold');
  doc.text('YAYASAN PENDIDIKAN KRISTEN IMMANUEL VIKTORI', 105, 12, { align: 'center' });
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  
  doc.setTextColor(20, 24, 31);
  doc.setFontSize(11);
  const dateStr = `Tanggal Cetak: ${new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date())}`;
  doc.text(dateStr, margin, 40);
  doc.text(`Admin Login: ${appState.user?.username || '-'}`, margin, 46);

  // 3. LOGIKA DATA
  let headData = [];
  let bodyData = [];
  let filename = 'laporan.pdf';

  if (appState.activeReport === 'laporan-hasil') {
    doc.setTextColor(255, 255, 255);
    doc.text('LAPORAN HASIL AKHIR PENILAIAN KINERJA GURU (SAW)', 105, 19, { align: 'center' });
    doc.setTextColor(20, 24, 31);
    doc.text(`Total Guru Dievaluasi: ${appState.guru.length}`, margin, 52);
    headData = [['Ranking', 'Nama Guru', 'Nilai Preferensi']];
    bodyData = appState.hasil.map((row) => [String(row.ranking), row.nama_guru, row.preferensi.toFixed(4)]);
    filename = 'laporan-hasil-akhir.pdf';
  } else if (appState.activeReport === 'laporan-kriteria') {
    doc.setTextColor(255, 255, 255);
    doc.text('LAPORAN DATA KRITERIA PENILAIAN', 105, 19, { align: 'center' });
    doc.setTextColor(20, 24, 31);
    doc.text(`Total Kriteria: ${appState.kriteria.length}`, margin, 52);
    headData = [['No', 'Kode', 'Nama Kriteria', 'Sifat', 'Bobot']];
    bodyData = appState.kriteria.map((row, i) => [String(i + 1), row.kode, row.nama_kriteria, row.sifat, Number(row.bobot).toFixed(4)]);
    filename = 'laporan-data-kriteria.pdf';
  } else if (appState.activeReport === 'laporan-guru') {
    doc.setTextColor(255, 255, 255);
    doc.text('LAPORAN DATA MASTER GURU', 105, 19, { align: 'center' });
    doc.setTextColor(20, 24, 31);
    doc.text(`Total Guru: ${appState.guru.length}`, margin, 52);
    headData = [['No', 'Nama Guru']];
    bodyData = appState.guru.map((row, i) => [String(i + 1), row.nama_guru]);
    filename = 'laporan-data-guru.pdf';
  } else if (appState.activeReport === 'laporan-mentah') {
    doc.setTextColor(255, 255, 255);
    doc.text('LAPORAN REKAPITULASI NILAI MENTAH', 105, 19, { align: 'center' });
    doc.setTextColor(20, 24, 31);
    doc.text(`Total Guru: ${appState.guru.length} | Total Kriteria: ${appState.kriteria.length}`, margin, 52);
    const dynamicHeaders = appState.kriteria.map(k => k.kode);
    headData = [['No', 'Nama Guru', ...dynamicHeaders]];
    bodyData = appState.guru.map((row, i) => {
      const rowData = [String(i + 1), row.nama_guru];
      appState.kriteria.forEach(k => rowData.push(String(row.nilai?.[k.id_kriteria] || 0)));
      return rowData;
    });
    filename = 'laporan-rekap-mentah.pdf';
  }

  // 4. RENDER TABEL (Sekarang 'doc' sudah terdefinisi)
  doc.autoTable({
    startY: 60,
    head: headData,
    body: bodyData,
    theme: 'grid',
    styles: { fontSize: 9, cellPadding: 3 },
    headStyles: { fillColor: [37, 99, 235] },
    margin: { left: margin, right: margin },
  });

  // 5. MEMBUAT BLOK TANDA TANGAN BESERTA LOKASI DAN TANGGAL
  const fullDatePdf = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }).format(new Date()).replace(/,/, '');
  const cityDatePdf = `Bekasi, ${fullDatePdf}`;

  const finalY = doc.lastAutoTable?.finalY ?? 120;
  
  // Posisi tanggal dan "Mengetahui,"
  doc.text(cityDatePdf, 150, finalY + 12, { align: 'center' });
  doc.text('Mengetahui,', 150, finalY + 18, { align: 'center' });
  
  // Posisi Garis (y = 40)
  doc.line(125, finalY + 40, 175, finalY + 40);
  
  // Posisi teks di bawah garis (y = 45) - HARUS LEBIH BESAR DARI GARIS
  doc.text('Kepala Sekolah / Pimpinan', 150, finalY + 45, { align: 'center' });
  
  doc.save(filename);
}

    function setActiveTab(tab) {
      appState.activeTab = tab;
      tabButtons.forEach((button) => {
        const active = button.dataset.tab === tab;
        button.classList.toggle('bg-primary-600', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('bg-white', !active);
        button.classList.toggle('text-slate-700', !active);
      });

      tabPanes.forEach((pane) => pane.classList.toggle('active', pane.id === `tab-${tab}`));
      if (tab === 'laporan') updateReportSection();
      lucide.createIcons();
    }

    // Toggle Tampilan Laporan
    document.querySelectorAll('.menu-laporan-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const targetId = e.currentTarget.dataset.target;
        appState.activeReport = targetId;
        
        // Aktifkan button menu
        document.querySelectorAll('.menu-laporan-btn').forEach(b => {
          const isActive = b.dataset.target === targetId;
          b.classList.toggle('bg-primary-600', isActive);
          b.classList.toggle('text-white', isActive);
          b.classList.toggle('border-primary-200', isActive);
          b.classList.toggle('bg-white', !isActive);
          b.classList.toggle('text-slate-700', !isActive);
          b.classList.toggle('border-slate-200', !isActive);
        });

        // Tampilkan sheet yang benar
        document.querySelectorAll('.report-sheet').forEach(sheet => {
          sheet.classList.toggle('hidden', sheet.id !== targetId);
          sheet.classList.toggle('block', sheet.id === targetId);
        });
      });
    });

    function renderDashboard() {
      document.getElementById('stat-kriteria').textContent = appState.kriteria.length;
      document.getElementById('stat-guru').textContent = appState.guru.length;
      document.getElementById('stat-bobot').textContent = number4.format(appState.totalBobot);
      document.getElementById('stat-top').textContent = appState.hasil[0] ? `${appState.hasil[0].nama_guru} (${number4.format(appState.hasil[0].preferensi)})` : '-';

      const warning = document.getElementById('dashboard-warning');
      if (appState.bobotValid) warning.classList.add('hidden');
      else {
        warning.textContent = `Total bobot saat ini ${number4.format(appState.totalBobot)}. Disarankan kembali ke 1.0000 agar perhitungan akurat.`;
        warning.classList.remove('hidden');
      }

      document.getElementById('top-3-preview').innerHTML = appState.hasil.slice(0, 3).map((row) => `
        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
          <div>
            <div class="text-sm font-semibold text-slate-900">${escapeHtml(row.nama_guru)}</div>
            <div class="text-xs text-slate-500">Ranking #${row.ranking}</div>
          </div>
          <div class="rounded-full bg-white px-3 py-1 text-sm font-bold text-primary-700 shadow-sm">${number4.format(row.preferensi)}</div>
        </div>
      `).join('') || '<div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Belum ada data.</div>';
    }

    function renderCriteriaTable() {
      document.getElementById('criteria-table').innerHTML = appState.kriteria.map((row) => `
        <tr>
          <td class="px-4 py-3 font-semibold text-slate-900">${escapeHtml(row.kode)}</td>
          <td class="px-4 py-3 text-slate-700">${escapeHtml(row.nama_kriteria)}</td>
          <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold ${row.sifat === 'Benefit' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${escapeHtml(row.sifat)}</span></td>
          <td class="px-4 py-3 text-slate-700">${number4.format(Number(row.bobot))}</td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-2">
              ${isAdmin() ? `<button class="edit-kriteria rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" data-id="${row.id_kriteria}">Edit</button><button class="delete-kriteria rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" data-id="${row.id_kriteria}">Hapus</button>` : '<span class="text-xs text-slate-400">Read only</span>'}
            </div>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada kriteria.</td></tr>';
    }

    function renderGuruFields(selectedGuru = null) {
      const fields = document.getElementById('guru-value-fields');
      if (!appState.kriteria.length) {
        fields.innerHTML = '<div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">Tambahkan kriteria terlebih dahulu.</div>';
        return;
      }

      fields.innerHTML = appState.kriteria.map((row) => {
        const val = selectedGuru ? (selectedGuru.nilai?.[String(row.id_kriteria)] ?? '') : '';
        return `
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">${escapeHtml(row.kode)} - ${escapeHtml(row.nama_kriteria)} (${escapeHtml(row.sifat)})</label>
            <input type="number" step="0.0001" min="0" name="nilai[${row.id_kriteria}]" value="${val}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:ring-1 focus:ring-primary-500" placeholder="0.0000" required>
          </div>
        `;
      }).join('');
    }

    function renderGuruTable() {
      document.getElementById('guru-table').innerHTML = appState.guru.map((row) => `
        <tr>
          <td class="px-4 py-3 font-semibold text-slate-900">${escapeHtml(row.nama_guru)}</td>
          <td class="px-4 py-3"><button type="button" class="toggle-guru-detail inline-flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-100" data-id="${row.id_guru}"><i data-lucide="eye" class="h-3.5 w-3.5"></i> Lihat Nilai</button></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-2">
              ${isAdmin() ? `<button class="edit-guru rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" data-id="${row.id_guru}">Edit</button><button class="delete-guru rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" data-id="${row.id_guru}">Hapus</button>` : '<span class="text-xs text-slate-400">Read only</span>'}
            </div>
          </td>
        </tr>
        <tr id="guru-detail-${row.id_guru}" class="hidden bg-slate-50">
          <td colspan="3" class="px-4 py-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              ${appState.kriteria.map((k) => `
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                  <div class="text-xs font-semibold uppercase text-slate-500">${escapeHtml(k.kode)} - ${escapeHtml(k.nama_kriteria)}</div>
                  <div class="mt-2 text-lg font-bold text-primary-700">${row.nilai?.[String(k.id_kriteria)] || 0}</div>
                </div>
              `).join('')}
            </div>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada data guru.</td></tr>';
      lucide.createIcons();
    }

    function renderSawTable() {
      const wrap = document.getElementById('saw-table-wrap');
      if (!appState.hasil.length) {
        wrap.innerHTML = '<div class="px-4 py-10 text-center text-sm text-slate-500">Belum ada hasil perhitungan.</div>';
        return;
      }

      const getPembandingValues = (kriteriaId) => {
        const values = appState.guru
          .map((guru) => Number(guru.nilai?.[String(kriteriaId)] ?? 0))
          .filter((value) => Number.isFinite(value));

        return {
          max: values.length ? Math.max(...values) : 0,
          min: values.length ? Math.min(...values) : 0,
        };
      };

      const renderDetailRows = (row) => {
        const detailRows = (row.detail || []).map((item) => `
          ${(() => {
            const pembanding = getPembandingValues(item.id_kriteria);
            const pembandingValue = item.sifat === 'Cost' ? pembanding.min : pembanding.max;
            const formulaText = item.sifat === 'Cost'
              ? `min(${number4.format(pembanding.min)}) / ${number4.format(Number(item.nilai_mentah || 0))}`
              : `${number4.format(Number(item.nilai_mentah || 0))} / max(${number4.format(pembanding.max)})`;

            return `
          <tr>
            <td class="px-4 py-4">
              <div class="font-semibold text-slate-900">${escapeHtml(item.kode)}</div>
              <div class="text-xs text-slate-500">${escapeHtml(item.nama_kriteria)}</div>
            </td>
            <td class="px-4 py-4">
              <span class="rounded-full px-3 py-1 text-xs font-semibold ${item.sifat === 'Benefit' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${escapeHtml(item.sifat)}</span>
            </td>
            <td class="px-4 py-4 font-semibold text-slate-800">${number4.format(Number(item.nilai_mentah || 0))}</td>
            <td class="px-4 py-4 text-slate-700">
              <div class="font-medium">${escapeHtml(formulaText)}</div>
              <div class="text-xs text-slate-500">${item.sifat === 'Cost' ? 'Normalisasi cost = min / nilai' : 'Normalisasi benefit = nilai / max'}</div>
            </td>
            <td class="px-4 py-4 text-slate-700">
              <div class="font-semibold text-slate-800">${number4.format(pembandingValue)}</div>
              <div class="text-xs text-slate-500">${item.sifat === 'Cost' ? 'Min pembanding' : 'Max pembanding'}</div>
            </td>
            <td class="px-4 py-4 font-semibold text-slate-800">${number4.format(Number(item.nilai_normalisasi || 0))}</td>
            <td class="px-4 py-4 font-semibold text-slate-800">${number4.format(Number(item.bobot || 0))}</td>
            <td class="px-4 py-4 font-semibold text-primary-700">${number4.format(Number(item.kontribusi || 0))}</td>
          </tr>
            `;
          })()}
        `).join('');

        const formulaTerms = (row.detail || []).map((item) => `(${number4.format(Number(item.bobot || 0))} x ${number4.format(Number(item.nilai_normalisasi || 0))})`);

        return `
          <tr id="saw-detail-${row.id_guru}" class="hidden bg-slate-50">
            <td colspan="4" class="px-4 py-4">
              <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <h3 class="text-base font-bold text-slate-900">Tahap Perhitungan ${escapeHtml(row.nama_guru)}</h3>
                    <p class="mt-1 text-sm text-slate-500">Nilai preferensi dihitung dari penjumlahan bobot x normalisasi tiap kriteria.</p>
                  </div>
                  <button type="button" class="toggle-saw-detail rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50" data-id="${row.id_guru}">Tutup</button>
                </div>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-100">
                  <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                      <tr>
                        <th class="px-4 py-3 font-semibold">Kriteria</th>
                        <th class="px-4 py-3 font-semibold">Sifat</th>
                        <th class="px-4 py-3 font-semibold">Nilai Mentah</th>
                        <th class="px-4 py-3 font-semibold">Rumus Normalisasi</th>
                        <th class="px-4 py-3 font-semibold">Pembanding</th>
                        <th class="px-4 py-3 font-semibold">Normalisasi</th>
                        <th class="px-4 py-3 font-semibold">Bobot</th>
                        <th class="px-4 py-3 font-semibold">Kontribusi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      ${detailRows || '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Detail perhitungan tidak tersedia.</td></tr>'}
                    </tbody>
                  </table>
                </div>

                <div class="mt-5 rounded-2xl bg-primary-50 px-4 py-4 text-sm text-slate-700">
                  <div class="font-semibold text-primary-700">Rumus akhir</div>
                  <div class="mt-2 leading-7">
                    Preferensi = ${formulaTerms.join(' + ') || '(0,0000 x 0,0000)'} = <span class="font-bold text-slate-900">${number4.format(Number(row.preferensi || 0))}</span>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        `;
      };

      wrap.innerHTML = `
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-slate-600">
            <tr>
              <th class="px-4 py-3 font-semibold">Ranking</th>
              <th class="px-4 py-3 font-semibold">Guru</th>
              <th class="px-4 py-3 font-semibold">Preferensi (V)</th>
              <th class="px-4 py-3 font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            ${appState.hasil.map((row) => `
              <tr class="align-top ${row.ranking === 1 ? 'bg-emerald-50/40' : ''}">
                <td class="px-4 py-3 font-bold text-primary-700">#${row.ranking}</td>
                <td class="px-4 py-3 text-slate-800">${escapeHtml(row.nama_guru)}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">${number4.format(row.preferensi)}</td>
                <td class="px-4 py-3">
                  <button type="button" class="toggle-saw-detail inline-flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-100" data-id="${row.id_guru}">
                    <i data-lucide="eye" class="h-3.5 w-3.5"></i> Detail
                  </button>
                </td>
              </tr>
              ${renderDetailRows(row)}
            `).join('')}
          </tbody>
        </table>
      `;
      lucide.createIcons();
    }

    function updateReportSection() { 
      const currentDate = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date());
      // Tambahkan format hari dan tanggal lengkap
      const fullDateStr = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }).format(new Date()).replace(/,/, '');
      const cityDateText = `Bekasi, ${fullDateStr}`; // Ganti 'Depok' dengan kota yang sesuai
     // Update global text di laporan
      document.querySelectorAll('.report-date').forEach(el => el.textContent = currentDate);
      document.querySelectorAll('.report-city-date').forEach(el => el.textContent = cityDateText);
      document.querySelectorAll('.report-total-guru').forEach(el => el.textContent = appState.guru.length);
      document.querySelectorAll('.report-total-kriteria').forEach(el => el.textContent = appState.kriteria.length);

      // 1. Laporan Hasil
      document.getElementById('report-table-hasil').innerHTML = appState.hasil.map((row) => `
        <tr>
          <td class="px-4 py-3 font-bold text-primary-700">#${row.ranking}</td>
          <td class="px-4 py-3 text-slate-800">${escapeHtml(row.nama_guru)}</td>
          <td class="px-4 py-3 font-semibold text-slate-900">${number4.format(row.preferensi)}</td>
          <td class="px-4 py-3">${row.ranking === 1 ? '<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Terbaik</span>' : '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Alternatif</span>'}</td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada hasil untuk dicetak.</td></tr>';

      // 2. Laporan Kriteria
      document.getElementById('report-table-kriteria').innerHTML = appState.kriteria.map((row, i) => `
        <tr>
          <td class="px-4 py-3">${i + 1}</td>
          <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(row.kode)}</td>
          <td class="px-4 py-3">${escapeHtml(row.nama_kriteria)}</td>
          <td class="px-4 py-3">${escapeHtml(row.sifat)}</td>
          <td class="px-4 py-3">${number4.format(row.bobot)}</td>
        </tr>
      `).join('') || '<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data kriteria.</td></tr>';

      // 3. Laporan Guru
      document.getElementById('report-table-guru').innerHTML = appState.guru.map((row, i) => `
        <tr>
          <td class="px-4 py-3">${i + 1}</td>
          <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(row.nama_guru)}</td>
        </tr>
      `).join('') || '<tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">Belum ada data guru.</td></tr>';

      // 4. Laporan Rekap Nilai Mentah
      const headMentah = `<tr>
        <th class="px-4 py-3 font-semibold">No</th>
        <th class="px-4 py-3 font-semibold">Nama Guru</th>
        ${appState.kriteria.map(k => `<th class="px-4 py-3 font-semibold" title="${escapeHtml(k.nama_kriteria)}">${escapeHtml(k.kode)}</th>`).join('')}
      </tr>`;
      document.getElementById('report-head-mentah').innerHTML = headMentah;

      document.getElementById('report-table-mentah').innerHTML = appState.guru.map((row, i) => `
        <tr>
          <td class="px-4 py-3">${i + 1}</td>
          <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(row.nama_guru)}</td>
          ${appState.kriteria.map(k => `<td class="px-4 py-3">${row.nilai?.[String(k.id_kriteria)] || 0}</td>`).join('')}
        </tr>
      `).join('') || `<tr><td colspan="${appState.kriteria.length + 2}" class="px-4 py-8 text-center text-slate-500">Belum ada data nilai mentah.</td></tr>`;
    }

    async function loadAll() {
      const data = await apiRequest('bootstrap');
      appState.kriteria = data.kriteria || [];
      appState.guru = data.guru || [];
      appState.hasil = data.hasil || [];
      appState.totalBobot = Number(data.total_bobot || 0);
      appState.bobotValid = Boolean(data.bobot_valid);
      if (data.user) {
        appState.user = data.user;
        appState.role = data.role || data.user.role;
      }
      renderDashboard(); renderCriteriaTable(); renderGuruTable(); renderGuruFields(); renderSawTable(); updateReportSection(); applyPermissions(); lucide.createIcons();
    }

    // Event Listeners Dasar
    tabButtons.forEach((button) => {
      button.addEventListener('click', () => setActiveTab(button.dataset.tab));
    });

    document.getElementById('btn-show-register').addEventListener('click', () => {
      loginContainer.classList.add('hidden');
      registerContainer.classList.remove('hidden');
    });

    document.getElementById('btn-cancel-register').addEventListener('click', () => {
      loginContainer.classList.remove('hidden');
      registerContainer.classList.add('hidden');
    });

    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const data = await apiRequest('login', new FormData(e.target));
        setAuthState(data.user);
        await loadAll();
        setActiveTab('dashboard');
        showToast('success', 'Login berhasil.');
      } catch (err) { showToast('error', err.message); }
    });

    document.getElementById('register-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await apiRequest('register', new FormData(e.target));
        showToast('success', 'Registrasi berhasil! Silakan masuk dengan akun baru.');
        document.getElementById('btn-cancel-register').click();
        e.target.reset();
      } catch (err) { showToast('error', err.message); }
    });

    document.getElementById('btn-logout').addEventListener('click', async () => {
      try {
        await apiRequest('logout');
        setAuthState(null);
        showToast('info', 'Anda telah logout.');
      } catch (err) { showToast('error', err.message); }
    });

    document.getElementById('btn-refresh').addEventListener('click', async () => { await loadAll(); showToast('success', 'Data dimuat ulang.'); });
    document.getElementById('btn-refresh-saw').addEventListener('click', async () => {
      try {
        const data = await apiRequest('saw');
        appState.hasil = data.hasil || [];
        renderSawTable(); updateReportSection();
        showToast('success', 'Perhitungan SAW diperbarui.');
      } catch (error) { showToast('error', error.message); }
    });

    document.getElementById('btn-print').addEventListener('click', () => window.print());
    document.getElementById('btn-download-pdf').addEventListener('click', () => {
      try { buildPdfReport(); showToast('success', 'PDF berhasil dibuat.'); } catch (err) { showToast('error', err.message); }
    });

    document.getElementById('btn-kriteria-baru').addEventListener('click', () => {
      if (!isAdmin()) { showToast('warning', 'Mode viewer tidak bisa ubah data.'); return; }
      resetCriteriaForm();
    });
    function resetCriteriaForm() {
      document.getElementById('criteria-form').reset();
      document.getElementById('criteria-id').value = '';
      document.getElementById('criteria-form-title').textContent = 'Tambah Kriteria';
    }
    document.getElementById('criteria-cancel').addEventListener('click', resetCriteriaForm);

    document.getElementById('criteria-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await apiRequest('save_kriteria', new FormData(e.target));
        showToast('success', 'Kriteria disimpan.');
        await loadAll();
        resetCriteriaForm();
      } catch (err) { showToast('error', err.message); }
    });

    document.getElementById('btn-guru-baru').addEventListener('click', () => {
      if (!isAdmin()) { showToast('warning', 'Mode viewer tidak bisa ubah data.'); return; }
      resetGuruForm();
    });
    function resetGuruForm() {
      document.getElementById('guru-form').reset();
      document.getElementById('guru-id').value = '';
      document.getElementById('guru-form-title').textContent = 'Tambah Data Guru';
      renderGuruFields();
      applyPermissions(); // field baru harus ikut terkunci untuk viewer
    }
    document.getElementById('guru-cancel').addEventListener('click', resetGuruForm);

    document.getElementById('guru-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await apiRequest('save_guru', new FormData(e.target));
        showToast('success', 'Data guru disimpan.');
        await loadAll();
        resetGuruForm();
      } catch (err) { showToast('error', err.message); }
    });

    document.addEventListener('click', async (e) => {
      const el = e.target;
      if (el.closest('.edit-kriteria')) {
        const row = appState.kriteria.find((item) => String(item.id_kriteria) === el.closest('.edit-kriteria').dataset.id);
        if (row) {
          document.getElementById('criteria-id').value = row.id_kriteria;
          document.getElementById('criteria-kode').value = row.kode;
          document.getElementById('criteria-nama').value = row.nama_kriteria;
          document.getElementById('criteria-sifat').value = row.sifat;
          document.getElementById('criteria-bobot').value = row.bobot;
          document.getElementById('criteria-form-title').textContent = 'Edit Kriteria';
        }
      }
      if (el.closest('.delete-kriteria')) {
        if (!confirm('Hapus kriteria ini?')) return;
        const fd = new FormData(); fd.append('id_kriteria', el.closest('.delete-kriteria').dataset.id);
        try { await apiRequest('delete_kriteria', fd); await loadAll(); showToast('success', 'Terhapus.'); } catch (err) { showToast('error', err.message); }
      }
      if (el.closest('.edit-guru')) {
        const row = appState.guru.find((item) => String(item.id_guru) === el.closest('.edit-guru').dataset.id);
        if (row) {
          document.getElementById('guru-id').value = row.id_guru;
          document.getElementById('guru-nama').value = row.nama_guru;
          document.getElementById('guru-form-title').textContent = 'Edit Data Guru';
          renderGuruFields(row);
        }
      }
      if (el.closest('.delete-guru')) {
        if (!confirm('Hapus guru ini?')) return;
        const fd = new FormData(); fd.append('id_guru', el.closest('.delete-guru').dataset.id);
        try { await apiRequest('delete_guru', fd); await loadAll(); showToast('success', 'Terhapus.'); } catch (err) { showToast('error', err.message); }
      }
      if (el.closest('.toggle-guru-detail')) {
        document.getElementById(`guru-detail-${el.closest('.toggle-guru-detail').dataset.id}`).classList.toggle('hidden');
      }
      if (el.closest('.toggle-saw-detail')) {
        document.getElementById(`saw-detail-${el.closest('.toggle-saw-detail').dataset.id}`).classList.toggle('hidden');
      }
    });

    async function bootstrap() {
      try {
        if (appState.authenticated) {
          setAuthState(appState.user);
          await loadAll();
        } else {
          loginScreen.classList.remove('auth-hidden');
          appShell.classList.add('auth-hidden');
        }
      } catch (err) { showToast('error', err.message); }
    }
    bootstrap();
  </script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then((reg) => console.log('Service Worker registered with scope:', reg.scope))
          .catch((err) => console.error('Service Worker registration failed:', err));
      });
    }
  </script>
</body>
</html>