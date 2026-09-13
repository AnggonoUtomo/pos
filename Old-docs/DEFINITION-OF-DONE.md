# Definisi Selesai

Dokumen ini adalah standar minimum sebelum pekerjaan dianggap selesai.

## Berlaku Untuk Semua Work-Item

- Scope sesuai work-item.
- kriteria penerimaan terpenuhi.
- Tidak ada perubahan di luar scope tanpa dicatat.
- Tidak ada public behavior yang berubah tanpa persetujuan.
- Tidak ada secret, credential, atau data lokal yang ikut tersimpan.
- Dokumentasi terkait diperbarui jika keputusan berubah.
- QA automated yang relevan sudah direncanakan, dijalankan, dan buktinya dicatat.

## Checklist Sebelum Coding

- [ ] Baca `AGENTS.md`.
- [ ] Baca `docs/SPEC.md`.
- [ ] Baca `docs/PRD.md`.
- [ ] Baca `docs/ARCHITECTURE.md`.
- [ ] Baca ADR terkait di `docs/adr/`.
- [ ] Baca work-item aktif.
- [ ] Pastikan kriteria penerimaan jelas.
- [ ] Pastikan rencana verifikasi jelas.
- [ ] Pastikan rencana QA automated mengacu ke `docs/QA-AUTOMATION.md`.
- [ ] Jika UI/browser tersentuh, pastikan rencana Chrome DevTools MCP QA sudah disebut.
- [ ] Pastikan modul target dan increment pengerjaan sudah disebut di work-item.
- [ ] Pastikan kebutuhan demo seeder module sudah diputuskan.
- [ ] Cek status git jika repository sudah tersedia.
- [ ] Identifikasi risiko schema, route, permission, FIFO, payment, tax, audit log.

## Checklist Sesudah Coding

- [ ] Perubahan sesuai scope.
- [ ] Migration aman dan reversible jika memungkinkan.
- [ ] Test relevan ditambahkan atau diperbarui.
- [ ] Command verifikasi dijalankan dan hasilnya dicatat.
- [ ] Checklist per increment pada work-item diperbarui.
- [ ] QA automated relevan sudah PASS atau alasan gagal/belum bisa dijalankan dicatat.
- [ ] Chrome DevTools MCP QA sudah PASS untuk perubahan UI/browser, atau status `SKIPPED/BLOCKED` dan fallback dicatat.
- [ ] Build frontend dijalankan jika UI berubah.
- [ ] Demo seeder module diisi dan diverifikasi jika relevan, atau alasan skip dicatat.
- [ ] Tidak ada transaksi posted yang bisa diedit bebas.
- [ ] Tidak ada jalur stok minus.
- [ ] Activity log dicatat untuk aksi penting.
- [ ] Dokumentasi/work-item diperbarui.
- [ ] Git diff dicek sebelum laporan selesai.

## Bukti Yang Diharapkan

Catat command dan hasil ringkas, misalnya:

```text
php artisan test --filter=SalesPostingTest
Hasil: PASS

npm run build
Hasil: PASS
```

Jika command belum bisa dijalankan karena source belum tersedia atau dependency belum dipasang, tulis alasan eksplisit. Jangan klaim pass.

## Aturan Increment Work-Item

Setiap pekerjaan modul harus memiliki bagian increment di work-item aktif sebelum coding dimulai.

Minimal berisi:

- Modul target yang akan dikerjakan.
- Urutan increment kecil.
- Checklist sebelum coding per increment.
- Checklist selesai per increment.
- Command QA automated per increment.
- Keputusan demo seeder per module atau increment data.
- Kolom status yang diperbarui setelah pekerjaan selesai.

Agent atau developer harus menginformasikan modul target dan increment pertama sebelum mulai coding.

## Kondisi Blocked

Work-item dianggap blocked jika:

- Dokumen saling bertentangan dan keputusan domain diperlukan.
- Source belum tersedia untuk implementasi.
- Dependency utama belum bisa diinstall.
- Test environment tidak bisa dibuat.
- Ada risiko data loss atau perubahan behavior besar yang belum disetujui.
