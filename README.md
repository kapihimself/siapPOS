<div align="center">

# SiapPOS 🛒

**Sistem POS Open Source berbasis PHP Vanilla, dirancang dengan skalabilitas SaaS Multi-Tenant.**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-SQLite-003B57?logo=sqlite)](https://sqlite.org/)
[![Architecture](https://img.shields.io/badge/Architecture-DDD-success)](#)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

</div>

<br>

## 🚀 Apa itu SiapPOS?

SiapPOS adalah rekonstruksi modern dari arsitektur *monolithic* sistem POS Enterprise tradisional (seperti UltimatePOS). Alih-alih bergantung pada framework berat yang memakan resource server (seperti "Fat Controllers" di Laravel lama), SiapPOS direkayasa menggunakan **Vanilla PHP 8+** dengan pendekatan **Domain-Driven Design (DDD)**, menghasilkan sistem point-of-sale yang luar biasa ringan, dapat di-host secara mandiri, dan sangat *maintainable*.

Sistem ini didesain sebagai **SaaS Multi-Tenant**. Setiap fitur mulai dari manajemen stok, penjualan, sampai pengaturan otorisasi kasir beroperasi dalam ranah isolasi penyewa (Business ID).

---

## 💡 Fitur Unggulan

- **🏢 Arsitektur SaaS (Multi-Tenancy):** Dibangun dengan prinsip Single Database / Shared Schema. Setiap tenant/bisnis benar-benar terisolasi pada level query (menggunakan `business_id`).
- **📦 Inventory & FIFO Tracking:** Mendukung Single & Variable Products dengan perhitungan harga pokok (*Cost of Goods Sold/COGS*) menggunakan sistem FIFO (*First-In-First-Out*) yang ketat melalui skema pemetaan batch pembelian (Purchase Lots).
- **🛒 POS Terminal (SPA Offline-First):** Layar kasir dibangun menggunakan *Vanilla JS* sebagai Single Page Application, mengurangi beban request HTML dan meminimalkan *network latency*.
- **🧾 Double-Entry Accounting:** Sistem tidak hanya mencatat transaksi penjualan, tapi otomatis memposting entri debit & kredit ke buku besar (*general ledger*) untuk kalkulasi laporan Laba/Rugi (*Profit & Loss*) yang absolut presisi.
- **🍔 Add-on F&B (KDS & Modifiers):** Mendukung manajemen variasi tambahan (contoh: Extra Cheese) dan sistem *Kitchen Display System (KDS)* lengkap dengan *polling* audio untuk pesanan baru.
- **⚡ Background Workers (Event-Driven):** Event core (*Checkout*, *Refund*) diproses secara asinkron (misalnya mengirim tagihan publik ke email) melalui worker script dan tabel `jobs`.

---

## 📸 Antarmuka Pengguna

<div align="center">
  <img src="docs/screenshots/login.png" width="48%" alt="Halaman Login">
  <img src="docs/screenshots/dashboard.png" width="48%" alt="Halaman Dashboard">
</div>

---

## 🛠 Tech Stack & Engine

- **Backend:** Vanilla PHP 8+ (No bloatware, performa maksimal)
- **Database:** SQLite (Dapat dengan mudah dimigrasi ke MySQL/PostgreSQL lewat koneksi `PDO`)
- **Arsitektur:**
  - **Domain-Driven Design (DDD):** Memisahkan domain `Inventory`, `Transaction`, `Accounting`, dll.
  - **Single Table Inheritance (STI):** Menggunakan tabel `transactions` untuk berbagai tipe gerakan (`sell`, `purchase`, `expense`) dengan polimorfisme untuk efisiensi laporan finansial.
  - **Pub/Sub Event Bus:** Sistem responsif melalui *event dispatching*.
- **Frontend:** Vanilla JS SPA (Terminal POS) & Bootstrap CSS (Dashboard Admin).

---

## 🚦 Getting Started

Jalankan SiapPOS di *local environment* Anda dalam kurang dari 10 detik.

1. **Clone Repository**
   ```bash
   git clone https://github.com/your-username/siappos.git
   cd siappos
   ```

2. **Start the Engine & Worker**
   Sistem terpadu di *routing script* tunggal `public/index.php`. Jalankan Web Server dan Background Queue Worker di terminal yang berbeda:

   **Terminal 1 (Web Server):**
   ```bash
   php -S 127.0.0.1:8088 -t public
   ```

   **Terminal 2 (Queue Worker):**
   ```bash
   php src/worker.php
   ```

3. **Akses Aplikasi**
   Buka web browser dan arahkan ke: `http://127.0.0.1:8088/`

### 🔑 Kredensial Demo (Role-Based Access Control)

Sistem akan otomatis *seeding* data jika file SQLite baru dibuat. Berikut adalah kredensial demo untuk menguji sistem RBAC:

| Role | Username | PIN | Akses Kemampuan |
|---|---|---|---|
| **Owner / Admin** | `owner` | `1111` | Akses penuh, pengaturan SaaS, laporan keuangan utama |
| **Manager** | `manager` | `2222` | Manajemen stok produk, otorisasi KDS restoran |
| **Cashier** | `cashier` | `3333` | Mengakses terminal POS, memproses pelanggan |

---

## 🤝 Berkontribusi (Contributing)

Kami sangat menyambut baik *Pull Requests*! Jika Anda ingin berkontribusi, harap ikuti panduan berikut:
1. Fork repositori ini.
2. Buat *branch* fitur Anda (`git checkout -b feat/NamaFiturAnda`).
3. Commit perubahan Anda, pastikan mengikuti konvensi standard.
4. Lakukan pengecekan kode dengan *linter* (`find src public views -name '*.php' -exec php -l {} \;`).
5. Buat sebuah *Pull Request* dan jelaskan perubahan yang Anda buat.

Untuk diskusi *architecture decisions*, silakan buka halaman **Issues**.

---

<div align="center">
  <i>SiapPOS — Open Source, Lightweight, & Enterprise-Ready.</i>
</div>
