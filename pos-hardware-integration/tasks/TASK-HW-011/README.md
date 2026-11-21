# TASK-HW-011: Akses Produksi Uji Scanner

**Status:** COMPLETE: DIAGNOSTIK SCANNER ON; FISIK PENDING.
**Tanggal:** 21 November 2026.
**Module:** Platform/Hardware.

User meminta commit/push hasil sebelumnya lalu mengaktifkan fitur agar dapat
digunakan ketika perangkat fisik tersedia. TASK-HW-002 sudah di-commit/push:
`96bddfe7d05d8f6f5ce2ff30c74fd3f9622fe08d` pada origin/main.

Yang sudah diimplementasikan hanya pemeriksaan identitas barcode satuan jual.
Rencana ini membuka akses halaman tersebut pada instalasi production, bukan
mengimplementasikan printer, gateway atau menjamin perangkat fisik kompatibel.
Nomor 011 dipakai agar tidak mengubah nomor usulan bundle 003 sampai 010.

- [PLAN dan exact allowlist](PLAN.md).
- [Persetujuan](APPROVAL.md).
- [Checklist pelaksanaan](tasks.md).
- [REPORT dan bukti aktivasi](REPORT.md).

PLAN disetujui eksplisit pada 21 November 2026, lalu implementasi/QA dan
aktivasi dilakukan sesuai allowlist. POS_HARDWARE_ENABLED=true terverifikasi
di config cache production dan dua route tersedia di route cache.
URL aktif: `https://pos.test/platform/hardware/scanner`.
Tidak menambahkan menu global; akses langsung didokumentasikan untuk operator.
Scanner mode keyboard dapat diuji setelah tersedia, dengan suffix Enter dan
input fokus. Kompatibilitas USB/dongle/Bluetooth tetap PHYSICAL_PENDING.

CT420D label TSPL/CPCL, cetak otomatis dan gateway tidak termasuk scope ini.
Scan pada POS/pembelian tetap melalui alur existing, bukan halaman uji ini.

Software VIRTUAL_PASSED: 39 test/449 assertion pada gate terdampak, final
guard subset 4 test/26 assertion setelah isolasi flag test, browser 12 cek,
fingerprint 71 tabel latihan identik. Tidak mengulang gate source yang sama.
HTTPS login200, scanner guest302 ke login dan resolve guest401; TLS tervalidasi.
Tidak mengubah akun, master, transaksi atau permission toko.
