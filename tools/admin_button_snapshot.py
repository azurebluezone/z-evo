#!/usr/bin/env python3
"""
admin_button_snapshot.py - list the button class strings the admin used
BEFORE the rewrite, so tools/verify_admin_buttons.php can still prove the
<x-admin.button> conversion was class-for-class faithful.

    python3 tools/admin_button_snapshot.py [commit]

The snapshot lives in /tmp, which does not survive a reboot; this regenerates
it from any commit. The default is the commit before the conversion, found by
walking back from HEAD to the last one that still contains hand-written
<button class="..."> markup.

    python3 tools/admin_button_snapshot.py 022bc96 > /tmp/opencode/admin_combos_before.json
    php tools/verify_admin_buttons.php
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BTN = re.compile(r"<button\b[^>]*>", re.S | re.I)
CLASS = re.compile(r"""class\s*=\s*(["'])(.*?)\1""", re.S)


def files_in(commit: str) -> list[str]:
    out = subprocess.run(
        ["git", "ls-tree", "-r", "--name-only", commit, "resources/views/backEnd"],
        cwd=ROOT, capture_output=True, text=True, check=True,
    )
    return [l for l in out.stdout.split("\n") if l.endswith(".blade.php")]


def snapshot(commit: str) -> Counter:
    counts: Counter = Counter()
    for path in files_in(commit):
        blob = subprocess.run(
            ["git", "show", f"{commit}:{path}"],
            cwd=ROOT, capture_output=True, text=True,
        )
        if blob.returncode != 0:
            continue
        for tag in BTN.findall(blob.stdout):
            m = CLASS.search(tag)
            if m:
                counts[" ".join(m.group(2).split())] += 1
    return counts


def commit_before_conversion() -> str:
    """The most recent commit that still holds hand-written button markup."""
    out = subprocess.run(
        ["git", "log", "--format=%H", "-40"], cwd=ROOT, capture_output=True, text=True, check=True,
    )
    for sha in out.stdout.split("\n"):
        if not sha:
            continue
        probe = subprocess.run(
            ["git", "grep", "-l", "-E", r'<button[^>]*class=', sha, "--", "resources/views/backEnd"],
            cwd=ROOT, capture_output=True, text=True,
        )
        if probe.returncode == 0 and probe.stdout.strip():
            return sha
    raise SystemExit("no commit with hand-written buttons found in the last 40")


if __name__ == "__main__":
    commit = sys.argv[1] if len(sys.argv) > 1 else commit_before_conversion()
    counts = snapshot(commit)
    json.dump(counts, sys.stdout, indent=0)
    print(f"// {len(counts)} distinct class strings across "
          f"{sum(counts.values())} buttons at {commit}", file=sys.stderr)