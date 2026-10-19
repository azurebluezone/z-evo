<?php

namespace App\Support;

use Illuminate\View\FileViewFinder;

/**
 * View finder that hands view-name resolution to the template registry.
 *
 * Laravel resolves every Blade name through the finder, so a single subclass is
 * enough to switch a whole template set on or off: no controller, route or
 * query has to know which design is live.
 *
 * Names the registry does not claim are passed straight through, which is what
 * keeps layouts, partials, ajax payloads and admin screens on their original
 * files.
 */
class ThemeViewFinder extends FileViewFinder
{
    /**
     * Locate the given view, letting App\Support\FrontendTheme decide which
     * template set actually owns the name.
     *
     * The signature mirrors FileViewFinder::find() exactly - deliberately no
     * extra arguments, because this framework version builds the file name from
     * $this->extensions and passing anything into the constructor would replace
     * them.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        return parent::find(FrontendTheme::resolve((string) $name));
    }
}
