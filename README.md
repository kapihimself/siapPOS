<div align="center">

# SiapPOS 🛒

**Kasir Modern yang Cepat Dipakai, Rapi Datanya, dan Siap Tumbuh.**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-SQLite-003B57?logo=sqlite)](https://sqlite.org/)
[![Architecture](https://img.shields.io/badge/Architecture-DDD-success)](#)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

</div>

<br>

![Dashboard](docs/screenshots/dashboard_demo.png)

---

## 🚀 The Vision: Empowering Local Businesses

SiapPOS didesain untuk menjadi "urat nadi" operasional bisnis retail modern. Di tengah tingginya persaingan, pemilik bisnis (*Owner*) membutuhkan visibilitas data yang *real-time*, manajer membutuhkan kontrol inventaris yang akurat, dan kasir membutuhkan kecepatan pelayanan.

SiapPOS menghadirkan **Zero-Friction POS**, sebuah sistem point of sale yang tidak memerlukan instalasi infrastruktur rumit, namun memiliki *core architecture* kelas Enterprise (Domain-Driven Design). Kami percaya perangkat lunak operasional harus bekerja untuk Anda, bukan sebaliknya.

---

## 💡 Value Propositions (Mengapa SiapPOS?)

1. **Zero-Configuration Deployment**
   Lupakan proses setup server berhari-hari. SiapPOS dibangun agar bisa langsung di-*serve* (*plug-and-play*) menggunakan PHP *built-in server* dan SQLite. Tidak ada *dependency* eksternal. *Time-to-market* bisnis Anda menjadi hitungan menit.

2. **Enterprise-Grade Architecture (Domain-Driven Design)**
   Berbeda dengan sistem konvensional yang kodenya tumpang-tindih, SiapPOS diarsiteki layaknya produk kelas *Enterprise*:
   - Domain logic diisolasi (`Order`, `Product`, `Auth`, `Settings`).
   - Komunikasi antar modul (*bounded contexts*) dijalankan melalui tersentralisasi menggunakan **Event Bus Pub/Sub** Pattern.
   - Fleksibel: Kode saat ini menggunakan *Repository Pattern* untuk SQLite, yang siap diganti ke MySQL/PostgreSQL saat bisnis tumbuh *scaling*.

3. **Smart Business Onboarding**
   Sistem secara cerdas memahami jenis industri Anda: F&B, Service (Jasa), Retail Cepat, atau Kelontong. Sistem otomatis menyesuaikan terminologi, layout, dan modul aktif berdasarkan profil bisnis.

4. **Security by Default**
   Dibangun dengan lapis perlindungan tingkat bank:
   - *Cross-Site Request Forgery (CSRF)* Token di setiap form transaksi.
   - *Security Headers* standar industri (*X-Frame-Options, X-Content-Type-Options*).
   - Numeric-guard PIN dan *session regeneration* instan.

---

## 📸 Antarmuka Pengguna

<div align="center">
  <img src="docs/screenshots/login.png" width="48%" alt="Halaman Login">
  <img src="docs/screenshots/dashboard.png" width="48%" alt="Halaman Dashboard">
</div>

---

## 🛠 Tech Stack & Engine

- **Backend:** Vanilla PHP 8+ (No bloatware, eksekusi super ringan)
- **Database:** SQLite (Embedded, terisolasi, atomic)
- **Design Pattern:** MVC, Domain-Driven Design (DDD), Repository Pattern, Data Transfer Objects (DTO)
- **Frontend:** Vanilla HTML/CSS/JS dengan pendekatan *Progressive Enhancement*

---

## 🚦 Getting Started (Developer & Evaluator)

Jalankan SiapPOS dalam waktu kurang dari 10 detik.

1. **Clone Repository**
   ```bash
   git clone https://github.com/your-username/siappos.git
   cd siappos
   ```

2. **Start the Engine**
   Sistem terpadu menggunakan router `public/index.php`.
   ```bash
   php -S 127.0.0.1:8088 -t public
   ```

3. **Access Application**
   Buka browser dan arahkan ke: `http://127.0.0.1:8088/?page=login`

### 🔑 Demo Credentials (RBAC)

Gunakan kredensial pra-konfigurasi berikut untuk menguji *Role-Based Access Control*:

| Role | Username | PIN | Deskripsi Kemampuan |
|---|---|---|---|
| **Owner / Admin** | `owner` | `1111` | Akses penuh, pengaturan toko, laporan keuangan utama |
| **Manager** | `manager` | `2222` | Kontrol inventaris, *void* transaksi kasir, *stock opname* |
| **Cashier** | `cashier` | `3333` | Akses POS Terminal, pencatatan transaksi dasar |

> **Pro Tip:** Di halaman login, Anda dapat mengklik tombol **"Try Demo 1 Klik"** untuk secara instan membuat *mock data* dan masuk ke dalam sistem tanpa perlu setup manual.

---

<div align="center">
  <i>SiapPOS — Siap Melayani, Siap Bertumbuh.</i>
</div>