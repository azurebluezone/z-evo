<?php

/**
 * verify_notification_template_ui.php
 *
 * Gate for the Template Manager UI slice. The actual assertions live in
 * tests/Feature/NotificationTemplateUiCheckTest.php so they can use the
 * framework's HTTP client and an authenticated admin; this script is the
 * command the goal contract records.
 *
 * It prints NOTIFICATION_TEMPLATE_UI_OK only when the test passes, because the
 * marker is written by the test after every assertion. A missing test, a failed
 * assertion or a PHP error produces no marker and a non-zero exit code.
 */

$root = dirname(__DIR__);
$command = 'cd ' . escapeshellarg($root)
    . ' && php artisan test --filter=NotificationTemplateUiCheck 2>&1';

passthru($command, $code);
exit($code);
