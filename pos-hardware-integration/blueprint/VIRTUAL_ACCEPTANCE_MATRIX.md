# Virtual vs Physical Acceptance Matrix

## Two independent result columns

Every UAT that involves physical hardware shall report BOTH `Software` and `Hardware` verdicts. Never collapse them into one ambiguous `PASS`.

| ID | Scenario | Virtual acceptance | Physical acceptance |
|---|---|---|---|
| VUAT-01 | HC4208DB scanner input | Fake keyboard emits characters/Enter and authorized lookup succeeds | Real HC4208DB USB/2.4G/Bluetooth scans accurately |
| VUAT-02 | Duplicate intentional scans | Two scan events correctly change quantity twice | Real repeated trigger behaves as intended |
| VUAT-03 | Android tablet layout | Responsive viewport/touch emulation passes | Real Android Chrome tablet input/rotation passes |
| VUAT-04 | CT420D TSPL/CPCL command output | Bytes/snapshot pass syntax/expected command checks | Real CT420D consumes correct language and prints label |
| VUAT-05 | Label preview | Layout/barcode rendering matches saved fixture | Real dimensions, gap sensor, contrast and scan readability pass |
| VUAT-06 | Gateway claim and ack | Fake gateway state transitions/idempotency pass | Real local gateway connects and sends printer job |
| VUAT-07 | Offline and retry | Fake failures produce correct safe statuses | Real unplug/replug recovery tested |
| VUAT-08 | Tenant/branch isolation | Unauthorized API calls fail | Gateway pairing on actual branch tested |
| VUAT-09 | Feature OFF | Baseline regression and absence of hardware jobs | Pilot activation/deactivation verified with hardware |
| VUAT-10 | Receipt ESC/POS | Only generic contract/mock; does not use CT420D | Separate actual ESC/POS receipt printer still required |

## Development acceptance gates

- **VG0:** baseline discovery read-only + impact report approved.
- **VG1:** UI mockup, original checkout regression snapshots and feature flag OFF tests approved.
- **VG2:** virtual scanner, label builder and label preview tests pass.
- **VG3:** fake gateway, idempotency, retry, failure and tenant isolation pass.
- **VG4:** virtual REPORT completed. Status = `VIRTUAL_PASSED` + `PHYSICAL_PENDING`.

`VG4` permits **development phase handoff only**. It explicitly **does not** override PIMS code approval requirements or authorize deployment.

## Physical acceptance gates (deferred)

- **PG1:** CT420D real USB driver connection, actual TSPL/CPCL label print, media sensor/gap/alignment and scanned barcode verification.
- **PG2:** HC4208DB real USB/dongle/Bluetooth connectivity and scan correctness in PC/Android Chrome.
- **PG3:** gateway end-to-end real print, offline recovery, `uncertain` status semantics and optional supported device capabilities.
- **PG4:** owner acceptance and **explicit** pilot enablement; otherwise remain flagged OFF.

Until PG1–PG4 pass, hardware-compatibility and production-ready checkboxes remain **unchecked** even if all software work is complete.

## Report status template

```text
Software acceptance: VIRTUAL_PASSED | FAILED | NOT_RUN
Physical acceptance: PHYSICAL_PENDING | HARDWARE_VERIFIED | FAILED
Deployment authorization: NOT_APPROVED | APPROVED_FOR_PILOT
Existing POS regression: PASSED | FAILED | NOT_RUN
```

**Initial state of this documentation: `NOT_RUN / PHYSICAL_PENDING / NOT_APPROVED / NOT_RUN`.** No development tests or real hardware tests have been executed by this document bundle.
