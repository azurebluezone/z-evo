<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Image Cache
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports caching of images. You can configure the
    | cache settings here.
    |
    */

    'cache' => [
        'driver' => env('IMAGE_CACHE_DRIVER', 'file'),
        'path' => storage_path('framework/cache/image'),
    ],

];
