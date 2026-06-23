# ap — Aplikasi Accounts Payable (Suite ERP DKM)

> Aplikasi Laravel berdiri sendiri untuk modul **AP**: Invoice → Payment, plus laporan AP (Open/Aged/Aged Summary/History Payables). Bagian dari suite ERP baru PT. Dharma Karyatama Mulia (DKM). Lihat `C:\Project\Web\sls\CLAUDE.md` untuk konteks suite secara umum dan `C:\Project\Web\erp-schema\MODULES-ROADMAP.md` untuk rencana modul lain.

## Cakupan

- **Invoice line-based, bukan item-based** — beda dari `ar` yang line-nya menjual item katalog, AP invoice line langsung pilih `gl_account_id` (expense account, free-form) + `tax_id` opsional per line. Bisa opsional mereferensikan `prc_purchase_order_id` (tag saja, tidak auto-pull baris PO).
- **PPh (withholding tax)** di header invoice — `pph_type` (none/pph23/pph22), `pph_rate`, `pph_amount` dihitung dari DPP (= subtotal baris GL sebelum PPN) × rate. PPh mengurangi `owing` (disetor sendiri ke kas negara, bukan dibayar tunai ke vendor).
- **Payment** mengalokasikan satu pembayaran ke banyak invoice sekaligus, dengan opsi diskon (early payment) dan write-off per alokasi (write-off butuh pilih GL account).
- **Posting GL otomatis** (retrofit 2026-06-23) — `approve()` Invoice posting `Dr {line.gl_account_id}` + `Dr PPN Masukan` (per-line tax, BUKAN flat rate seperti `ar`) = `Cr AP Control` (total−pph) + `Cr PPh Payable`; Payment posting `Dr AP Control` = `Cr Bank` + `Cr Diskon AP` + `Cr akun write-off`. Model `GlAccount`/`GlJournal`/`GlJournalLine`/`GlSetting` di-duplikasi ke app ini (pola sama di `ar`/`prc`/`inv`, tidak ada cross-app call). Detail lengkap di `ApInvoiceController::postInvoiceJournal()`/`ApPaymentController::postPaymentJournal()`.
- **Tidak ada bank reconciliation sungguhan** — cuma flag `reconciled` di `ap_payments`.

## Reports

4 laporan mengikuti struktur wizard "AP Reports" BS1 (`ReportController.php`):
- **Open Payables** — flat list invoice belum lunas per vendor, tanpa age bucket (No Invoice/Tanggal/Jatuh Tempo/Total/Dibayar/Owing).
- **Aged Payables** — detail per-invoice dengan age bucket (current/1-30/31-60/61-90/90+), grouped per vendor dengan subtotal.
- **Aged Payables Summary** — rollup per vendor saja (cuma total per bucket, tanpa baris invoice).
- **AP History** — gabungan ApInvoice+ApPayment dalam rentang tanggal (filter `date_from`/`date_to`, default bulan ini), grouped per vendor, urut by date.

`agedPayables()`/`agedPayablesSummary()` share logic lewat private helper `buildAgedPayables()` — query dan alokasi bucket identik, cuma view yang beda level detailnya.

## Model Read-Only Lintas App

`Vendor`/`Bank`/`GlAccount`/`Tax`/`PurchaseOrder` — model biasa (tabel shared `erp`, tanpa migration di app ini). `PurchaseOrder` di app ini **read-only** (`prc` yang punya), cuma dibaca untuk referensi tagging opsional di invoice.

## RBAC & Struktur

Identik dengan `sls`/`ar`: role `sso_admin|admin|user|approval|viewer`, tabel `ap_users`/`ap_sessions`/`ap_cache`/`ap_jobs` dst (prefix `ap_`). `AutoNumberService` prefix: `BIL` (invoice), `PAY` (payment).

## Dev Lokal

SSO lokal sungguhan (bukan dev-login) — `AP_DEV_LOGIN_ENABLED=false` di `.env` lokal, entry point selalu lewat Portal SSO. Port lokal `8115`.

## Deployment

✅ **Live di production**: `https://ap.dkmapps.com`.

**Jangan jalankan `db:seed` di production** — sama seperti app lain di suite ini, seeder isinya data demo/dev-login fiktif.

## Status & Verifikasi

✅ Alur penuh sudah dicoba (lokal + production) via curl/SSO sungguhan: buat invoice (dengan PPN per-line + PPh) → submit → approve (posting jurnal GL balanced) → catat payment (partial + disc + write-off) → submit → approve (posting jurnal GL balanced) → owing terupdate benar → cek di semua 4 report AP. Full chain cross-app juga sudah diverifikasi (lihat `ar/CLAUDE.md` dan `prc/CLAUDE.md` untuk retrofit GL terkait): AP Invoice yang mereferensikan PO `prc` bisa "clear" akun GR/IR Clearing dengan memilih akun itu sebagai `gl_account_id` baris invoice, tanpa kode khusus.

⏳ Belum dicoba: tampilan visual penuh di browser asli untuk semua halaman (kebanyakan verifikasi lewat HTTP/curl + Playwright, bukan klik manual satu-satu).
