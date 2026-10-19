<?php

namespace App\Support;

/**
 * ShopPromises
 * ------------
 * The four shop promises shown across the front end. They are pure interface
 * copy (no database behind them), so they live here as a single definition
 * that every view can read:
 *
 *   - sections/service-strip.blade.php        shows all four under the hero
 *   - sections/detail/summary.blade.php       value banner, first three
 *   - sections/detail/tabs.blade.php          support card, last one
 *
 * Blade @include cannot hand variables back to the view that included it,
 * which is why this is a plain class instead of a shared partial. Update the
 * wording here and every placement changes together.
 */
class ShopPromises
{
    /**
     * The four promises, in display order.
     *
     * @return array<int, object{icon: string, title: string, text: string}>
     */
    public static function all(): array
    {
        return [
            (object) [
                'icon'  => 'bi-truck',
                'title' => 'Fast delivery',
                'text'  => 'Inside Dhaka in 24 hours, nationwide in 2-3 days',
            ],
            (object) [
                'icon'  => 'bi-shield-check',
                'title' => 'Secure payment',
                'text'  => 'Cash on delivery, cards and mobile banking',
            ],
            (object) [
                'icon'  => 'bi-arrow-counterclockwise',
                'title' => 'Easy returns',
                'text'  => 'Return any product within 7 days',
            ],
            (object) [
                'icon'  => 'bi-headset',
                'title' => 'Support',
                'text'  => 'Every day from 9 am to 9 pm',
            ],
        ];
    }
}
