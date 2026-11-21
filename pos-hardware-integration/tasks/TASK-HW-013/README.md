# TASK-HW-013: Setup Awal QZ Dan Kesiapan Perangkat

Status: COMPLETE untuk software/native verification; commit/push berhasil.
Tanggal: 21 November 2026. Module target: Platform/Hardware.

User mengubah keputusan pemasangan QZ: ingin QZ terpasang pada PC ini agar
setup awal klien sederhana. Printer dipilih/dikonfigurasi dari Perangkat POS.
Scanner dipastikan lewat jawaban eksplisit: "Langsung scan + tes kesiapan".
Tidak menambahkan enumerasi USB/HID atau menebak merek scanner.

- [Kebutuhan dan rancangan pengalaman](PRD.md).
- [PLAN dan exact allowlist](PLAN.md).
- [Checklist increment](tasks.md).
- [Persetujuan](APPROVAL.md).
- [Laporan implementasi dan verifikasi](REPORT.md).

User menyetujui PLAN dan memasang QZ sendiri secara manual. Agent tidak
menjalankan installer/download/upgrade kedua atau mengubah trust/startup.
Source autodiscovery dan kesiapan scanner, helper setup klien, panduan/ADR
sudah dibuat dan QA akhir lulus. Tidak ada perubahan backend/database/.env/flag cetak.
Native read-only PASS: QZ2.3.0+1 dan6 printer OS, tanpa cetak; browser final12
cek PASS. Implementasi d00cb82 dan report aa38d54 dipush, origin/main
diverifikasi cocok, tanpa file LSP user. Physical gate tetap pending/OFF.

Setelah exact PLAN disetujui, paket diselesaikan tanpa persetujuan ulang per
langkah kecil. UAC/izin OS tetap memerlukan tindakan user jika diminta Windows.
Pemasangan QZ bukan bukti printer/scanner fisik kompatibel atau izin print ON.
