<?php

namespace App\Support;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\File;

/**
 * Front-end template registry.
 *
 * The site ships two complete and independent sets of Blade templates:
 *
 *   classic  resources/views/frontend/**        the original design
 *   modern   resources/views/frontend/fm/**     the FreshMart design
 *
 * Neither set references the other, so either one can be switched on or off
 * without editing a controller, a route or a query. This class is the single
 * place that answers "which set owns this view name"; ThemeViewFinder asks it
 * on every lookup.
 *
 * Mapping follows a naming convention rather than a hand written table, so
 * adding a page needs no registration - dropping the file in place is enough:
 *
 *   frontend.home                      ->  frontend.fm.pages.home
 *   frontend.pages.products            ->  frontend.fm.pages.products
 *   frontend.pages.auth.login          ->  frontend.fm.pages.auth.login
 *   frontend.layouts.customer.checkout ->  frontend.fm.pages.customer.checkout
 *
 * Any view without a modern counterpart (layouts, partials, ajax payloads,
 * admin screens) always keeps its classic path. That is what makes converting
 * the site one page at a time safe: anything not converted yet keeps serving
 * the original markup.
 */
final class FrontendTheme
{
    public const CLASSIC = 'classic';
    public const MODERN = 'modern';

    /**
     * Session key holding a temporary "design preview" flag.
     *
     * It is deliberately a session value and not a query string parameter:
     * query strings belong to the storefront (brand=, q=, sort_by=... must keep
     * working while a design is being previewed), and a session flag only ever
     * affects the person who turned it on.
     */
    public const PREVIEW_KEY = 'fm_theme_preview';

    /** Folder of each template set inside resources/views. */
    public const ROOT = [
        self::CLASSIC => 'frontend',
        self::MODERN => 'frontend/fm',
    ];

    /**
     * Memoised answer for the current request. Views are resolved many times
     * per request, and a status update must invalidate it, hence forget().
     */
    private static ?string $active = null;

    /**
     * Human readable metadata for the admin template picker.
     *
     * @return array<string, array<string, string>>
     */
    public static function catalog(): array
    {
        return [
            self::CLASSIC => [
                'key' => self::CLASSIC,
                'name' => 'Classic (current design)',
                'summary' => 'The previous design, exactly as it always worked. No risk.',
                'root' => self::ROOT[self::CLASSIC],
            ],
            self::MODERN => [
                'key' => self::MODERN,
                'name' => 'Modern (new design)',
                'summary' => 'New design built on Bootstrap 5. Responsive, fast and modern.',
                'root' => self::ROOT[self::MODERN],
            ],
        ];
    }

    /** Template set that should answer front-end view lookups right now. */
    public static function active(): string
    {
        if (self::$active !== null) {
            return self::$active;
        }

        return self::$active = self::previewing() ?? self::stored();
    }

    /**
     * The row that actually drives the site. Matches what AppServiceProvider
     * shares as $generalsetting, so admin and visitor always read the same
     * setting.
     */
    private static function row(): GeneralSetting|null
    {
        return GeneralSetting::query()->where('status', 1)->orderBy('id')->first()
            ?? GeneralSetting::query()->orderBy('id')->first();
    }

    /** Value saved by an administrator, falling back to the original design. */
    public static function stored(): string
    {
        try {
            $value = self::row()?->frontend_theme;
        } catch (\Throwable $e) {
            // Column missing or database unreachable: never block rendering.
            return self::CLASSIC;
        }

        return self::isValid($value) ? $value : self::CLASSIC;
    }

    /** Persist the active template set for every visitor. */
    public static function save(string $key): bool
    {
        if (! self::isValid($key)) {
            return false;
        }

        $row = self::row();

        if ($row === null) {
            return false;
        }

        $row->frontend_theme = $key;
        $row->save();

        // A lingering preview would hide what was just saved.
        self::clearPreview();

        return true;
    }

    /**
     * Template set a session-level preview is forcing, if any.
     *
     * Stored in the session rather than the query string: query strings stay
     * free for real storefront parameters (brand=, q=, sort_by=...) and a flag
     * only ever affects the browser that raised it.
     */
    public static function previewing(): ?string
    {
        try {
            $key = session()->get(self::PREVIEW_KEY);
        } catch (\Throwable $e) {
            return null;
        }

        return self::isValid($key) ? $key : null;
    }

    /** Turn a temporary preview on. */
    public static function startPreview(string $key): bool
    {
        if (! self::isValid($key)) {
            return false;
        }

        try {
            session()->put(self::PREVIEW_KEY, $key);
        } catch (\Throwable $e) {
            return false;
        }

        self::forget();

        return true;
    }

    /** Drop a temporary preview so the saved setting answers again. */
    public static function clearPreview(): void
    {
        try {
            session()->forget(self::PREVIEW_KEY);
        } catch (\Throwable $e) {
            // Nothing to clean up when there is no session driver.
        }

        self::forget();
    }

    /**
     * URL that turns a preview on and lands the visitor on the page they asked
     * for. $back must be a local absolute path ("/products"), anything else is
     * ignored so this can never become an open redirect.
     */
    public static function previewRoute(string $key, ?string $back = null): string
    {
        $url = route('site.theme.preview', ['theme' => $key]);

        if ($back !== null && str_starts_with($back, '/') && ! str_starts_with($back, '//')) {
            $url .= '?back=' . rawurlencode($back);
        }

        return $url;
    }

    /** URL that turns a preview off. */
    public static function clearRoute(): string
    {
        return route('site.theme.clear');
    }

    /**
     * Resolve a requested view name to the file that should actually render.
     * Unknown or unmapped names are returned untouched.
     */
    public static function resolve(string $view): string
    {
        if (self::active() !== self::MODERN) {
            return $view;
        }

        $modern = self::modernViewFor($view);

        if ($modern === null) {
            return $view;
        }

        // Guard against a half finished conversion: if the modern file is not
        // on disk the classic one still answers, so no page can ever 500
        // because of a missing template.
        return self::fileExists($modern) ? $modern : $view;
    }

    /**
     * Modern counterpart of a classic view name, following the folder
     * convention documented on this class. Null when the name is outside the
     * convertible surface (layouts, partials, ajax payloads, admin views).
     */
    public static function modernViewFor(string $view): ?string
    {
        if (! str_starts_with($view, 'frontend.')) {
            return null;
        }

        $rest = substr($view, strlen('frontend.'));

        if ($rest === 'home') {
            return 'frontend.fm.pages.home';
        }

        if (str_starts_with($rest, 'pages.')) {
            return 'frontend.fm.pages.' . substr($rest, strlen('pages.'));
        }

        if (str_starts_with($rest, 'layouts.customer.')) {
            return 'frontend.fm.pages.customer.' . substr($rest, strlen('layouts.customer.'));
        }

        return null;
    }

    /** Absolute path of a Blade view inside the modern template set. */
    public static function path(string $modernView): string
    {
        return self::viewsPath() . '/' . str_replace('.', '/', $modernView) . '.blade.php';
    }

    public static function fileExists(string $modernView): bool
    {
        return File::exists(self::path($modernView));
    }

    /** Drop the memoised value. Called after the setting changes. */
    public static function forget(): void
    {
        self::$active = null;
    }

    /**
     * Conversion report used by the admin screen: every front-end page view,
     * whether the modern set already covers it, and where to look at it.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function coverage(): array
    {
        $rows = [];

        foreach (self::convertibleViews() as $classicView) {
            $modern = self::modernViewFor($classicView);
            $hasModern = $modern !== null && self::fileExists($modern);

            $rows[] = [
                'view' => $classicView,
                'modern' => $hasModern,
                'url' => self::urlFor($classicView),
                // Opening the page through the preview route shows the modern
                // markup when it exists, the classic markup when it does not.
                'preview_url' => $hasModern
                    ? self::previewRoute(self::MODERN, self::pathFor($classicView))
                    : null,
            ];
        }

        return $rows;
    }

    /** Local path of the page a classic view renders, for preview redirects. */
    private static function pathFor(string $classicView): ?string
    {
        $url = self::urlFor($classicView);

        if ($url === null) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path !== '' && str_starts_with($path, '/') ? $path : null;
    }

    /** Classic page views that a template set is allowed to replace. */
    private static function convertibleViews(): array
    {
        $views = [];

        if (File::exists(self::viewsPath() . '/frontend/home.blade.php')) {
            $views[] = 'frontend.home';
        }

        $root = self::viewsPath();

        foreach (['frontend/pages', 'frontend/layouts/customer'] as $folder) {
            $dir = $root . '/' . $folder;

            if (! File::isDirectory($dir)) {
                continue;
            }

            foreach (File::allFiles($dir) as $file) {
                $name = $file->getFilename();

                // SplFileInfo reports the last extension only, so ".blade.php"
                // has to be matched against the whole filename.
                if (! str_ends_with($name, '.blade.php')) {
                    continue;
                }

                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $relative = substr($relative, 0, -strlen('.blade.php'));

                $views[] = $folder . '/' . $relative;
            }
        }

        $views = array_map(static fn (string $path) => str_replace('/', '.', $path), $views);

        sort($views);

        return $views;
    }

    /** Absolute path of the resource views folder. */
    private static function viewsPath(): string
    {
        return rtrim(resource_path('views'), '/');
    }

    /** Front-end URL for a classic view name, when one is well known. */
    private static function urlFor(string $classicView): ?string
    {
        $routes = [
            'frontend.home' => 'home',
            'frontend.pages.products' => 'products.page',
            'frontend.pages.product-details' => 'products.page',
            'frontend.pages.categories' => 'categories.page',
            'frontend.pages.subcategory' => 'products.page',
            'frontend.pages.main-categories' => 'categories.page',
            'frontend.pages.cart' => 'cart.show',
            'frontend.pages.checkout' => 'customer.checkout',
            'frontend.pages.contact' => 'contact',
            'frontend.pages.search' => 'products.page',
            'frontend.pages.offers' => 'offers.index',
            'frontend.pages.hotdeals' => 'hotdeals',
            'frontend.pages.about-us' => 'about.us',
            'frontend.pages.track-order' => 'track.order',
            // route('page') needs a slug, so it cannot be generated from a
            // view name alone - no link is better than a broken one.
            'frontend.pages.page' => null,
            'frontend.pages.service' => 'home',
            'frontend.pages.backpacks' => 'home',
            'frontend.pages.campaigns' => 'home',
            'frontend.pages.auth.login' => 'customer.login',
            'frontend.pages.auth.register' => 'customer.register',
        ];

        if (isset($routes[$classicView])) {
            // A route that needs parameters cannot be linked from a view name
            // alone; never let that break the admin screen.
            try {
                return route($routes[$classicView]);
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (str_starts_with($classicView, 'frontend.layouts.customer.')) {
            return url('/customer');
        }

        return null;
    }

    private static function isValid(mixed $value): bool
    {
        return is_string($value) && array_key_exists($value, self::catalog());
    }
}
