# ADR Global

Folder ini menyimpan keputusan lintas module atau keputusan tingkat project.
Keputusan yang hanya berlaku pada satu module disimpan di folder `decisions/`
module tersebut.

Gunakan format `ADR-NNN-nama-keputusan.md` dan template
[`../templates/ADR.md`](../templates/ADR.md). Status yang digunakan:
`Proposed`, `Accepted`, `Superseded`, atau `Deprecated`.

ADR aktif diindeks pada [`../DECISIONS.md`](../DECISIONS.md).

Aturan:

- Jangan menghapus ADR lama setelah baseline dinyatakan fix.
- Jika keputusan berubah, buat ADR pengganti dan hubungkan keduanya.
- Pada fase penyusunan baseline awal, revisi dokumen aktif boleh dilakukan tanpa
  menyimpan riwayat lama agar developer tidak bingung.
