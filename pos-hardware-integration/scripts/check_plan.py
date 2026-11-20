#!/usr/bin/env python3
"""Read-only structural checker for PIMS-style hardware task PLAN.

Usage: python3 scripts/check_plan.py tasks/TASK-HW-XXX/PLAN.md
       python3 scripts/check_plan.py tasks/TASK-HW-XXX/PLAN.md --require-approved
"""

import argparse
from pathlib import Path
import re
import sys


REQUIRED_HEADINGS = [
    "Baseline read-only",
    "Scope",
    "File allowlist",
    "Contract and regressions",
    "Implementation steps",
    "Test/UAT",
    "Virtual testing and physical gate",
    "Rollback",
    "Risk & mitigation",
    "Owner approval",
]


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("plan", type=Path)
    parser.add_argument("--require-approved", action="store_true")
    parser.add_argument("--approval", type=Path, default=None)
    args = parser.parse_args()
    if not args.plan.is_file():
        print(f"FAIL: PLAN not found: {args.plan}")
        return 2

    content = args.plan.read_text(encoding="utf-8")
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

    if args.require_approved:
        approval_path = args.approval or args.plan.with_name("APPROVAL.md")
        if not approval_path.is_file():
            problems.append(f"Missing approval file: {approval_path}")
        else:
            approval = approval_path.read_text(encoding="utf-8")
            if not re.search(r"(?im)^\s*(?:\*\*)?Status:\s*APPROVED\b", approval):
                problems.append(f"Approval is not explicitly APPROVED: {approval_path}")
            if re.search(r"(?m)^- \[x\] PENDING", approval):
                problems.append("Approval still has PENDING checkbox selected")

    if problems:
        for problem in problems:
            print(f"FAIL: {problem}")
        return 1
    print("PASS: PLAN structure complete" + (" and approval recorded" if args.require_approved else ""))
    return 0


if __name__ == "__main__":
    sys.exit(main())
