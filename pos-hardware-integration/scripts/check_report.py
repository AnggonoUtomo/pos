#!/usr/bin/env python3
"""Read-only structural checker for PIMS-style hardware task REPORT.

Usage: python3 scripts/check_report.py tasks/TASK-HW-XXX/REPORT.md
"""

import argparse
from pathlib import Path
import re
import sys


REQUIRED_HEADINGS = [
    "Scope delivered",
    "File changes",
    "Tests",
    "Invariants and regressions",
    "Hardware evidence",
    "Virtual and physical acceptance",
    "Rollback verification",
    "Remaining risks",
    "Final status",
]


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("report", type=Path)
    args = parser.parse_args()
    if not args.report.is_file():
        print(f"FAIL: REPORT not found: {args.report}")
        return 2

    content = args.report.read_text(encoding="utf-8")
    found = list(re.finditer(r"(?m)^##\s+(.+?)\s*$", content))
    sections = {}
    for index, match in enumerate(found):
        end = found[index + 1].start() if index + 1 < len(found) else len(content)
        sections[match.group(1).strip().lower()] = content[match.end() : end].strip()

    problems = []
    for heading in REQUIRED_HEADINGS:
        body = sections.get(heading.lower(), "")
        if not body:
            problems.append(f"Missing/empty section: {heading}")
        elif re.search(r"\b(TODO|TBD|BELUM DIISI)\b", body, re.IGNORECASE):
            problems.append(f"Placeholder remains in: {heading}")

    final = sections.get("final status", "")
    if final and not re.search(r"\b(COMPLETE|BLOCKED)\b", final, re.IGNORECASE):
        problems.append("Final status must state COMPLETE or BLOCKED")
    acceptance = sections.get("virtual and physical acceptance", "")
    if acceptance and not re.search(
        r"(?im)^\s*Software acceptance:\s*(?:VIRTUAL_PASSED|FAILED|NOT_RUN)\b",
        acceptance,
    ):
        problems.append("Explicit Software acceptance status required")
    if acceptance and not re.search(
        r"(?im)^\s*Physical acceptance:\s*(?:PHYSICAL_PENDING|HARDWARE_VERIFIED|FAILED)\b",
        acceptance,
    ):
        problems.append("Explicit Physical acceptance status required")
    if problems:
        for problem in problems:
            print(f"FAIL: {problem}")
        return 1
    print("PASS: REPORT structure complete (manual evidence review still required)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
