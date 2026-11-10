#!/usr/bin/env python3
"""Shared Edge TTS helpers for the project's Bengali audit audio.

Edge TTS rejects a single very long request, so the text is split on
paragraph/sentence boundaries, each chunk is synthesized with the CLI, and the
parts are concatenated with ffmpeg. The CLI is used instead of the Python
Communicate class because the installed library version returned
NoAudioReceived for long Bengali text while the CLI handled the same chunks.
"""

import re
import shutil
import subprocess
import tempfile
from pathlib import Path


def split_text(text: str, max_chars: int = 3000) -> list[str]:
    """Split on paragraph/sentence boundaries so Edge TTS gets bounded chunks."""
    paragraphs = [p.strip() for p in re.split(r"\n\s*\n", text) if p.strip()]
    chunks: list[str] = []
    current = ""

    for paragraph in paragraphs:
        sentences = re.split(r"(?<=[।!?])\s+", paragraph)
        for sentence in sentences:
            while len(sentence) > max_chars:
                if current:
                    chunks.append(current)
                    current = ""
                chunks.append(sentence[:max_chars])
                sentence = sentence[max_chars:]
            if not sentence:
                continue

            candidate = f"{current}\n\n{sentence}".strip() if current else sentence
            if len(candidate) > max_chars:
                if current:
                    chunks.append(current)
                current = sentence
            else:
                current = candidate

    if current:
        chunks.append(current)
    return chunks


def synthesize(text: str, voice: str, rate: str, out: Path) -> int:
    """Synthesize text in chunks and concatenate into one MP3. Returns chunk count."""
    out.parent.mkdir(parents=True, exist_ok=True)
    chunks = split_text(text)

    edge_tts_bin = shutil.which("edge-tts")
    if not edge_tts_bin:
        raise RuntimeError("edge-tts CLI not found on PATH")

    with tempfile.TemporaryDirectory(prefix="landing-tts-") as tmp:
        parts: list[Path] = []
        for index, chunk in enumerate(chunks):
            text_file = Path(tmp) / f"part-{index:03d}.txt"
            part = Path(tmp) / f"part-{index:03d}.mp3"
            text_file.write_text(chunk, encoding="utf-8")
            subprocess.run(
                [
                    edge_tts_bin,
                    "--voice", voice,
                    f"--rate={rate}",
                    "--file", str(text_file),
                    "--write-media", str(part),
                ],
                check=True,
            )
            if not part.exists() or part.stat().st_size == 0:
                raise RuntimeError(f"edge-tts produced no audio for chunk {index}")
            parts.append(part)

        list_file = Path(tmp) / "concat.txt"
        list_file.write_text(
            "".join(f"file '{part}'\n" for part in parts),
            encoding="utf-8",
        )
        subprocess.run(
            [
                "ffmpeg", "-y", "-v", "error",
                "-f", "concat", "-safe", "0",
                "-i", str(list_file),
                "-c", "copy",
                str(out),
            ],
            check=True,
        )

    return len(chunks)


def audio_seconds(path: Path) -> float | None:
    """Return the MP3 duration with ffprobe, or None if it cannot be read."""
    try:
        result = subprocess.run(
            [
                "ffprobe",
                "-v", "error",
                "-show_entries", "format=duration",
                "-of", "csv=p=0",
                str(path),
            ],
            capture_output=True,
            text=True,
            check=True,
        )
        return float(result.stdout.strip())
    except (subprocess.CalledProcessError, ValueError):
        return None
