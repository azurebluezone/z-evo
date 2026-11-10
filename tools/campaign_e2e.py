#!/usr/bin/env python3
"""Run the campaign verification chain and keep an evidence log.

The runner is deliberately small: it executes the existing PHPUnit filters in
order, writes the full output to work/logs/campaign-e2e-<timestamp>.log, and
updates work/logs/campaign-e2e-latest.json. A failure in one step does not hide
the later steps, so one run shows the whole state.

Usage:
    python3 tools/campaign_e2e.py
    python3 tools/campaign_e2e.py --filter CampaignEndToEnd
    python3 tools/campaign_e2e.py --quick
"""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOG_DIR = ROOT / "work" / "logs"

DEFAULT_STEPS = [
    ("schema", "CampaignPricingSchemaTest", "campaign pricing columns and types"),
    ("resolver", "CampaignPricingTest", "fixed/percent/variant/fallback rules"),
    ("landing", "LandingCampaignOrderTest", "landing render, cart, campaign validation"),
    ("e2e", "CampaignEndToEndTest", "admin create -> landing -> cart -> order -> snapshot"),
]

QUICK_STEPS = DEFAULT_STEPS[:3]


def run_step(name: str, phpunit_filter: str, description: str) -> dict:
    command = ["php", "artisan", "test", f"--filter={phpunit_filter}"]
    started = datetime.now(timezone.utc)
    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            capture_output=True,
            text=True,
            timeout=600,
        )
        output = (result.stdout or "") + (result.stderr or "")
        exit_code = result.returncode
    except subprocess.TimeoutExpired as exc:
        output = f"TIMEOUT after 600s\n{exc.stdout or ''}\n{exc.stderr or ''}"
        exit_code = 124
    except FileNotFoundError as exc:
        output = f"COMMAND NOT FOUND: {exc}"
        exit_code = 127

    finished = datetime.now(timezone.utc)
    return {
        "name": name,
        "description": description,
        "filter": phpunit_filter,
        "command": " ".join(command),
        "exitCode": exit_code,
        "seconds": round((finished - started).total_seconds(), 2),
        "output": output,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--filter",
        dest="single_filter",
        help="Run only one PHPUnit filter instead of the full chain.",
    )
    parser.add_argument(
        "--quick",
        action="store_true",
        help="Skip the full E2E test and run schema/resolver/landing only.",
    )
    args = parser.parse_args()

    steps = QUICK_STEPS if args.quick else DEFAULT_STEPS
    if args.single_filter:
        steps = [("custom", args.single_filter, "single filter")]

    LOG_DIR.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    log_path = LOG_DIR / f"campaign-e2e-{stamp}.log"
    latest_path = LOG_DIR / "campaign-e2e-latest.json"

    results = []
    for name, phpunit_filter, description in steps:
        print(f"== {name}: {description} ==", flush=True)
        result = run_step(name, phpunit_filter, description)
        results.append(result)
        print(f"exit={result['exitCode']} in {result['seconds']}s", flush=True)
        if result["output"]:
            print(result["output"], flush=True)

    payload = {
        "timestamp": stamp,
        "allPassed": all(item["exitCode"] == 0 for item in results),
        "steps": [
            {key: value for key, value in item.items() if key != "output"}
            for item in results
        ],
    }

    with log_path.open("w", encoding="utf-8") as handle:
        handle.write(f"# Campaign E2E run {stamp}\n\n")
        for item in results:
            handle.write(f"## {item['name']} — exit {item['exitCode']}\n")
            handle.write(f"Command: `{item['command']}`\n\n")
            handle.write("```text\n")
            handle.write(item["output"].rstrip())
            handle.write("\n```\n\n")

    latest_path.write_text(json.dumps(payload, indent=2) + "\n", encoding="utf-8")

    if payload["allPassed"]:
        print("CAMPAIGN_E2E_OK", flush=True)
        return 0

    print("CAMPAIGN_E2E_FAIL", flush=True)
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
