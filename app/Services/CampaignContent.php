<?php

namespace App\Services;

/**
 * Whitelist for template-specific campaign content.
 *
 * The admin form is generated from templates.config, but the request is still
 * untrusted: only these keys ever reach campaigns.extra_fields, and every row
 * is trimmed, emptied rows are dropped and the row count is bounded.
 */
class CampaignContent
{
    public const MAX_ROWS = 12;

    public const TEXT_FIELDS = [
        'video_url',
        'countdown_date',
        'contact_phone',
        'email',
        'facebook',
        'instagram',
        'youtube',
    ];

    public const ROW_FIELDS = [
        'features' => ['title', 'description', 'image'],
        'benefits' => ['icon', 'title', 'text'],
        'testimonials' => ['name', 'text', 'rating'],
        'faqs' => ['question', 'answer'],
        'why_us' => ['text'],
        'packages' => ['name', 'price', 'image'],
    ];

    public function normalize(array $raw): array
    {
        $clean = [];

        foreach (self::TEXT_FIELDS as $field) {
            $value = is_scalar($raw[$field] ?? null) ? trim((string) $raw[$field]) : '';
            if ($value !== '') {
                $clean[$field] = $value;
            }
        }

        foreach (self::ROW_FIELDS as $field => $keys) {
            $rows = $raw[$field] ?? [];
            if (!is_array($rows)) {
                continue;
            }

            $kept = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $item = [];
                foreach ($keys as $key) {
                    $value = is_scalar($row[$key] ?? null) ? trim((string) $row[$key]) : '';
                    if ($value !== '') {
                        $item[$key] = $value;
                    }
                }

                if ($item !== []) {
                    $kept[] = $item;
                }

                if (count($kept) >= self::MAX_ROWS) {
                    break;
                }
            }

            if ($kept !== []) {
                $clean[$field] = array_values($kept);
            }
        }

        return $clean;
    }
}
