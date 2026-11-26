<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Event key -> active template -> rendered notification -> queued job.
 *
 * Nothing here knows about a specific event: the listener supplies the key and
 * the variables, the admin owns the wording, and missing/inactive templates
 * are a silent no-op (logged at debug level) so a template edit can never break
 * checkout.
 */
final class NotificationDispatcher
{
    /**
     * @param array<string, scalar|null> $variables
     * @param iterable<int, object> $notifiables
     * @return int number of notifications dispatched
     */
    public function send(string $eventKey, array $variables = [], iterable $notifiables = []): int
    {
        $template = NotificationTemplate::query()
            ->active()
            ->where('event_key', $eventKey)
            ->first();

        if (! $template) {
            Log::debug('No active notification template for event', ['event_key' => $eventKey]);

            return 0;
        }

        return $this->sendTemplate($template, $notifiables, $variables, ['event_key' => $eventKey]);
    }

    /**
     * Send a template to a set of notifiables. Used by the manual broadcast
     * screens as well as the event listeners.
     *
     * @param array<string, scalar|null> $variables
     * @param iterable<int, object> $notifiables
     * @param array<string, mixed> $meta
     * @return int number of notifications dispatched
     */
    public function sendTemplate(
        NotificationTemplate $template,
        iterable $notifiables,
        array $variables = [],
        array $meta = []
    ): int {
        $title = $template->renderTitle($variables);
        $body = $template->renderBody($variables);

        if ($title === '' || $body === '') {
            return 0;
        }

        $channels = $template->channelList();

        $meta = array_merge($meta, [
            'template_id' => $template->id,
            'event_key' => $template->event_key,
        ]);

        $dispatched = 0;

        foreach ($notifiables as $notifiable) {
            if (! is_object($notifiable)) {
                continue;
            }

            SendNotificationJob::dispatch($notifiable, $title, $body, $channels, $meta);

            $dispatched++;
        }

        return $dispatched;
    }
}
