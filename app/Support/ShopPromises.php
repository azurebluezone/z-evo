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
                'title' => 'Delivery',
                'text'  => 'Delivery charge is shown at checkout',
            ],
            (object) [
                'icon'  => 'bi-shield-check',
                'title' => 'Cash on delivery',
                'text'  => 'Pay when your order reaches you',
            ],
            (object) [
                'icon'  => 'bi-arrow-counterclockwise',
                'title' => 'Returns',
                'text'  => 'Contact support before sending an item back',
            ],
            (object) [
                'icon'  => 'bi-headset',
                'title' => 'Support',
                'text'  => 'Reach us through the contact page',
            ],
        ];
    }
}
