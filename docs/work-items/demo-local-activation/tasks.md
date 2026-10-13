# Tasks: Aktivasi Demo Lokal Dan POS

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md dan dokumen arsitektur/struktur relevan dibaca.
- [x] Migrate:status menunjukkan customer masters Ran; demo baru masih kosong.
- [x] User mengonfirmasi halaman master dan POS sama-sama perlu diperbarui.
- [x] Scope additive, preservasi data user dan mock-only POS ditetapkan.

## Increment 1: Demo Pada Database Lokal

- [x] Jalankan seeder demo relasional melalui pengaman local/MySQL.
  - Acceptance: 9 produk CCTV/kelistrikan, relasi satuan, stok awal FIFO dan
    3 level/pelanggan tersedia; seluruh row existing dan akun/grant tetap sama.
  - Verification: apply-demo-local.php dan fingerprint sebelum/sesudah.
- [x] Ulangi provisioning dan commit increment.
  - Acceptance: tidak ada duplikasi, ledger/audit baru tidak bertambah saat repeat.
  - Verification: count/snapshot dan migrate:status.

## Increment 2: Mock POS

Increment 1 Passed: apply-demo-local.php menambah 5 satuan (3 kode sudah ada),
9 barang, 18 relasi, 1 dokumen stok posted/5 baris, 5 saldo/movement/FIFO layer,
3 level dan 3 pelanggan. Fingerprint semua row existing dan akun/grant identik.
Pemanggilan kedua seluruh delta 0, termasuk activity_log. Tidak migrate:fresh.

- [ ] Ganti mock sembako dengan demo CCTV/kelistrikan.
  - Acceptance: 9 SKU dan rasio satuan sesuai dataset seeder; level Eceran,
    Grosir, Installer; 2 gudang demo; harga/stok tetap fiktif, bukan engine live.
  - Verification: regresi dataset mock dan route POS.
- [ ] Jalankan lint/typecheck/build dan QA browser desktop/tablet.
  - Acceptance: produk baru terlihat, pencarian/cart/satuan bekerja, framing
    tidak overflow, tidak ada error console; theme tidak berubah.
  - Verification: Chrome DevTools MCP bila tersedia, fallback Playwright existing.
- [ ] Perbarui bukti, commit dan push.
  - Acceptance: status sesuai realita, tidak mengklaim posting POS backend.
  - Verification: diff check, git status, remote HEAD.
