# Work Item: Patch HTTP Foundation

## Status Dan Scope

In Progress. User menyetujui patch terpisah saat QA riwayat penjualan,
16 Oktober 2026. Composer audit menemukan CVE-2026-108082 pada
symfony/http-foundation7.4.18. Update hanya package ini dalam seri7.4;
composer.json/stack/framework/permission/feature bisnis tidak diubah.

## Bukti Dan Boundary

Header Cache-Control dari request diparsing HeaderBag::parseCacheControl melalui
HeaderUtils::split saat Request dibuat. Regex vendor lama memakai token yang
juga mengonsumsi whitespace dan lookbehind; repeated whitespace sebelum separator
dapat menghabiskan backtrack budget dan membuang directive. Input unauthenticated,
bukan hanya controller POS. Jangan menambal controller atau vendor manual.

Sumber primer: [advisory Symfony](https://symfony.com/cve-2026-108082).
Patch keamanan7.4.19 mempunyai regresi separator whitespace pada7.4.19..20;
follow-up resmi7.4.21 memperbaikinya. Composer dry-run menawarkan satu update
7.4.18 ke7.4.21, tanpa install/remove package lain.

Acceptance: trigger terkontrol tidak membuang Cache-Control directive; token
quoted/normal dan separator whitespace tetap kompatibel; audit bersih dan
full regression/backend/browser yang relevan lulus. Test in-memory memakai
backtrack budget terbatas, bukan DoS request nyata atau benchmark produksi.

Tidak mengubah konfigurasi security/server, menambah dependency, atau mengerjakan
advisory lain tanpa persetujuan. Old-docs/SampleUI/LSP/env tidak disentuh.
Tahapan: [plan](plan.md), [tasks](tasks.md).
