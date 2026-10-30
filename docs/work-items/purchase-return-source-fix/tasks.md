# Tasks: Perbaikan Sumber Retur Pembelian

## Sebelum Mulai

- [x] Baca aturan project dan dokumentasi module relevan.
- [x] Scope dibatasi ke kegagalan sumber retur pembelian.
- [x] Reproduksi tiga kegagalan lookup di database disposable.

## Increment 1: Penyebab Dan Perbaikan

- [x] Isolasi predicate sumber yang gagal.
  - Acceptance: penyebab ditunjukkan oleh bukti test, bukan perkiraan.
  - Verification: test terfokus sebelum perubahan.
- [x] Perbaiki dan lengkapi test regresi.
  - Acceptance: sumber valid ditemukan; metadata/baris yang tidak cocok ditolak.
  - Verification: test lookup dan consumer retur.

## Increment 2: Verifikasi Dan Handoff

- [ ] Jalankan gate backend serta pemeriksaan native/browser yang relevan.
  - Acceptance: hasil dan gap di luar scope dilaporkan apa adanya.
  - Verification: command dan hasil dicatat pada README.
- [ ] Review diff dan perbarui gap dokumentasi terdahulu.
  - Acceptance: tidak ada perubahan data produksi atau berkas user.
  - Verification: git diff --check dan review scope.
- [ ] Commit terfokus, push dan verifikasi sinkronisasi remote.
