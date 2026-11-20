# Primary References — Verify Before Coding

Accessed/verified during documentation preparation: **18 November 2026**. The source pages can evolve; inspect current details and applicable printer firmware before coding or procurement.

**Hardware evidence added 19 November 2026:** user-provided screenshot of **CASHCOW CT420D** specification (label/waybill direct thermal, TSPL+CPCL, USB/Bluetooth/Serial, 20–120mm liner media, 203DPI), and official CASHCOW **HC-4208DB** catalogue. See `HARDWARE_PROFILE_CASHCOW.md`. This supersedes using generic Xprinter/Epson as the *owned* pilot devices; those remain alternatives for later receipt-printer UAT.

- [Epson TM-m30III — official product page](https://www.epson.co.id/For-Work/Printers/POS-Printers/Epson-TM-m30III-Thermal-Receipt-POS-Printer/p/C31CK50302): browser-oriented ePOS SDK and Server Direct Print features, exact model dependent.
- [Epson TM-T82IV — official product page](https://www.epson.co.id/For-Work/Printers/POS-Printers/Epson-TM-T82IV-Thermal-Receipt-Printer/p/C31CL47412): JavaScript SDK is listed for this model; **do not confuse with TM-T82X-II**.
- [Epson ESC/POS reference](https://download4.epson.biz/sec_pubs/pos/reference_en/escpos/): command capability varies by printer model/firmware.
- [Epson ESC p cash drawer pulse](https://download4.epson.biz/sec_pubs/pos/reference_en/escpos/esc_lp.html): command valid only for equipped models.
- [Zebra DS2208 reference manual](https://www.zebra.com/content/dam/support-dam/en/documentation/unrestricted/guide/product/ds2208-prg-en.pdf): USB Keyboard HID and host interface behavior.
- [Honeywell Voyager XP 1470g](https://automation.honeywell.com/us/en/products/productivity-solutions/barcode-scanners/general-purpose-handheld/voyager-xp-1470g-general-duty-scanner): model variant required for 2D.
- [QZ Tray — official guide](https://qz.io/docs/getting-started): desktop bridge and raw printing candidate; optional integration, review license/certificate for silent printing.
- [QZ Tray print server](https://qz.io/docs/print-server): alternative reference for mobile browser using a dedicated print server.
- [MDN Window.print](https://developer.mozilla.org/en-US/docs/Web/API/Window/print): standard browser print dialog.
- [MDN Local Network Access](https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Local_network_access): browser restrictions for access to LAN devices from HTTPS sites.
- [CASHCOW Official Handheld Scanner](https://www.cashcow.co.id/our-products/handheld-scanner/): HC-4208DB Bluetooth/2.4GHz/USB, 1D/2D and smart dock.
- [Playwright input](https://playwright.dev/docs/input): browser keyboard/Enter simulation.
- [Playwright emulation](https://playwright.dev/docs/emulation): viewport, touch and mobile browser emulation—not hardware pairing.
- [Playwright network](https://playwright.dev/docs/network): API response mocking and failure simulation.
- [TSC TSPL/TSPL2 programming manual index](https://in.tscprinters.com/en/downloads): command syntax reference; compatibility with CT420D firmware still needs real UAT.

## Procurement is not an acceptance gate

**Owned pilot devices are CASHCOW CT420D label printer and HC-4208DB scanner.** Future receipt-printer candidates include Xprinter XP-K260USE (80mm USB/LAN) and Epson models, but are not claimed as purchased. Marketplace titles/port variants can be misleading. Confirm exact model, protocol, ports, paper size, driver, cutter/drawer support and real UAT **before** listing as "supported".
