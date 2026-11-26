#!/usr/bin/env python3
"""Generate the corrected reports/analytics plan audio (v2) with Edge TTS.

The text is the Bengali narration in work/reports-analytics-plan-v2-bn.txt.
Lines that start with '#' are generator metadata (for example the
REPORTS-PLAN-V2 marker) and are not spoken.

Usage:
    python3 tools/generate_reports_plan_v2_audio.py
    python3 tools/generate_reports_plan_v2_audio.py --voice bn-BD-NabanitaNeural --rate=-5%
"""

import argparse
import subprocess
import sys
from pathlib import Path

from tts_utils import audio_seconds, synthesize

DEFAULT_TEXT = Path("work/reports-analytics-plan-v2-bn.txt")
DEFAULT_OUT = Path("/home/sahon/Music/mp3/new evo/reports-analytics-plan-v2-bn.mp3")
DEFAULT_VOICE = "bn-BD-PradeepNeural"
DEFAULT_RATE = "-3%"


def spoken_text(raw: str) -> str:
    """Drop metadata/comment lines and normalise blank lines for TTS."""
    lines = []
    for line in raw.splitlines():
        if line.lstrip().startswith("#"):
            continue
        lines.append(line.rstrip())

    paragraphs = []
    current = []
    for line in lines:
        if line.strip():
            current.append(line.strip())
        elif current:
            paragraphs.append(" ".join(current))
            current = []
    if current:
        paragraphs.append(" ".join(current))
    return "\n\n".join(paragraphs).strip()


def tag_mp3(source: Path, target: Path, voice: str, title: str) -> None:
    """Copy the MP3 and add provenance tags without re-encoding the audio."""
    target.parent.mkdir(parents=True, exist_ok=True)
    subprocess.run(
        [
            "ffmpeg", "-y", "-v", "error",
            "-i", str(source),
            "-c", "copy",
            "-metadata", f"title={title}",
            "-metadata", "artist=ZombieCoder",
            "-metadata", "comment=Edge TTS Bengali narration",
            "-metadata", "edge_tts_engine=edge-tts",
            "-metadata", f"edge_tts_voice={voice}",
            str(target),
        ],
        check=True,
    )


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--text-file", type=Path, default=DEFAULT_TEXT)
    parser.add_argument("--out", type=Path, default=DEFAULT_OUT)
    parser.add_argument("--voice", default=DEFAULT_VOICE)
    parser.add_argument("--rate", default=DEFAULT_RATE)
    args = parser.parse_args()

    raw = args.text_file.read_text(encoding="utf-8")
    text = spoken_text(raw)
    if len(text) < 500:
        print(f"Text too short to synthesize: {len(text)} chars", file=sys.stderr)
        return 2

    tmp = args.out.with_suffix(".tmp.mp3")
    chunks = synthesize(text, args.voice, args.rate, tmp)
    tag_mp3(tmp, args.out, args.voice, "রিপোর্ট ও অ্যানালিটিক্স পরিকল্পনা v2")
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
