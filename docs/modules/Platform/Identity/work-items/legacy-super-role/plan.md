# Plan: Migrasi Role Super Lama

## Increment 1: Command Dan Regresi

1. Tulis test gagal untuk migrasi role dan preview sebelum implementation.
2. Tambahkan action Application dengan transaction, row lock dan audit.
3. Tambahkan Console inbound adapter, daftar pada composition root Identity.
4. Verifikasi focused/full tests, Pint, command help dan route tetap; commit.

## Increment 2: Operasi Lokal Dan Penutupan

1. Preview database lokal; jalankan --apply sesuai persetujuan user.
2. Bandingkan ID/pivot/user dan jumlah permission sebelum/sesudah; Gate dan
   shared auth aktif. Jangan mencetak hash password atau detail akun.
3. Verifikasi idempotence preview/repeat, audit, dan dokumentasi status nyata.
4. Isi checklist setelah hasil tersedia; commit dan push work item selesai.

## Boundary Dan QA

Console memanggil Application. Spatie tetap dipakai mengikuti action Identity
existing; tidak membuat port tanpa consumer atau mengubah struktur canonical.
Tidak membuat schema/migration untuk rename administratif opt-in. Frontend
lint/build/typecheck SKIPPED karena tidak mengubah frontend. Chrome DevTools
dicoba bila tersedia; fallback HTTP feature test dan smoke backend apabila tidak.
