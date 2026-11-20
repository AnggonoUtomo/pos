# Requirements Traceability

All contracts/VS listed here are proposals to be matched to the real codebase. `UAT-xx` definitions are in `TESTING_AND_GATES.md`.

| Requirement IDs | Domain / Contract | Vertical slice | Gate / UAT |
|---|---|---|---|
| 001, 018, 023 | Baseline / ADR-HW-004 | VS-HW-01 onward | G0, UAT-01/10 |
| 002, 003, 015 | Terminal, PrinterProfile / CT-HW-001/002 | VS-HW-01 | G1, UAT-02/03/14 |
| 004, 006 | ScanSession / CT-HW-010 | VS-HW-02 | G2, UAT-04 |
| 005, 006 | ScanSession / CT-HW-010 | VS-HW-03 | G2, UAT-05 |
| 007 | ReceiptSnapshot / CT-HW-005 | VS-HW-04 | G3, UAT-06 |
| 008, 013 | Gateway, PrintJob / CT-HW-003/004/006/007 | VS-HW-05 | G4, UAT-07/12 |
| 009, 011, 017 | PrintJob / CT-HW-005/007 | VS-HW-06 | G5, UAT-08/10/16 |
| 010 | Reprint / CT-HW-008 | VS-HW-06 | G5, UAT-09 |
| 012, 019 | Authorization / All CT | Every VS | G0–G7, UAT-11/13 |
| 014 | CashDrawer / CT-HW-009 | VS-HW-07 | G6, UAT-13 |
| 016 | PrinterCapabilities | VS-HW-05/07 | G4/G6, UAT-15 |
| 020, 021, 022 | Optional adapters | After MVP | G8, UAT-17/18/19 |
| 024 | Fixtures / seeders | Each relevant VS | UAT-20 |
| 025 | CT420D device capabilities / ADR-HW-006 | VS-HW-09 | G9, UAT-21 |
| 026 | Label snapshot / CT-HW-011 + HC4208DB scan | VS-HW-09 | G9, UAT-22 |
| 027 | Real-device UAT, no assumed runtime support | VS-HW-09 / VS-HW-08 | G9, UAT-21/22 |
| 028 | `FakeBarcodeInput`, `FakeGateway`, `FakePrintTransport` | VS-HW-10, scanner/label slices | VG2/VG3, VUAT-01–07 |
| 029 | Two independent acceptance status columns | Every VS and REPORT | VG4, VUAT-04/05/10 |
| 030 | Test-only adapter boundary, feature flag OFF | VS-HW-10 onward | VG1/VG3, VUAT-08/09 |

Before implementation, append exact repository file mappings and test references. Do not mark a row `verified` based on a mock or emulator when the UAT requires real hardware.
