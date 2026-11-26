<?php

/**
 * Contract gc-mwhc66sp - the Bengali test report must be a real Microsoft Edge
 * TTS recording (not the old eSpeak render) and must keep the file-level
 * solutions for the six red tests.
 *
 * Run from the project root:
 *
 *     php tools/verify/test-solutions-audio.php
 *
 * Prints TEST_SOLUTIONS_AUDIO_OK only when:
 *
 *   1. The mp3 exists, ffprobe reads a real mp3, duration >= 540s, size >= 2 MB.
 *   2. ffmpeg reads the audio stream at 24000 Hz and the file carries the
 *      Edge TTS provenance tags (edge_tts_engine, edge_tts_voice) written by
 *      the generation pipeline.
 *   3. The .txt script states the Edge TTS pipeline with the voice name and
 *      that HTS is not installed on this machine.
 *   4. All six failing test names and the four solution markers are present.
 *
 * Any failed point prints why and exits non-zero.
 */

const MARKER = 'TEST_SOLUTIONS_AUDIO_OK';

const AUDIO_DIR = '/home/sahon/Music/mp3/new evo';
const STEM      = 'order-status-courier-tests-bn';

const EDGE_VOICE  = 'bn-BD-NabanitaNeural';
const EDGE_ENGINE = 'edge-tts';

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

/**
 * The local ffprobe is a Python shim that only reports format/duration/size,
 * so the stream and tag checks below use ffmpeg directly.
 */
function binaryPath(string $name, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    $found = trim((string) shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));

    return $found !== '' && is_executable($found) ? $found : null;
}

function ffprobeBinary(): ?string
{
    return binaryPath('ffprobe', [
        '/home/sahon/.local/bin/ffprobe',
        '/usr/bin/ffprobe',
        '/usr/local/bin/ffprobe',
    ]);
}

function ffmpegBinary(): ?string
{
    return binaryPath('ffmpeg', [
        '/home/sahon/.local/bin/ffmpeg',
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
    ]);
}

$audioPath  = AUDIO_DIR . '/' . STEM . '.mp3';
$scriptPath = AUDIO_DIR . '/' . STEM . '.txt';

// ---------------------------------------------------------------- 1. real mp3
if (! is_file($audioPath)) {
    fail('The mp3 is missing: ' . $audioPath);
} else {
    pass('The mp3 exists.');

    $probe = ffprobeBinary();

    if ($probe === null) {
        fail('ffprobe is not installed, so duration/size cannot be verified.');
    } else {
        $command = escapeshellarg($probe)
            . ' -v error -show_entries format=duration,size -of default=noprint_wrappers=1 '
            . escapeshellarg($audioPath) . ' 2>&1';

        exec($command, $output, $exitCode);
        $meta = implode("\n", $output);

        $duration = null;
        $reportedSize = null;

        if (preg_match('/^duration=([0-9.]+)$/m', $meta, $m)) {
            $duration = (float) $m[1];
        }

        if (preg_match('/^size=([0-9]+)$/m', $meta, $m)) {
            $reportedSize = (int) $m[1];
        }

        if ($exitCode === 0 && $duration !== null) {
            if ($duration >= 540) {
                pass('ffprobe reads a ' . round($duration, 1) . 's mp3 (>= 540s).');
            } else {
                fail('The mp3 is only ' . round($duration, 1) . 's; the expanded report must be at least 540s.');
            }
        } else {
            fail('ffprobe could not read the mp3: ' . $meta);
        }

        $size = $reportedSize ?? filesize($audioPath);

        if ($size >= 2 * 1024 * 1024) {
            pass('The mp3 is ' . round($size / 1048576, 2) . ' MB (>= 2 MB).');
        } else {
            fail('The mp3 is only ' . $size . ' bytes; a real recording must be at least 2 MB.');
        }
    }

    // ------------------------------------------------------------ 2. Edge TTS stream + tags
    $ffmpeg = ffmpegBinary();

    if ($ffmpeg === null) {
        fail('ffmpeg is not installed, so the Edge TTS stream and tags cannot be verified.');
    } else {
        $infoCommand = escapeshellarg($ffmpeg)
            . ' -hide_banner -i ' . escapeshellarg($audioPath) . ' 2>&1';

        exec($infoCommand, $infoLines, $infoExit);
        $info = implode("\n", $infoLines);

        if (! preg_match('/Audio:\s*mp3[^\n]*,\s*24000 Hz/', $info)) {
            fail('The audio stream is not the 24000 Hz stream Edge TTS produces. ffmpeg said: ' . trim($info));
        } else {
            pass('ffmpeg reads a 24000 Hz mp3 stream (Edge TTS signature).');
        }

        if (preg_match('/edge_tts_engine\s*:\s*' . preg_quote(EDGE_ENGINE, '/') . '/i', $info)) {
            pass('The file carries the Edge TTS engine tag: ' . EDGE_ENGINE . '.');
        } else {
            fail('The file has no edge_tts_engine tag proving the Edge TTS pipeline ran.');
        }

        if (preg_match('/edge_tts_voice\s*:\s*' . preg_quote(EDGE_VOICE, '/') . '/i', $info)) {
            pass('The file carries the Edge TTS voice tag: ' . EDGE_VOICE . '.');
        } else {
            fail('The file does not carry the expected edge_tts_voice tag (' . EDGE_VOICE . ').');
        }
    }
}

// ---------------------------------------------------------------- 3. pipeline stated
if (! is_file($scriptPath)) {
    fail('The script is missing: ' . $scriptPath);

    exit(1);
}

$text = (string) file_get_contents($scriptPath);

$pipelineMarkers = [
    'Edge TTS'        => 'the Microsoft Edge TTS pipeline is named',
    EDGE_VOICE        => 'the Bengali Edge TTS voice is named',
    'HTS'             => 'the HTS limitation is still stated',
    'ইনস্টল নেই'      => 'the script says HTS is not installed',
];

foreach ($pipelineMarkers as $needle => $description) {
    if (str_contains($text, $needle)) {
        pass('The script states ' . $description . ': ' . $needle . '.');
    } else {
        fail('The script never states: ' . $needle . ' (' . $description . ').');
    }
}

// ---------------------------------------------------------------- 4. the six red tests
$testNames = [
    'campaign_page_renders_attribution_context_and_funnel_hooks',
    'home_page_renders_the_modern_theme_with_batched_rails',
    'analytics_trend_chart_receives_a_series_array',
    'sample_template_renders_a_post_order_form_for_the_product',
    'sample_template_form_fields_reach_the_campaign_cart',
    'settings_page_never_prints_the_stored_webhook_token',
];

foreach ($testNames as $name) {
    if (str_contains($text, $name)) {
        pass('The script names the failing test: ' . $name . '.');
    } else {
        fail('The script does not name the failing test: ' . $name . '.');
    }
}

// ---------------------------------------------------------------- 5. the solution paths
$solutions = [
    'sample-template'               => 'the removed sample-template is named as the cause',
    'th3'                           => 'retargeting to the live th3 template is named',
    'fm-events-section'             => 'the home fm-events-section assertion is named',
    'admin-analytics-behaviour.js'  => 'the external analytics series file is named',
    'data-copy-url'                 => 'the masked webhook token copy button is named',
    'WebhookTokenMaskTest'          => 'the webhook test class is named',
];

foreach ($solutions as $needle => $description) {
    if (str_contains($text, $needle)) {
        pass('The script contains the solution marker "' . $needle . '": ' . $description . '.');
    } else {
        fail('The script is missing the solution marker "' . $needle . '".');
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
