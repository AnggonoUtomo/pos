# AGENTS.md

Panduan ini wajib dibaca sebelum agent atau pengembang mengubah kode di workspace `C:\laragon\www\pos`.

## Prinsip Utama

- Jangan mulai coding sebelum membaca `docs/SPEC.md`, `docs/PRD.md`, `docs/ARCHITECTURE.md`, ADR terkait, dan work-item aktif.
- Kerjakan hanya scope work-item yang disetujui.
- Jangan mengubah keputusan domain besar tanpa ADR baru atau persetujuan eksplisit.
- Jika dokumen dan kode bertentangan, hentikan asumsi dan laporkan konflik.
- Verifikasi dengan command nyata sebelum menyatakan pekerjaan selesai.
- Catat gap conformance pada work-item terkait; jangan menutup gap di luar scope tanpa persetujuan user.
- Setiap work-item modul harus memiliki rencana increment dan checklist per increment sebelum coding dimulai.
- QA automated wajib mengikuti `docs/QA-AUTOMATION.md`.
- Untuk perubahan UI/browser, agent wajib mencoba Chrome DevTools MCP dengan urutan akses eksplisit dari `docs/QA-AUTOMATION.md`.

## Stack Dan Batas Arsitektur

- Laravel 12, Inertia React, MySQL.
- shadcn/ui adalah default UI/UX aplikasi.
- Modular monolith dengan DDD-lite dan hexagonal architecture.
- Struktur modul: `Modules/{Category}/{Module}`.
- Primary key tabel utama: ULID.
- Naming database dan kode memakai English technical naming; dokumentasi dan UI label memakai Bahasa Indonesia.
- Auth: Laravel starter kit.
- Authorization: Spatie Laravel Permission.
- Audit trail: Spatie Laravel Activitylog.

## Keputusan Domain Yang Tidak Boleh Dilanggar

- Single company untuk fase 1.
- Multi gudang, bukan multi cabang.
- User boleh memilih gudang saat transaksi.
- Satu transaksi memakai satu gudang untuk MVP.
- Stok tidak boleh minus.
- FIFO per `item + warehouse`.
- Semua stok disimpan dalam base unit terkecil.
- Harga jual berdasarkan customer level dan satuan.
- Public registration dimatikan.
- Posted transaction tidak boleh diedit bebas.
- Koreksi posted transaction lewat retur, void/reversal, atau adjustment yang audited.
- Activity log bukan ledger stok atau ledger finance.

## Alur Kerja Wajib

1. Pilih work-item dari `docs/work-items/WORK-ITEM-REGISTRY.md`.
2. Baca dokumen rujukan work-item.
3. Informasikan modul target yang akan dikerjakan.
4. Isi checklist sebelum coding di file work-item.
5. Pastikan rencana increment dan QA automated sudah tertulis.
6. Implementasi per increment kecil dan terverifikasi.
7. Jalankan checklist sesudah coding per increment.
8. Catat command, hasil, dan bukti verifikasi.
9. Update status increment dan status work-item.

## Sebelum Coding

- Pastikan work-item punya acceptance criteria.
- Pastikan dampak database, route, permission, UI, dan laporan sudah disebut.
- Pastikan risiko FIFO, stok, pembayaran, pajak, dan audit trail dipertimbangkan jika tersentuh.
- Pastikan command QA automated untuk increment sudah jelas.
- Cek status git jika repository sudah tersedia.
- Jangan membuat fitur di luar work-item.
- Jika menyentuh integrasi lintas modul, baca `docs/MODULE-COMMUNICATION.md`.
- Untuk modul baru, gunakan `php artisan module:make {Category} {Module}` dan awali dengan `--dry-run` jika struktur file belum pasti.

## Sesudah Coding

- Jalankan test yang relevan.
- Jalankan build/typecheck/lint jika tersedia.
- Untuk UI/browser, jalankan QA via Chrome DevTools MCP atau catat alasan `SKIPPED/BLOCKED` beserta fallback.
- Jalankan migration/status check jika schema berubah.
- Verifikasi behavior utama secara manual jika UI atau transaksi berubah.
- Catat hasil command di work-item atau implementation report.
- Isi checklist sesudah coding dan status increment pada work-item.
- Jangan klaim selesai jika command gagal atau belum dijalankan.

## Gaya Perubahan

- Ikuti pola Laravel dan Inertia React yang ada.
- Untuk UI, gunakan shadcn/ui dan `lucide-react` sebagai default sebelum membuat komponen custom.
- Jangan membuat design system paralel tanpa persetujuan eksplisit.
- Letakkan komponen fitur dekat dengan page-nya: `resources/js/pages/{category}/{feature}/components`.
- Gunakan `resources/js/components/shared` hanya untuk komponen lintas fitur yang benar-benar umum dan stabil.
- Gunakan Form Request atau boundary validation untuk input user.
- Gunakan service/application action untuk use case transaksi.
- Komunikasi lintas modul harus lewat `Application/Contracts`, DTO, event publik, atau read model yang disepakati.
- Domain event atau integration event hanya dibuat jika memang memiliki consumer.
- `ServiceProvider.php` module adalah composition root untuk binding contract-adapter, route/migration, policy/listener, dan tidak boleh berisi business logic.
- Jangan gunakan Laravel Boost atau Wayfinder sebagai source of truth arsitektur.
- Jangan pindahkan migration module ke `database/migrations` global untuk table milik module.
- Jangan taruh business rule FIFO, pricing, tax, atau payment langsung di controller.
- Simpan snapshot transaksi untuk harga, satuan, pajak, diskon, dan HPP.
- Gunakan database transaction dan locking untuk posting stok/payment.

## Bahasa Dokumentasi

- Dokumentasi proyek ditulis dalam Bahasa Indonesia.
- Nama tabel, kolom, class, method, route, permission, enum, DTO, event, contract, dan namespace tetap mengikuti English technical naming.
