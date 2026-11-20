# Blueprint Index — POS Hardware Integration

**Scope:** an isolated, opt-in hardware integration capability for an established POS SaaS. **No existing system has been inspected** when this version was written. User-supplied hardware evidence identifies **CASHCOW CT420D label printer (TSPL/CPCL)** and **CASHCOW HC-4208DB 1D/2D scanner**, which require physical UAT. All API paths and repository assumptions remain provisional pending discovery.

## Read order

1. `../AGENTS.md` — do-not-change constraints.
2. `BASELINE_AND_BOUNDARY.md` — actual system discovery and stop gates.
3. `HARDWARE_PROFILE_CASHCOW.md` — required hardware-specific profiles and printer protocol separation.
4. `VIRTUAL_FIRST_DEVELOPMENT.md`, `VIRTUAL_ACCEPTANCE_MATRIX.md` — mandatory mock-first implementation and separate test results.
5. `REQUIREMENTS.md`, `TRACEABILITY.md` — functional scope and traceability.
6. `DOMAIN_SPEC.md`, `ARCHITECTURE.md`, `ADRS.md` — design ownership and decisions.
7. `COMMAND_CONTRACTS.md`, `INVARIANTS.md` — API and transaction guarantees.
8. `UI_UX_SPEC.md` — mockup acceptance before development.
9. `VERTICAL_SLICES.md`, `TESTING_AND_GATES.md` — isolated execution and UAT.
10. `REFERENCES.md` — primary sources; verify latest versions before implementation.

## Decisions frozen through v1.3

- Target **Windows PC/laptop browsers** and **Android Chrome tablets**.
- Target receipt printer default **thermal 80 mm LAN**; USB supported with gateway; 58 mm fallback. This is **not the model bought by Ino**.
- **Owned CT420D is a label printer using TSPL/CPCL (not ESC/POS), with USB/Bluetooth/Serial listed.** Label driver is a separate adapter.
- **Owned HC-4208DB scanner** supports 1D/2D USB/Bluetooth/2.4GHz; HID behavior must be tested.
- Generic receipt format ESC/POS through **capability-aware device adapter**, not arbitrary raw client data.
- Scanner USB/Bluetooth HID with `Enter` suffix; camera fallback optional.
- Browser-only POS; local gateway **optional per deployment** but required for common auto-print on tablet when printer lacks validated web API.
- Gateway initiates **outbound** HTTPS polling/WebSocket to SaaS, not SaaS inbound TCP to store LAN.
- Browser print always available where OS printer settings allow; no claim of universal silent printing.
- No offline transactions in this hardware slice; offline sales requires a separate business decision.
- Test representative devices before declaring brand compatibility.
- Existing POS module contracts and schema are the source of truth; additive design only.
- **Virtual-first:** no physical connection required for initial scanner, label, gateway and browser tests. `VIRTUAL_PASSED` and `PHYSICAL_PENDING` must be reported independently. Production compatibility waits for real hardware UAT.
- **In-repo companion:** `<POS_REPO_ROOT>/pos-hardware-integration/`, not `docs/` and not a separate repository.

## Decision register

| ID | Decision | Status |
|---|---|---|
| ADR-HW-001 | Outbound pull local print gateway | Proposed, baseline architecture |
| ADR-HW-002 | Capabilities per device & fallback browser print | Proposed |
| ADR-HW-003 | Immutable receipt snapshot & conservative retry | Proposed |
| ADR-HW-004 | Opt-in feature flag and narrow integration seam | Binding safety rule |
| ADR-HW-006 | Distinct TSPL/CPCL label and ESC/POS receipt adapters | Binding device correctness rule |
| ADR-HW-007 | Virtual-first tests with honest physical pending status | Binding development rule |

## Implementation readiness

- [x] Documentation companion prepared for placement in existing POS repository root
- [ ] Folder copied into owner's actual POS repository (not supplied in this session)
- [ ] Actual POS source discovered read-only
- [ ] Impact and compatibility reviewed
- [ ] UI mockups approved
- [ ] Specific devices selected and test plan approved
- [ ] Task PLAN validated
- [ ] User signed APPROVAL
- [ ] Coding authorized
- [ ] Virtual tests executed and verified (`VIRTUAL_PASSED`)
- [ ] Physical hardware actually tested (`HARDWARE_VERIFIED`)

**No coding is authorized while a required box is unchecked.**
