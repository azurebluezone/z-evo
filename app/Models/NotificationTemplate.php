<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'channels' => 'array',
        'variables' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Whitelisted delivery channels, defaulting to the in-app inbox.
     *
     * @return list<string>
     */
    public function channelList(): array
    {
        $channels = is_array($this->channels) ? $this->channels : [];

        $channels = array_values(array_unique(array_intersect($channels, ['database', 'mail'])));

        return $channels !== [] ? $channels : ['database'];
    }

    /**
     * The placeholders this template documents.
     *
     * @return list<string>
     */
    public function variableList(): array
    {
        $variables = is_array($this->variables) ? $this->variables : [];

        $variables = array_filter(array_map(
            static fn ($variable) => trim((string) $variable),
            $variables
        ));

        return array_values(array_unique($variables));
    }

    /**
     * Render the subject for a notification.
     *
     * @param array<string, scalar|null> $variables
     */
    public function renderTitle(array $variables = []): string
    {
        return $this->renderText(self::plainText((string) $this->title), $variables);
    }

    /**
     * Render the body as plain text.
     *
     * The admin edits the body in Quill (HTML), but the customer inbox escapes
     * it with `white-space: pre-wrap`; stripping tags here is what stops a
     * customer from seeing `<p>Hi</p>` literally.
     *
     * @param array<string, scalar|null> $variables
     */
    public function renderBody(array $variables = []): string
    {
        return $this->renderText(self::plainText((string) $this->body), $variables);
    }

    /**
     * HTML (or plain text) to readable plain text.
     */
    public static function plainText(string $value): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $text = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    protected function renderText(string $text, array $variables): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replacements['{' . $key . '}'] = (string) ($value ?? '');
            }
        }

        $text = strtr($text, $replacements);

        // Never leak a raw placeholder to a customer.
        $text = preg_replace('/\{[a-z0-9_]+\}/i', '', $text) ?? $text;

        return trim($text);
    }
}
