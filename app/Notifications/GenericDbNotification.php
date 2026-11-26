<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One database/mail notification built from a rendered template.
 *
 * The default channels stay `database`, so every existing caller keeps its old
 * behaviour. A caller that passes `mail` opts in, and via() silently drops the
 * mail channel when the recipient has no email address (guest checkout
 * customers do not have one).
 */
class GenericDbNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $meta
     * @param list<string> $channels
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $meta = [],
        public array $channels = ['database']
    ) {}

    public function via(object $notifiable): array
    {
        $channels = array_values(array_unique(array_intersect(
            $this->channels !== [] ? $this->channels : ['database'],
            ['database', 'mail']
        )));

        if (in_array('mail', $channels, true) && blank($notifiable->email ?? null)) {
            $channels = array_values(array_diff($channels, ['mail']));
        }

        return $channels !== [] ? $channels : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'message' => $this->body,
            'meta' => $this->meta,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->title)
            ->greeting('Hello ' . ($notifiable->name ?? ''));

        foreach (preg_split('/\r?\n/', $this->body) ?: [] as $line) {
            $message->line($line);
        }

        return $message;
    }
}
