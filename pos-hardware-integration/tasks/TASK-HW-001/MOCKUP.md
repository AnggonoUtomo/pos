# Rancangan Workspace Hardware

**Status:** DESAIN APPROVED oleh pemilik pada 20 November 2026.
Ini mockup, bukan UI POS aktif. Label DRAFT pada HTML/screenshot dipertahankan
sebagai artefak visual yang ditinjau; approval tidak mengaktifkan fitur.
Buka [MOCKUP.html](MOCKUP.html) secara lokal. Tidak membutuhkan dev server,
package, API, data toko, gateway atau perangkat. Tidak mengubah aplikasi.

## Kontrak Desain

- Workspace admin terpisah bernama Perangkat POS. Navigasi sidebar/palette
  pada aplikasi existing belum ditambahkan. POS fullscreen tidak diganti.
- Light pada contoh menggunakan prinsip surface putih/tint ringan dari app.css;
  implementasi kelak wajib reuse theme/token existing, termasuk Dark. CSS lokal
  mockup bukan design system runtime yang akan di-copy ke source.
- Heading kompak, band unframed, tabel terstruktur, aksen semantic biru/hijau/
  amber/merah dan status teks. Tidak bergantung pada warna saja.
- Target desktop1366/1920 dan tablet1024/768. Tablet landscape dua kolom,
  portrait satu kolom, tab wrap dan tabel boleh scroll di dalam panel.
- Shortcut dropdown di atas workspace, panduan Sheet kanan dan usulan F1.
  Shortcut global tidak dipasang pada HTML; PageGuide existing dipakai kelak.
- Autocomplete master, shared pagination dan Sonner tetap standar implementasi
  kelak. Bentuk input readonly mockup bukan usulan mengganti Autocomplete.
- Hanya tab dan panduan lokal dapat dibuka. Semua operasi bisnis disabled;
  tidak ada submit/fetch/print atau fake software yang diklaim sudah bekerja.

## Peta Seluruh Workspace

| Bagian | Isi | State yang harus jelas |
| --- | --- | --- |
| Ringkasan | Terminal, CT420D label, HC-4208DB scanner, alur kerja | Flag OFF, perangkat belum dipasangkan, physical pending |
| Terminal dan printer | Assignment perangkat milik instalasi toko | Permission denied, unsupported model/protocol, unpaired gateway |
| Uji scanner | Konteks gudang/pelanggan contoh, input barcode satuan | Enter, leading zero, scan berulang, unknown, disconnect simulasi, fokus pembayaran aman |
| Label produk | Barang/satuan/template/ukuran/copies dan preview | Kode-only atau harga sesuai level pilihan; media invalid; CT420D bukan receipt |
| Antrean cetak | Data job fiktif dan status transport | Pending, sent/accepted, failed-before-send, paper-out/offline simulasi, uncertain tanpa blind retry |
| Panduan | Tombol floating membuka panel kanan | Tidak ada credential/data pelanggan atau operasi perangkat nyata |

Tidak ada tenant/cabang, pilihan harga Installer hardcoded, editor harga baru,
cash drawer, cutter, receipt auto-print atau shipping/waybill yang belum punya
sumber bisnis. Label harga kelak mengikuti level pelanggan yang dinamis.

## Wireframe Desktop

```text
Sidebar existing | Topnav existing / Perangkat POS         Fitur OFF
                 | Perangkat POS                         Mode virtual
                 | Shortcut operasi
                 | Ringkasan | Terminal/printer | Scanner | Label | Antrean
                 | Terminal demo | Printer label | Scanner
                 | Perangkat/alur              | Status dan perhatian
                 |                              Panduan -> Sheet kanan
```

## Wireframe Tablet

```text
Topnav: breadcrumb dan status OFF (tidak ada tenant/cabang)
Judul dan mode virtual
Shortcut operasi
Tab wrap, tetap dapat dipilih dengan sentuh
Landscape: form/status berdampingan
Portrait: form, preview/status berurutan; tabel scroll lokal
Panduan -> Sheet kanan; tombol sentuh minimal44px
```

## Screenshot Dan Pemeriksaan

Render offline pada Chrome terpasang melalui Playwright existing, Node
v24.12.0. Tidak menginstal package atau menjalankan web server/aplikasi.
Discovery tidak menyediakan tool Chrome DevTools MCP pada sesi ini, sehingga
MCP BLOCKED; fallback hanya membuka file HTML lokal dalam context terisolasi.

| Viewport | Screenshot | Hasil |
| --- | --- | --- |
| Desktop 1366x768 | [Desktop1366](mockup-desktop-1366.png) | PASS |
| Desktop 1920x1080 | [Desktop1920](mockup-desktop-1920.png) | PASS |
| Tablet landscape 1024x768 | [Landscape](mockup-tablet-landscape.png) | PASS |
| Tablet portrait 768x1024 | [Portrait](mockup-tablet-portrait.png) | PASS |

Pada keempat viewport: root tidak overflow horizontal, kelima tab dapat
dibuka, panduan terbuka/tertutup dalam viewport, console error0 dan external
request0. Screenshot merekam Ringkasan; tab lain diperiksa visibility dan
overflow secara otomatis. Desktop1366 dan portrait juga diinspeksi visual.
Tidak mengklaim uji semua kombinasi keyboard/accessibility, theme Dark atau
perangkat Android nyata dari pemeriksaan terbatas ini.

Validator PLAN menggunakan `python` existing karena `python3` menunjuk alias
Windows Store yang tidak menyediakan interpreter. Hasil: `PASS: PLAN structure
complete`. Pemanggilan browser awal diperbaiki dari import path Windows menjadi
file URL; run final selesai PASS. Tidak ada pengubahan instalasi/konfigurasi.

Render ini bukan virtual acceptance hardware; software tetap NOT_RUN,
fisik PHYSICAL_PENDING dan rollout NOT_APPROVED.

## Desain Yang Disetujui

1. Workspace terpisah dengan tab dan alur label/scan-back tersebut.
2. Preview/status tidak diklaim hasil fisik, uncertain meminta pemeriksaan.
3. Tetap mempertahankan scanner, cetak browser dan seluruh POS existing.

User menyetujui melalui "ok, approved bro."; bukti dan batasnya tercatat di
[APPROVAL.md](APPROVAL.md). Persetujuan desain tidak mengaktifkan hardware atau
memberi izin modifikasi source. PLAN implementasi exact-file tetap dibutuhkan
berikutnya.
