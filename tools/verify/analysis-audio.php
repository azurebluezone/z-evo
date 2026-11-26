<?php

/**
 * Contract gc-mwh8a9ba - the Bengali analysis audio must exist and be real.
 *
 * Run from the project root:
 *
 *     php tools/verify/analysis-audio.php
 *
 * It prints ANALYSIS_AUDIO_OK only when EVERY point below holds:
 *
 *   1. The mp3 is in the project's audio folder.
 *   2. ffprobe reads it as a real mp3 with more than a minute of audio.
 *   3. The spoken script sits next to it, is not empty, and actually contains
 *      the analysis: the audit log (order notes + inventory movements), the
 *      courier balance, the parcel count, the risk check and the courier's own
 *      401 / 403 answers.
 *   4. The file is big enough to be a real recording (not a stub).
 *
 * Any failed point prints why and exits non-zero.
 */

const MARKER = 'ANALYSIS_AUDIO_OK';

const AUDIO_DIR = '/home/sahon/Music/mp3/new evo';
const AUDIO_NAME = 'order-courier-risk-analysis-bn';

$failures = [];

function fail(string $message): void
{
    global $failures;
    $failures[] = $message;
    echo 'FAIL ' . $message . PHP_EOL;
}

function pass(string $message): void
{
    echo 'PASS ' . $message . PHP_EOL;
}

/** ffprobe is not always on the PATH of a web/PHP process. */
function ffprobeBinary(): ?string
{
    $candidates = ['ffprobe', '/home/sahon/.local/bin/ffprobe', '/usr/bin/ffprobe', '/usr/local/bin/ffprobe'];

    foreach ($candidates as $candidate) {
        $path = $candidate;

        if (! str_contains($candidate, '/')) {
            $found = trim((string) shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
            if ($found === '') {
                continue;
            }
            $path = $found;
        }

        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

$mp3 = AUDIO_DIR . '/' . AUDIO_NAME . '.mp3';
$txt = AUDIO_DIR . '/' . AUDIO_NAME . '.txt';

// ---------------------------------------------------------------- 1. the file is there
if (! is_file($mp3)) {
    fail('The audio file is missing: ' . $mp3);

    exit(1);
}

$bytes = filesize($mp3);

pass('Audio file exists: ' . $mp3 . ' (' . number_format((float) $bytes) . ' bytes)');

if ((float) $bytes <= 50000) {
    fail('The audio file is only ' . $bytes . ' bytes - too small to be a real recording.');
} else {
    pass('The file size is a real recording, not a stub.');
}

// ---------------------------------------------------------------- 2. ffprobe reads it
$ffprobe = ffprobeBinary();

if (! $ffprobe) {
    fail('ffprobe was not found on this machine.');
} else {
    $duration = trim((string) shell_exec(
        escapeshellcmd($ffprobe) . ' -v error -of csv=p=0 -show_entries format=duration ' . escapeshellarg($mp3) . ' 2>/dev/null'
    ));

    $format = trim((string) shell_exec(
        escapeshellcmd($ffprobe) . ' -v error -of csv=p=0 -show_entries format=format_name ' . escapeshellarg($mp3) . ' 2>/dev/null'
    ));

    $codec = trim((string) shell_exec(
        escapeshellcmd($ffprobe) . ' -v error -of csv=p=0 -show_entries stream=codec_name ' . escapeshellarg($mp3) . ' 2>/dev/null'
    ));

    echo '..   ffprobe: format=' . $format . ' codec=' . $codec . ' duration=' . $duration . PHP_EOL;

    if ($format !== '' && ! str_contains(strtolower($format), 'mp3')) {
        fail('ffprobe does not read the file as mp3 (said: ' . $format . ').');
    }

    if ($codec !== '' && ! str_contains(strtolower($codec), 'mp3')) {
        fail('The audio stream is not mp3 (said: ' . $codec . ').');
    }

    if ($duration === '' || (float) $duration < 60) {
        fail('The audio is shorter than a minute (duration: ' . ($duration === '' ? 'unknown' : $duration) . ').');
    } else {
        pass('ffprobe reads a real mp3 of ' . round((float) $duration, 2) . ' seconds (' . round((float) $duration / 60, 1) . ' minutes).');
    }
}

// ---------------------------------------------------------------- 3. the spoken script
if (! is_file($txt)) {
    fail('The spoken script is missing next to the audio: ' . $txt);

    exit(1);
}

$script = (string) file_get_contents($txt);

pass('Spoken script exists: ' . $txt . ' (' . number_format(mb_strlen($script)) . ' characters)');

if (mb_strlen(trim($script)) < 500) {
    fail('The spoken script is empty.');
}

// The analysis itself: every topic the request named has to be IN the script.
$topics = [
    'the audit log'          => ['অডিট লগ'],
    'order notes'            => ['অর্ডার নোট'],
    'inventory movements'    => ['ইনভেন্টরি মুভমেন্ট'],
    'the courier balance'    => ['ব্যালেন্স'],
    'the parcel count'       => ['পার্সেল'],
    'the risk check'         => ['রিস্ক'],
    'the courier 401'        => ['চার শো এক', '৪০১'],
    'the courier 403'        => ['চার শো তিন', '৪০৩'],
    'the two courier buttons' => ['দুইটা কুরিয়ার বাটন', 'স্টেডফাস্ট বাটন'],
];

foreach ($topics as $label => $needles) {
    $found = false;

    foreach ($needles as $needle) {
        if (mb_strpos($script, $needle) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        pass('The script covers ' . $label . '.');
    } else {
        fail('The script does not cover ' . $label . '.');
    }
}

// ---------------------------------------------------------------- verdict
echo str_repeat('-', 72) . PHP_EOL;

if ($failures !== []) {
    echo PHP_EOL . 'FAILED ' . count($failures) . ' check(s):' . PHP_EOL;

    foreach ($failures as $failure) {
        echo ' - ' . $failure . PHP_EOL;
    }

    exit(1);
}

echo MARKER . PHP_EOL;

exit(0);
