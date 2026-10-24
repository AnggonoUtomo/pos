# Pengaturan Operasional Awal

User menyetujui module baru setelah persiapan UAT menemukan printer fisik belum
tersedia dan jadwal backup ingin opsional di UI. Ini pengecualian scope freeze
yang eksplisit, bukan perluasan modul bisnis MVP.

Hasil: form printer/backup, preferensi cetak POS, runner due backup, adapter
scheduler Windows, permission/audit dan status aman. Target Windows/Laragon LAN.

Di luar scope: raw printer, silent print, upload/download backup via web,
retensi/penghapusan backup, media offsite otomatis, konfigurasi secret/.env,
sertifikat/firewall/DNS dari UI, dan persetujuan UAT fisik otomatis.

Implementasi mengikuti [rencana](plan.md) dan [checklist](tasks.md).
