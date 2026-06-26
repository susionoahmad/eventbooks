# UI Wireframe Specification - EventBooks

This document details the visual layouts and key UI/UX flows of the EventBooks frontend. The application uses a modern responsive grid, high-fidelity dark mode toggles, and sliding panels (Drawers) for micro-interactions.

---

## 1. Global Admin Layout (Sidebar & Navbar)
A collapsible left sidebar containing primary navigation links, with a top toolbar for profile settings, search, and a light/dark mode toggler.

```
+------------------------------------------------------------------------------------------+
|  [Logo] EventBooks     |  [Search Event...]                  (Dark Mode)  [User Profile] |
|------------------------+-----------------------------------------------------------------|
|  (a) Dashboard         |                                                                 |
|  (b) Master Data    v  |   [MAIN VIEW CONTAINER]                                         |
|      - Klien           |   Loads DashboardView, EventView, RABView, etc.                 |
|      - Vendor          |                                                                 |
|  (c) Event Organizer   |                                                                 |
|  (d) RAB & Budgeting   |                                                                 |
|  (e) Buku Kas (Ledger) |                                                                 |
|  (f) Hutang & Piutang  |                                                                 |
|  (g) Rekap Pajak       |                                                                 |
|  (h) Laporan Keuangan  |                                                                 |
|                        |                                                                 |
|  [<< Collapse Sidebar] |                                                                 |
+------------------------------------------------------------------------------------------+
```

---

## 2. Dashboard View
Displays high-level KPI cards, real-time Cash Flow charts, and event profitability lists.

```
+------------------------------------------------------------------------------------------+
|  Dashboard Overview                                                [Filter: Bulan Ini v] |
|                                                                                          |
|  +------------------+  +------------------+  +------------------+  +------------------+  |
|  |  Event Aktif     |  |  Pendapatan      |  |  Laba Bersih     |  |  Pajak Terutang  |  |
|  |  5 Event         |  |  Rp 350.000.000  |  |  Rp 170.000.000  |  |  Rp 18.500.000   |  |
|  |  [+3 dari bln lalu]|  |  [+12% vs target] |  |  [Margin 48.5%]  |  |  [Jatuh Tempo:15]|  |
|  +------------------+  +------------------+  +------------------+  +------------------+  |
|                                                                                          |
|  +--------------------------------------------+  +------------------------------------+  |
|  |  Cash Flow Chart (In vs Out)               |  |  Profit per Event (Top margin %)   |  |
|  |                                            |  |                                    |  |
|  |  300M |      __*---*                       |  |  1. Wedding Budi (46.7% / 116M)    |  |
|  |  200M |   *-*       *                      |  |  2. Konser Jazz  (32.1% / 95M)     |  |
|  |  100M |  *           *                     |  |  3. Agency Expo  (18.2% / 14M)     |  |
|  |    0M +-------------------                 |  |                                    |  |
|  |         Jan   Feb   Mar                    |  |  [Lihat Selengkapnya]              |  |
|  +--------------------------------------------+  +------------------------------------+  |
|                                                                                          |
|  +------------------------------------------------------------------------------------+  |
|  |  Piutang Klien (Jatuh Tempo Terdekat)                                              |  |
|  |  - INV-2026-0045 | PT Sentosa Abadi | Rp 111.000.000 | JT: 05-07-2026 | [Kirim Email]|  |
|  |  - INV-2026-0048 | CV Tunas Mulia   | Rp 45.000.000  | JT: 12-07-2026 | [Kirim Email]|  |
|  +------------------------------------------------------------------------------------+  |
+------------------------------------------------------------------------------------------+
```

---

## 3. RAB & Budget Builder View
The budgeting workspace allows editing estimated items side-by-side with actual expenses.

```
+------------------------------------------------------------------------------------------+
|  Detail Event: Wedding Ceremony Budi & Rina                              [Status: DP]   |
|  [Detail]  [RAB Anggaran]  [Transaksi Buku Kas]  [Invoice Klien]  [Lampiran Dokumen]     |
|                                                                                          |
|  Summary RAB:                                                                            |
|  Nilai Kontrak: Rp 250.000.000 | Total Anggaran: Rp 150.000.000 | Realisasi: Rp 45.000.000|
|                                                                                          |
|  +------------------------------------------------------------------------------------+  |
|  | Kategori     | Deskripsi               | Qty | Harga        | Subtotal  | Realisasi|  |
|  +--------------+-------------------------+-----+--------------+-----------+----------+  |
|  | Catering     | Buffet Prasmanan        | 500 | Rp   150.000 | Rp 75.00M | Rp 45.00M|  |
|  | Sound System | Sewa Sound 10K Watt     |   1 | Rp 15.000.000 | Rp 15.00M | Rp  0.00 |  |
|  | Lighting     | Moving Head & LED       |   1 | Rp 12.000.000 | Rp 12.00M | Rp  0.00 |  |
|  +------------------------------------------------------------------------------------+  |
|  | [+ Tambah Item Anggaran]                                    | Selisih: Rp 105.000.000  |
|  +------------------------------------------------------------------------------------+  |
|                                                                                          |
|  Actions: [Simpan Revisi]   [Export RAB Excel]   [Export RAB PDF]                        |
+------------------------------------------------------------------------------------------+
```

---

## 4. Invoice Generator & Tax Component View
Integrated form where applying client tax additions is computed in real-time.

```
+------------------------------------------------------------------------------------------+
|  Buat Invoice Baru                                                                       |
|                                                                                          |
|  Pilih Event   : [Wedding Ceremony Budi & Rina v]                                         |
|  Pilih Client  : Riana Indah (PT Sentosa Abadi)                                          |
|  Termin Invoice: [DP / Down Payment v]                                                   |
|                                                                                          |
|  +------------------------------------------------------------------------------------+  |
|  | Keterangan Item                                      | Nominal/Subtotal            |
|  +------------------------------------------------------+-----------------------------+  |
|  | Pembayaran DP sebesar 40% dari total nilai kontrak   | Rp 100.000.000              |
|  +------------------------------------------------------+-----------------------------+  |
|                                                                                          |
|  Opsi Pajak:                                                                             |
|  [x] Kenakan PPN (11%)                                  | PPN: Rp 11.000.000          |
|                                                                                          |
|  --------------------------------------------------------------------------------------  |
|  Total Tagihan                                          | Rp 111.000.000              |
|  --------------------------------------------------------------------------------------  |
|                                                                                          |
|  Jatuh Tempo: [ 2026-07-05 ]                                                             |
|                                                                                          |
|  [Batal]                                                  [Simpan & Generate Invoice PDF]|
+------------------------------------------------------------------------------------------+
```
