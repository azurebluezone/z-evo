#!/usr/bin/env python3
"""Generate the dev-first v3 reports/analytics plan audio with Edge TTS.

The narration lives in work/reports-seed-first-v3-bn.txt. Lines starting
with '#' are metadata (for example the SEED-FIRST-V3 marker) and are not spoken.

Usage:
    python3 tools/generate_reports_seed_first_v3_audio.py
    python3 tools/generate_reports_seed_first_v3_audio.py --voice bn-BD-NabanitaNeural --rate=-5%
"""

import argparse
import sys
from pathlib import Path

from generate_reports_plan_v2_audio import spoken_text, tag_mp3
from tts_utils import audio_seconds, synthesize

DEFAULT_TEXT = Path("work/reports-seed-first-v3-bn.txt")
DEFAULT_OUT = Path("/home/sahon/Music/mp3/new evo/reports-seed-first-v3-bn.mp3")
DEFAULT_VOICE = "bn-BD-PradeepNeural"
DEFAULT_RATE = "-3%"
DEFAULT_TITLE = "রিপোর্ট ও অ্যানালিটিক্স পরিকল্পনা v3 — seed-first"


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--text-file", type=Path, default=DEFAULT_TEXT)
    parser.add_argument("--out", type=Path, default=DEFAULT_OUT)
    parser.add_argument("--voice", default=DEFAULT_VOICE)
    parser.add_argument("--rate", default=DEFAULT_RATE)
    parser.add_argument("--title", default=DEFAULT_TITLE)
    args = parser.parse_args()

    raw = args.text_file.read_text(encoding="utf-8")
    text = spoken_text(raw)
    if len(text) < 500:
        print(f"Text too short to synthesize: {len(text)} chars", file=sys.stderr)
        return 2

    tmp = args.out.with_suffix(".tmp.mp3")
    chunks = synthesize(text, args.voice, args.rate, tmp)
    tag_mp3(tmp, args.out, args.voice, args.title)
    tmp.unlink(missing_ok=True)

    seconds = audio_seconds(args.out)
    print(f"WROTE {args.out}")
    print(f"BYTES {args.out.stat().st_size}")
    print(f"SECONDS {seconds if seconds is not None else 'unknown'}")
    print(f"CHARS {len(text)}")
    print(f"CHUNKS {chunks}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
