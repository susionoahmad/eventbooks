# EventBooks - Portal Keuangan Event Organizer

EventBooks adalah platform pembukuan, keuangan, dan manajemen pajak terintegrasi yang dirancang khusus untuk **Event Organizers (EO)**, **Wedding Organizers (WO)**, **Concert/Festival Organizers**, dan **Creative Agencies**. Aplikasi ini memungkinkan organisasi mengelola anggaran event (RAB), memantau kewajiban vendor (Hutang), piutang klien, profitabilitas event secara real-time, serta kewajiban perpajakan (PPN & PPh) dalam satu dasbor terpadu.

---

## 🚀 Teknologi yang Digunakan

### Backend (API)
- **Framework**: Laravel 11 (PHP)
- **Autentikasi**: Laravel Sanctum (Token-Based API Auth)
- **Database**: MySQL / MariaDB
- **Lokasi Kode**: `/backend`

### Frontend (SPA & PWA)
- **Framework**: Vue 3 (Composition API dengan `<script setup lang="ts">`)
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router
- **Desain & UI**: Tailwind CSS (Desain premium dengan dukungan Dark Mode & Glassmorphism)
- **PWA**: Native Service Worker dengan Manifest terintegrasi (siap di-build via PWA Builder)
- **Lokasi Kode**: `/frontend`

---

## 🛠️ Fitur Utama

- **Manajemen Multi-Tenant & RBAC**: Mendukung isolasi data organisasi dengan Role-Based Access Control (`Owner`, `Finance Manager`, `Admin`, `Event Staff`).
- **Dashboard & Analisis Keuangan**: Tampilan dasbor interaktif dengan grafik arus kas, ringkasan saldo kas, sisa hutang/piutang, serta rangkuman laba rugi.
- **Penyusunan RAB (Budget Builder)**: Pembuatan dan pengelolaan Rencana Anggaran Biaya (RAB) per event beserta persetujuan revisi anggaran.
- **Pencatatan Buku Kas (Ledger Bookkeeping)**: Buku besar transaksi pemasukan dan pengeluaran kas yang dapat difilter per event.
- **Faktur & Pembayaran (Invoicing)**: Pembuatan invoice pembayaran bertahap (termin) dengan pencatatan status pelunasan otomatis.
- **Perpajakan Indonesia**: Pelacakan otomatis Pajak Pertambahan Nilai (PPN 11%) serta kalkulator Pajak Penghasilan (PPh Pasal 21 & PPh Pasal 23).
- **Penguncian Masa Trial 30 Hari**: Fitur keamanan untuk mengunci akses portal secara otomatis saat masa percobaan gratis organisasi berakhir, disertai dasbor interaktif pembaruan status.
- **Progressive Web App (PWA)**: Aplikasi dapat diinstal langsung di HP/Desktop dengan Service Worker dan Manifest teruji, siap dibungkus menjadi aplikasi native via PWA Builder.

---

## 📦 Struktur Direktori

```text
PROJECT/EO/
├── backend/            # Aplikasi Laravel API
│   ├── app/            # Controller, Middleware, Models, dll.
│   ├── bootstrap/      # Bootstrapping & Cache Laravel
│   ├── config/         # File konfigurasi Laravel
│   ├── database/       # Migrasi DB & Seeders
│   ├── routes/         # Definisi rute API v1
│   └── tests/          # Unit & Integration Tests
├── frontend/           # Aplikasi Vue 3 SPA + PWA
│   ├── public/         # PWA Manifest, Service Worker, & Icons
│   ├── src/            # Komponen Vue, Layout, Views, Pinia Stores
│   ├── tsconfig.json   # Konfigurasi TypeScript
│   └── vercel.json     # Konfigurasi rewrite rute SPA untuk deployment Vercel
└── README.md           # Dokumentasi Proyek
```

---

## ⚙️ Petunjuk Pemasangan Lokal

### Prasyarat
- PHP >= 8.2 & Composer
- Node.js >= 18 & npm
- MySQL / MariaDB Server

### 1. Pengaturan Backend (Laravel API)
1. Masuk ke direktori backend:
   ```bash
   cd backend
   ```
2. Instal dependensi PHP:
   ```bash
   composer install
   ```
3. Salin konfigurasi `.env` dan buat App Key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Sesuaikan konfigurasi database Anda di `.env` (misal: `DB_DATABASE=eo_db`, `DB_USERNAME=root`, `DB_PASSWORD=`).
5. Jalankan migrasi dan isi data awal database (termasuk user demo):
   ```bash
   php artisan migrate:fresh --seed
   ```
6. Jalankan server pengembangan lokal backend (berjalan di port `8000`):
   ```bash
   php artisan serve
   ```

**Akun Demo Tersemai (Seeded Accounts):**
- **Owner**: `owner@eventbooks.com` (password: `password`)
- **Finance Manager**: `finance@eventbooks.com` (password: `password`)
- **Admin**: `admin@eventbooks.com` (password: `password`)
- **Staff**: `staff@eventbooks.com` (password: `password`)

### 2. Pengaturan Frontend (Vue 3 SPA)
1. Masuk ke direktori frontend:
   ```bash
   cd ../frontend
   ```
2. Instal dependensi Node:
   ```bash
   npm install
   ```
3. Jalankan server pengembangan lokal frontend:
   ```bash
   npm run dev
   ```
4. Buka browser pada alamat yang diberikan Vite (biasanya `http://localhost:5173` atau `http://localhost:5174`).

---

## 🌐 Panduan Deployment

### Frontend (di Vercel)
Aplikasi frontend telah dilengkapi dengan file [vercel.json](file:///d:/PROJECT/EO/frontend/vercel.json) yang berisi aturan penanganan rute Single Page Application:
```json
{
  "cleanUrls": true,
  "rewrites": [
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}
```
Untuk men-deploy ke Vercel:
1. Hubungkan repositori frontend Anda ke Vercel.
2. Atur **Build Command** menjadi `npm run build` dan **Output Directory** ke `dist`.
3. Tambahkan environment variable yang relevan jika ada (misal: API Base URL) dan klik **Deploy**.

---

## 📱 Kesiapan PWA & PWA Builder
Aplikasi frontend siap didistribusikan ke Google Play Store, Apple App Store, atau Microsoft Store menggunakan **PWA Builder**:
- **Manifest File**: Terletak pada [manifest.json](file:///d:/PROJECT/EO/frontend/public/manifest.json) untuk mendefinisikan nama PWA, warna, resolusi icon (`192x192` dan `512x512`), dan display mode (`standalone`).
- **Service Worker**: [sw.js](file:///d:/PROJECT/EO/frontend/public/sw.js) mengelola offline caching dan merespons event fetch, memenuhi kriteria instalasi PWA pada perangkat Android/iOS.
- **Pendaftaran SW**: Dilakukan secara native di dalam [index.html](file:///d:/PROJECT/EO/frontend/index.html).
