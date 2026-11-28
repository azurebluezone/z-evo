<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Abandoned cart window
    |--------------------------------------------------------------------------
    |
    | How long a cart may sit with no activity before it counts as abandoned.
    | The cart recorder stores last_activity_at on every mutation and the
    | `carts:mark-abandoned` command compares it against this window, so the
    | rule lives in configuration and not in a hard-coded number.
    |
    */
    'abandoned_cart_window_hours' => (int) env('ABANDONED_CART_WINDOW_HOURS', 24),
];
