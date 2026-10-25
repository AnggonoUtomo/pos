# Penyegaran Theme Dashboard

User meminta UI/UX mengikuti arah visual referensi TailwindAdmin light/dark,
tanpa mengubah fungsi existing. Pending operasional/UAT dikerjakan nanti.

Referensi: https://react.tailwind-admin.com/ dan
https://tailwindadmin-reactjs-dark.netlify.app/. Keduanya dilihat melalui
Chrome DevTools MCP, termasuk screenshot setelah loading selesai.
Artefak referensi ignored: storage/framework/testing/ui-references.

## Kontrak Desain

- Sidebar penuh setinggi layar, pemisah tipis; workspace tidak menjadi kartu
  dekoratif besar. Logo POS existing, bukan merek/ilustrasi produk referensi.
- Light: sidebar/topbar putih, workspace sejuk tipis; ringkasan pastel semantic.
  Dark: surface berlapis netral, aksen warna terkontrol, kontras terbaca.
  Stone mempertahankan palette existing; dark lainnya memakai kedalaman
  serupa dengan dasar abu-abu netral dan warna pada aksen interaktif.
- Menu aktif tegas, icon semantic, badge/transaksi tetap bermakna. Tidak
  menambahkan footer sidebar, grafik palsu, tren, shift atau angka demo.
- Kartu maksimal radius8px; tipografi tetap Instrument Sans, letter spacing0.
- Semua accent/mode existing tetap tersedia. Topbar solid di awal, glass saat
  scroll. Sidebar collapse/tooltip/offset tetap bekerja; hover tanpa request.
- Page/dashboard/CRUD/POS tetap memakai shadcn/ui dan token yang sama; auth,
  permission, kalkulasi, posting, audit dan data toko tidak diubah.
- Root langsung login untuk guest, dashboard untuk user login; welcome dihapus.

## Verifikasi

Test redirect/auth/dashboard terfokus, lint/typecheck/build; QA browser
disposable desktop/tablet pada light/dark, satu accent representatif, dashboard
real, sidebar expanded/collapse/scroll, command palette, dialog master dan POS.
Tidak mengubah konfigurasi produksi atau menjalankan fixture di MySQL toko.
