# ADR-0006: Gunakan shadcn/ui Sebagai Default UI/UX

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Aplikasi POS membutuhkan UI yang konsisten untuk admin ERP dan POS fullscreen. Source Laravel 12 Inertia React yang masuk sudah memiliki konfigurasi `components.json`, sehingga shadcn/ui dapat menjadi fondasi komponen sejak awal.

Konfigurasi saat ini:

- Style: `default`.
- Tailwind base color: `neutral`.
- CSS variables: aktif.
- TSX: aktif.
- RSC: tidak dipakai.
- Icon library: `lucide`.
- Alias: `@/components`, `@/components/ui`, `@/lib`, dan `@/hooks`.

## Keputusan

Gunakan shadcn/ui sebagai default UI/UX aplikasi.

Admin ERP dan POS fullscreen harus memakai token visual, spacing, komponen dasar, dan pola interaksi yang konsisten dengan shadcn/ui. Komponen khusus POS boleh dibuat hanya jika workflow kasir membutuhkan interaksi yang tidak cocok dengan komponen standar.

Feedback operasi CRUD memakai Sonner toast dari shadcn/ui. Toast dipakai untuk status sukses dan error non-field, sedangkan validasi field tetap ditampilkan dekat input dan aksi destructive tetap membutuhkan konfirmasi.

Frontend memakai struktur feature-folder per halaman/domain di `resources/js/pages`. Komponen lokal fitur disimpan di folder fitur masing-masing, misalnya `pages/platform/users/components`. Komponen hanya dinaikkan ke `components/shared` jika benar-benar dipakai lintas fitur dan kontraknya stabil.

## Alternatif Yang Dipertimbangkan

### Komponen Custom Dari Nol

- Kelebihan: kontrol penuh.
- Kekurangan: mahal dirawat dan rawan tidak konsisten.
- Ditolak.

### Headless UI Sebagai Default

- Kelebihan: fleksibel dan sudah tersedia di dependency.
- Kekurangan: membutuhkan styling/design system sendiri.
- Ditolak sebagai default, tetapi boleh dipakai jika ada kebutuhan spesifik yang tidak ditangani shadcn/ui.

### Library Komponen Besar

- Kelebihan: banyak komponen siap pakai.
- Kekurangan: lebih sulit disesuaikan dengan POS fullscreen dan Tailwind starter kit.
- Ditunda.

## Konsekuensi

- Developer harus memakai komponen shadcn/ui sebelum membuat primitive baru.
- Ikon memakai `lucide-react`.
- Token warna dan spacing mengikuti Tailwind/shadcn.
- UI custom POS tetap harus konsisten dengan design system utama.
- Operasi CRUD UI memiliki feedback konsisten melalui Sonner toast.
- Struktur frontend tidak boleh flat; komponen fitur harus dekat dengan page/domain yang memakainya.
- Perubahan design system besar membutuhkan ADR baru atau persetujuan eksplisit.
