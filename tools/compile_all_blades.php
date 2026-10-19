<?php
/**
 * Compile every blade file in the project and report failures.
 *
 *   php /tmp/opencode/compile_all.php
 *
 * Compiling is the cheapest check that a template is still valid PHP: it
 * catches an unclosed directive, a mismatched @if, a route() call that no
 * longer resolves at compile time. Rendering is not needed, and cannot be done
 * for most admin pages without a session.
 */
$base = '/home/sahon/Desktop/arafat20';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = app('blade.compiler');
$fail = 0;
$n = 0;

foreach (['backEnd', 'frontend'] as $group) {
    $dir = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("$base/resources/views/$group")
    );
    foreach ($dir as $f) {
        if ($f->getExtension() !== 'php') {
            continue;
        }
        $n++;
        try {
            $compiler->compileString(file_get_contents($f->getPathname()));
        } catch (Throwable $e) {
            $fail++;
            echo "FAIL ", str_replace("$base/", '', $f->getPathname()), ": ", substr($e->getMessage(), 0, 90), "\n";
        }
    }
}

printf("\n%d blade files compiled, %d failures\n", $n, $fail);
exit($fail ? 1 : 0);