# UI/UX Specification — Isolated Hardware Workspace

**Reference:** https://react.tailwind-admin.com/ (visual inspiration only; do not copy or overwrite the running POS theme). Mock up the **whole hardware module** before implementing its first component. Use `diagrams/hardware_workspace_mockup_16x9.svg` as a conceptual illustration, NOT an exact screenshot of existing POS.

## New workspace map (all behind opt-in feature flag)

1. **Hardware Overview:** terminal online/offline, printer pairing, last print status, scanner test status, errors, quick actions.
2. **Terminals:** browse terminals per branch; add/edit only after authorized scope validation.
3. **Printers:** selected model/connection, 58/80 mm paper, capability badges (`cut`, `drawer`, `qr`), choose default and test print.
4. **Barcode Scanner:** scanner input panel, example scan, suffix Enter explanation, duplicate-scan quantity preview, Android Bluetooth setup.
5. **Print Queue:** statuses, `sent` vs `uncertain`, job detail, only authorized manual reprint and job audit.
6. **User Guide:** floating help button opens sheet with images/examples, pairing, paper change, Bluetooth instructions, offline troubleshooting.
7. **Label Printing (separate workspace behind flag):** CT420D label template selection (`Produk`, `Harga`, optional `Waybill`), validated dimensions in mm, SKU/barcode preview, copy count, Test Label, scan-back validation and wrong-printer warning. **Never reuse receipt template on this printer.**
8. **Developer Virtual Test Panel (non-production only):** fake scanner text+Enter input, fake gateway status selector (online/offline/paper-out/ack-lost), generated TSPL/CPCL label preview and test log. This panel **must not appear in active customer workspaces** unless explicitly approved for diagnostics.

## UI norms from user's PIMS baseline

- Complete module before starting the next, including all its UI/UX and demo seed fixtures.
- Command palette in top nav **only if a compatible existing command palette already exists or its additive seam is separately approved**. Hardware actions get distinct colored icons; never replace app's global nav.
- Eleven accent themes: `default`, `clean default`, `grey`, `stone`, `forest`, `ruby`, `quartz`, `aurora`, `cooper`, `safron`, `plum` when adopted by the existing design system. Do not fork or override theme engine simply for this module.
- Sonner toast success/error/processing, with exact status semantics (`Tugas cetak dikirim`, not `Struk tercetak` unless verified).
- Tables: standard PIMS pagination `<< < Prev … 1 2 3 4 5 … 10 Next > >>`; debounced server-side search with minimum three characters where appropriate.
- Soft delete/disable evaluated per entity: terminal/gateway should use revoke/deactivate; never hard-delete transaction audit.
- Responsive layout PC/laptop plus Android tablet 8–11 inch, landscape first but portrait functional; touch target comfortable and avoid hover-only controls.
- Keep existing POS primary cashier workspace intact. Hardware module is **separate navigation/workspace** unless a narrow seam is explicitly approved.

## Mockup approval checklist

- [ ] Existing UI framework, CSS tokens, breakpoints and layout inspected (read only).
- [ ] One complete design across Overview, Devices, Scanner Test, Print Queue, User Guide.
- [ ] Windows desktop 1366 × 768/1920 × 1080 and Android tablet landscape/portrait samples.
- [ ] Offline printer, permission denied, paper out, unsupported model, unpaired gateway and uncertain job states visible.
- [ ] Clear manual browser-print fallback and its expected dialog.
- [ ] Owner approves screenshot/mockup before coding.

## Example UX copy

- `Printer belum dipasangkan. Pilih printer atau gunakan Cetak Browser.`
- `Tugas cetak sudah dikirim ke gateway. Periksa printer untuk memastikan struk keluar.`
- `Status cetak tidak pasti. Periksa struk fisik sebelum mengulang.`
- `Barcode tidak ditemukan dalam katalog cabang ini.`
- `Perangkat ini membutuhkan gateway lokal untuk cetak otomatis.`
- `CASHCOW CT420D adalah printer label TSPL/CPCL. Pilih printer struk ESC/POS untuk bukti transaksi.`
- `Label berhasil dikirim. Pindai label dengan HC-4208DB untuk memverifikasi hasil cetak.`
- `Simulasi berhasil. Perangkat fisik belum diuji.`
- `Uji virtual selesai; status integrasi USB/Bluetooth masih menunggu perangkat.`

## Accessibility and safety

All controls keyboard and touch accessible. Scan capture must not steal focus from payment fields or modal confirmation. A visibly enabled *Scan Mode* can own input focus. Drawer and reprint actions request authorization context and must remain unavailable if feature flag off.
