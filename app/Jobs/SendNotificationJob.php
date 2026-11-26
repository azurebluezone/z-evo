<?php

namespace App\Jobs;

use App\Notifications\GenericDbNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Deliver one rendered notification to one notifiable.
 *
 * With QUEUE_CONNECTION=sync this runs inline on the request. With the
 * `database` driver it becomes a row in `jobs` and the scheduled
 * `queue:work --stop-when-empty` drains it, which is the shared-hosting
 * upgrade path. Retries are bounded and the final failure is logged.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param list<string> $channels
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public Model $notifiable,
        public string $title,
        public string $body,
        public array $channels = ['database'],
        public array $meta = []
    ) {}

    public function handle(): void
    {
        $channels = array_values(array_unique(array_intersect($this->channels, ['database', 'mail'])));

        if (in_array('mail', $channels, true) && blank($this->notifiable->email ?? null)) {
            $channels = array_values(array_diff($channels, ['mail']));
        }

        if ($channels === []) {
            return;
        }

        $this->notifiable->notify(new GenericDbNotification(
            $this->title,
            $this->body,
            $this->meta,
            $channels
        ));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Notification delivery failed', [
            'notifiable_type' => $this->notifiable::class,
            'notifiable_id' => $this->notifiable->getKey(),
            'event_key' => $this->meta['event_key'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
