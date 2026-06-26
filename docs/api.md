# REST API Specification - EventBooks

This document details the REST API endpoints provided by the Laravel 12 backend for the EventBooks Vue 3 frontend. 

All API request and response bodies use JSON format. API routes are prefix-scoped with `/api/v1`.

---

## 1. Authentication Module

### 1.1 Login User
Authenticate users via email credentials and return a Sanctum API token.

- **Endpoint:** `POST /api/v1/auth/login`
- **Headers:** `Accept: application/json`
- **Request Body:**
  ```json
  {
    "email": "owner@eventbooks.com",
    "password": "password"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "token": "1|laravel_sanctum_token_hash_here",
    "user": {
      "id": 1,
      "name": "Alex Owner",
      "email": "owner@eventbooks.com",
      "role": "owner",
      "tenant": {
        "id": 1,
        "name": "Royal Event Organizer"
      }
    }
  }
  ```

---

## 2. Clients & Vendors Master

### 2.1 List Clients
Retrieve paginated list of clients scoped to the authenticated tenant.

- **Endpoint:** `GET /api/v1/clients`
- **Query Params:** `page=1`, `search=Royal`, `limit=15`
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 5,
        "kode_klien": "CLI-005",
        "nama": "Riana Indah",
        "perusahaan": "PT Sentosa Abadi",
        "npwp": "01.234.567.8-901.000",
        "email": "riana@sentosa.co.id",
        "telepon": "081234567890",
        "alamat": "Jl. Sudirman No. 45, Jakarta"
      }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "total": 1 }
  }
  ```

### 2.2 Create Client
- **Endpoint:** `POST /api/v1/clients`
- **Request Body:**
  ```json
  {
    "kode_klien": "CLI-006",
    "nama": "Budi Santoso",
    "perusahaan": "CV Tunas Mulia",
    "npwp": "02.345.678.9-012.000",
    "email": "budi@tunasmulia.com",
    "telepon": "081299998888",
    "alamat": "Jl. Gatot Subroto No. 12, Bandung"
  }
  ```
- **Response (211 Created):**
  ```json
  {
    "message": "Client created successfully",
    "data": { "id": 6, "kode_klien": "CLI-006", "nama": "Budi Santoso", ... }
  }
  ```

---

## 3. Events Module

### 3.1 List Events
- **Endpoint:** `GET /api/v1/events`
- **Query Params:** `status=berjalan`, `search=Wedding`
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 12,
        "nomor_event": "EV-2606001",
        "nama_event": "Wedding Ceremony Budi & Rina",
        "jenis_event": "Wedding",
        "tanggal_mulai": "2026-07-10",
        "tanggal_selesai": "2026-07-12",
        "lokasi": "Hotel Mulia Senayan, Jakarta",
        "nilai_kontrak": 250000000.00,
        "status": "dp",
        "client": {
          "id": 5,
          "nama": "Riana Indah",
          "perusahaan": "PT Sentosa Abadi"
        }
      }
    ]
  }
  ```

### 3.2 Fetch Event RAB (Rencana Anggaran Biaya)
Retrieve detailed budget items and budget execution statistics.

- **Endpoint:** `GET /api/v1/events/{id}/rab`
- **Response (200 OK):**
  ```json
  {
    "event_summary": {
      "id": 12,
      "nama_event": "Wedding Ceremony Budi & Rina",
      "nilai_kontrak": 250000000.00,
      "total_anggaran_rab": 150000000.00,
      "realisasi_biaya_aktual": 45000000.00,
      "selisih_sisa_anggaran": 105000000.00
    },
    "items": [
      {
        "id": 41,
        "kategori": "Catering",
        "deskripsi": "Prasmanan untuk 500 Pax",
        "qty": 500.00,
        "harga": 150000.00,
        "subtotal": 75000000.00,
        "aktual_terbayar": 75000000.00
      },
      {
        "id": 42,
        "kategori": "Sound System",
        "deskripsi": "Sewa Sound System 10.000 Watt",
        "qty": 1.00,
        "harga": 15000000.00,
        "subtotal": 15000000.00,
        "aktual_terbayar": 0.00
      }
    ]
  }
  ```

### 3.3 Add/Update RAB Item
- **Endpoint:** `POST /api/v1/events/{id}/rab` or `PUT /api/v1/events/{event_id}/rab/{item_id}`
- **Request Body:**
  ```json
  {
    "kategori": "Lighting",
    "deskripsi": "Moving Head & LED Par Lights rental",
    "qty": 1,
    "harga": 12000000
  }
  ```

---

## 4. Bookkeeping (Buku Kas)

### 4.1 Record Transaction (Kas Masuk/Keluar)
Post ledger items. If the transaction relates to a vendor payment, PPh 23 or PPh 21 can be withheld by the system automatically based on input checkboxes.

- **Endpoint:** `POST /api/v1/transactions`
- **Request Body:**
  ```json
  {
    "tanggal": "2026-06-25",
    "tipe": "kas_keluar",
    "kategori": "pembayaran_vendor",
    "event_id": 12,
    "vendor_id": 3,
    "deskripsi": "DP Sewa Lighting vendor CV Terang Jaya",
    "nominal": 5000000.00,
    "metode_pembayaran": "transfer_bank",
    "calculate_pph23": true,
    "vendor_has_npwp": true
  }
  ```
- **Response (211 Created):**
  ```json
  {
    "message": "Transaction recorded successfully",
    "data": {
      "id": 105,
      "nomor_transaksi": "TRX-2606022",
      "nominal": 5000000.00,
      "tax_withheld": {
        "tipe_pajak": "pph_23",
        "dpp": 5000000.00,
        "tarif": 2.00,
        "nominal_pajak": 100000.00,
        "net_payout": 4900000.00
      }
    }
  }
  ```

---

## 5. Invoicing Module

### 5.1 Create Invoice
Generate customer invoices. PPN (11%/12%) is applied if specified.

- **Endpoint:** `POST /api/v1/invoices`
- **Request Body:**
  ```json
  {
    "client_id": 5,
    "event_id": 12,
    "tanggal": "2026-06-25",
    "jatuh_tempo": "2026-07-05",
    "jenis_invoice": "dp",
    "subtotal": 100000000.00,
    "apply_ppn": true
  }
  ```
- **Response (211 Created):**
  ```json
  {
    "message": "Invoice created successfully",
    "data": {
      "id": 34,
      "nomor_invoice": "INV-2026-0045",
      "subtotal": 100000000.00,
      "ppn": 11000000.00,
      "total": 111000000.00,
      "status": "belum_bayar"
    }
  }
  ```

---

## 6. Financial Reports & Dashboard

### 6.1 Dashboard Overview Statistics
- **Endpoint:** `GET /api/v1/dashboard/summary`
- **Response (200 OK):**
  ```json
  {
    "total_event": 24,
    "event_aktif": 5,
    "pendapatan_bulan_ini": 350000000.00,
    "pengeluaran_bulan_ini": 180000000.00,
    "laba_bersih": 170000000.00,
    "piutang_klien": 120000000.00,
    "hutang_vendor": 45000000.00,
    "pajak_terutang": 18500000.00
  }
  ```

### 6.2 Event Profitability Rankings
- **Endpoint:** `GET /api/v1/dashboard/event-profitability`
- **Response (200 OK):**
  ```json
  [
    {
      "event_id": 12,
      "nama_event": "Wedding Ceremony Budi & Rina",
      "nilai_kontrak": 250000000.00,
      "total_biaya": 120000000.00,
      "pajak_dikeluarkan": 13200000.00,
      "laba_bersih": 116800000.00,
      "margin_persentase": 46.72,
      "ranking": 1
    }
  ]
  ```
