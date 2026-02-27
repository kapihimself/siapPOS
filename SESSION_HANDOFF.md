# SiapPOS Session Handoff (Feb 25, 2026)

## Status saat ini
- Session 1 selesai dan sudah usable.
- Fokus yang sudah jadi: login, try-demo, onboarding, template bisnis, dashboard baseline, UX polish, CSRF/session hardening.
- Validasi terakhir: LINT_OK, SMOKE_LOGIN_OK, SMOKE_ONBOARD_DASH_OK.

## File kunci yang diubah terakhir
- public/index.php
- views/layout.php
- views/login.php
- views/onboarding.php
- views/dashboard.php
- public/assets/app.css
- public/assets/app.js
- src/App/View.php
- README.md

## Cara jalanin lokal
```powershell
cd "C:\Users\LRN KHALID BIN WALID\Desktop\siappos"
C:\xampp\php\php.exe -S 127.0.0.1:8088 -t public
```
URL: http://127.0.0.1:8088/?page=login

## Akun local
- owner / 1111
- manager / 2222
- cashier / 3333

## Next target (Session 2)
1. POS terminal page.
2. Cart add/update/remove item.
3. Checkout basic (cash/qris stub).
4. Simpan order + order lines + update stok.
5. UI receipt sederhana.

## Prompt pembuka besok (copy-paste)
"Lanjut SiapPOS dari SESSION_HANDOFF.md. Kerjakan Session 2: POS terminal + cart + checkout basic, dan update progress % tiap block."
