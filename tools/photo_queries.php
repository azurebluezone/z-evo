<?php
/**
 * Per-product Commons search terms.
 *
 * The generic "<product name> food" query produced visibly wrong matches:
 * broccoli returned a shrimp stir-fry, milk returned "kheer" (a sweet rice
 * pudding), honey returned a bee, red rice returned a cooked chicken dish.
 * These explicit terms are what a shopkeeper would actually type, and they are
 * stored here so the choice is reviewable rather than hidden inside a loop.
 */
return [
    'Organic Hass Avocados'   => ['avocado fruit', 'avocados'],
    'Crisp Farm Broccoli'     => ['broccoli vegetable fresh', 'broccoli'],
    'Pure Organic Whole Milk' => ['milk bottle fresh', 'milk glass bottle', 'fresh milk'],
    'Artisan Raw Honey'       => ['honey jar', 'honeycomb jar', 'honey pouring'],
    'Farm Fresh Tomatoes'     => ['tomatoes fresh red', 'tomato fruit'],
    'Organic Potatoes'       => ['potatoes raw', 'potato tubers'],
    'Organic Spinach'        => ['spinach leaves fresh', 'raw spinach'],
    'Farm Fresh Eggs'        => ['eggs basket fresh', 'hen eggs', 'egg carton'],
    'Fresh Carrots'          => ['carrots bunch fresh', 'carrots raw'],
    'Red Apple'              => ['red apples fruit', 'apple fruit'],
    'Skinless Chicken'       => ['raw chicken breast', 'chicken meat raw'],
    'Artisan Sourdough Bread'=> ['sourdough bread loaf', 'bread loaf'],
    'Fresh Bananas'          => ['bananas bunch', 'banana fruit'],
    'Desi Ghee'              => ['ghee jar', 'clarified butter ghee', 'ghee'],
    'Fresh Creamer Pack'     => ['milk carton fresh', 'milk pack', 'milk plastic bottle'],
    'Green Chilli'           => ['green chili peppers', 'chilli peppers fresh'],
    'Cold Pressed Mustard Oil'=> ['mustard oil bottle', 'oil bottle cooking', 'mustard oil'],
    'Red Onions'             => ['red onions', 'onion bulbs red'],
    'Red Rice'               => ['red rice grain raw', 'red rice grains', 'rice grains raw'],
    'Roasted Chickpeas'      => ['roasted chickpeas', 'chickpeas roasted', 'garbanzo beans roasted'],
];
