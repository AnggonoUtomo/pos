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
  Dark: pertahankan kepekatan palette sebelum penyeragaman netral. Sidebar,
  workspace, panel dan topbar memakai rona yang selaras dengan tema terpilih,
  bukan seluruh dark disamakan abu-abu. Stone mempertahankan palette existing.
- Aksen solid tombol/menu aktif/logo memakai warna pekat light pada kedua
  mode. Token `primary-solid` terpisah dari `primary` agar teks link dan mix
  surface dark tidak ikut berubah; foreground solid menjaga kontras.
- Menu aktif tegas dan icon semantic, tanpa badge Aktif pada sidebar; status
  transaksi tetap bermakna. Tidak
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
