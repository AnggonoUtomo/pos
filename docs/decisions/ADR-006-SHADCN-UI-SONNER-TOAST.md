# ADR-006: shadcn/ui Dan Sonner Toast Sebagai Baseline UI Feedback

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Aplikasi membutuhkan UI admin ERP yang konsisten, POS fullscreen yang efisien,
dan feedback operasi CRUD yang jelas. Project memakai Inertia React dan Tailwind
CSS, sehingga shadcn/ui cocok sebagai baseline komponen.

## Keputusan

shadcn/ui menjadi default UI/UX aplikasi. `lucide-react` menjadi default icon.
Sonner toast dari shadcn/ui digunakan untuk feedback operasi CRUD seperti
create, update, delete, restore, activate, deactivate, dan sync.

## Konsekuensi

- Jangan membuat design system paralel tanpa persetujuan eksplisit.
- Komponen fitur diletakkan dekat page.
- Komponen shared hanya untuk elemen lintas fitur yang benar-benar stabil.
- Jika package atau komponen Sonner belum tersedia, work item UI baseline harus
  menambahkannya sebelum fitur CRUD UI menggunakannya.

## Verifikasi

- CRUD UI menampilkan success/error toast yang sesuai.
- Loading, disabled, empty, dan error state tersedia pada alur utama.
- UI browser diverifikasi dengan Chrome DevTools MCP bila perubahan menyentuh
  tampilan atau interaksi.
