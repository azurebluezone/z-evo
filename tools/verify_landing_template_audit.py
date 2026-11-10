#!/usr/bin/env python3
"""Verify the landing-template audit report, manifest and Bengali audio.

Exit 0 and print TEMPLATE_MANIFEST_OK only when:
  - the report and audio exist,
  - their SHA-256 hashes match the manifest,
  - the report has at least 7 "## " sections and contains the product binding finding,
  - ffprobe reports at least 420 seconds of audio.

This is the recorded check for goal contract gc-mw8qith2. It does not trust
the agent's earlier exit code; it re-reads the artifacts itself.
"""

import hashlib
import json
import re
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
MANIFEST = ROOT / "work" / "landing-template-audit.json"
MIN_SECTIONS = 7
MIN_SECONDS = 420


def fail(message: str) -> "NoReturn":
    print(f"TEMPLATE_MANIFEST_FAIL: {message}", file=sys.stderr)
    raise SystemExit(1)


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main() -> int:
    if not MANIFEST.is_file():
        fail(f"missing manifest: {MANIFEST}")

    data = json.loads(MANIFEST.read_text(encoding="utf-8"))

    report = ROOT / data["report"]
    audio = Path(data["audio"])

    if not report.is_file():
        fail(f"missing report: {report}")
    if not audio.is_file():
        fail(f"missing audio: {audio}")

    if sha256(report) != data["report_sha256"]:
        fail("report hash does not match the manifest")
    if sha256(audio) != data["audio_sha256"]:
        fail("audio hash does not match the manifest")

    text = report.read_text(encoding="utf-8")
    sections = len(re.findall(r"^## ", text, re.M))
    if sections < MIN_SECTIONS:
        fail(f"report has {sections} sections, need at least {MIN_SECTIONS}")
    if sections != data.get("sections"):
        fail(f"manifest says {data.get('sections')} sections, report has {sections}")
    if "campaigns.product_id" not in text:
        fail("report does not contain the campaigns.product_id finding")
    if not data.get("key_findings"):
        fail("manifest has no key_findings")

    ffprobe = shutil.which("ffprobe") or "/home/sahon/.local/bin/ffprobe"
    try:
        seconds = float(
            subprocess.check_output(
                [
                    ffprobe,
                    "-v", "error",
                    "-show_entries", "format=duration",
                    "-of", "csv=p=0",
                    str(audio),
                ],
                text=True,
            ).strip()
        )
    except (OSError, subprocess.CalledProcessError, ValueError) as exc:
        fail(f"ffprobe could not read the audio duration: {exc}")

    if seconds < MIN_SECONDS:
        fail(f"audio is {seconds:.2f}s, need at least {MIN_SECONDS}s")
    if abs(seconds - float(data.get("audio_seconds", -1))) > 1.0:
        fail(
            f"manifest says {data.get('audio_seconds')}s, ffprobe says {seconds:.2f}s"
        )

    print(f"TEMPLATE_MANIFEST_OK {sections} {seconds:.2f}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
