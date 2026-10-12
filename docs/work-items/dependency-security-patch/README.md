# Perbaikan Security Dependency

Status: In Progress. Disetujui user pada 12 Oktober 2026 sebagai pekerjaan
terpisah dari penutupan Inventory/Stock.

## Scope

Perbarui dependency transitive existing tanpa mengganti stack, major version,
constraint dependency langsung, credential atau database. Target:

| Package | Versi awal | Target kompatibel | Advisory |
| --- | --- | --- | --- |
| shell-quote | 1.10.0 | >=1.11.0; resolved 1.12.0 | GHSA-pqg4-j6r4-53mv |
| source-map-js | 1.2.1 | 1.2.2 | GHSA-68fv-2mgg-jv7q |
| league/commonmark | 2.10.1 | 2.10.2 | GHSA-97jj-33gv-5xf9, GHSA-3q6v-r5mr-hxv8 |

Dev tambahan: typescript-eslint 8.24.1 menjadi 8.48.0; brace-expansion 1.1.18
menjadi 1.1.21 dan 2.1.4 menjadi 2.1.7. Rantai braces/micromatch/fast-glob lama
tidak lagi digunakan tooling tersebut. Constraint root tetap sama.

Temuan audit menunjukkan versi rentan terpasang, bukan bukti bahwa endpoint
Stock menerima input Markdown/source map atau mengeksekusi shell pengguna.
Jangan menambah endpoint atau mitigasi aplikasi yang tidak memiliki consumer.

Audit npm penuh juga menemukan brace-expansion dan rantai braces/fast-glob
di tooling lint. User menyetujui tambahan scope dev dependency pada
12 Oktober 2026: update typescript-eslint dalam major 8 existing dan dependency
transitive terkait, tanpa mengganti konfigurasi lint atau major stack.

## Acuan

- [Plan](plan.md) dan [Tasks](tasks.md).
- [Quality](../../QUALITY.md) dan [Workflow](../../WORKFLOW.md).
- [shell-quote](https://github.com/advisories/GHSA-pqg4-j6r4-53mv).
- [source-map-js](https://github.com/advisories/GHSA-68fv-2mgg-jv7q).
- [CommonMark HTML](https://github.com/advisories/GHSA-97jj-33gv-5xf9).
- [CommonMark Table](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8).

## Non-Scope

Tidak mengubah fitur, schema, role, akun, tema, Old-docs atau artefak SampleUI.
Tidak memakai audit fix --force, menghapus package, atau upgrade stack mayor.
