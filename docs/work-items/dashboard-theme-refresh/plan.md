# Rencana

1. Tulis RED root redirect, ganti entry route dan hapus welcome yang tidak
   digunakan; pertahankan nama home/login/dashboard.
2. Rapikan token surface/color/shadow serta shell/sidebar/header/shortcut dan
   dashboard/login, tanpa design system atau dependency baru.
3. Focused test selama perubahan; satu gate frontend dan browser alur berubah.
   Jika perlu perbaikan, ulang hanya gate yang terdampak.
4. Refresh route cache produksi setelah test; smoke redirect/login/asset tanpa
   credential di output, catat bukti dan commit setiap increment terverifikasi.
5. Push setelah work item software selesai; UAT/printer/scheduler tetap ditunda.
