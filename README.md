# SiapPOS - Session 1 (Production-Ready Baseline)

Session 1 sekarang fokus pada fondasi yang usable untuk operasional awal.

## Yang sudah stabil

- Login berbasis role (`admin`, `manager`, `cashier`)
- Tombol `Try Demo` 1 klik
- Setup bisnis (onboarding) dengan progress bar + checklist go-live
- Pemilihan template bisnis:
  - `fnb`
  - `service`
  - `retail`
  - `kelontong_bangunan`
- Dashboard ringkas dengan KPI utama, checklist kesiapan operasional, dan prioritas minggu pertama
- UX interaction dasar:
  - loading state tombol submit
  - input PIN numeric guard
  - template card selection highlight
  - auto-hide success flash

## Hardening teknis Session 1

- Session ID regenerate saat login/logout
- CSRF untuk semua POST form
- Security headers dasar (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`)
- Routing fallback `404` dengan tampilan konsisten

## Menjalankan aplikasi

```powershell
cd "C:\Users\LRN KHALID BIN WALID\Desktop\siappos"
C:\xampp\php\php.exe -S 127.0.0.1:8088 -t public
```

Buka:
- `http://127.0.0.1:8088/?page=login`

## Akun lokal pengembangan

- `owner / 1111`
- `manager / 2222`
- `cashier / 3333`

## Validasi yang sudah dijalankan

- Lint seluruh file PHP: `LINT_OK`
- Smoke test halaman login: `SMOKE_LOGIN_OK`
- Smoke test alur onboarding + dashboard: `SMOKE_ONBOARD_DASH_OK`

## Scope sesi berikutnya

- Sesi 2: POS terminal + cart + checkout
- Sesi 3: Product + stock + stock opname
- Sesi 4: invoicing + payment full/DP/partial
- Sesi 5: template F&B recipe/BOM auto-decrement ingredient
