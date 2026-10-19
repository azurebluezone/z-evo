<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes a variant's attribute selection.
 *
 * ---------------------------------------------------------------------------
 * WHY A SERVICE AND NOT CODE IN THE CONTROLLER
 * ---------------------------------------------------------------------------
 * There is NO pivot table. A variant's attributes live entirely inside
 * product_variants.attributes_json:
 *
 *     {"attrs":{"colour":"Forest Green"},"ids":{"colour":"7"}}
 *
 * So reading, writing and merging this JSON has to behave identically wherever
 * it happens - the variant screen, the product screen, and anything added later.
 * Two hand-written copies of that JSON shape drift, and a mismatch shows up as
 * storefront swatches that do not match the price, which is very hard to trace.
 *
 * The stored shape is also a contract with FrontendController: variantDetail()
 * and details() both decode it and read `attrs` and `ids`. Changing the shape
 * silently breaks the storefront, so it is written exactly as ProductController
 * already writes it (lines 533-536).
 *
 * ---------------------------------------------------------------------------
 * WHY THE SHAPE IS THE PROBLEM, NOT THE DATA
 * ---------------------------------------------------------------------------
 * A JSON blob cannot be joined in SQL. FrontendController matches a visitor's
 * chosen pill to a variant by walking every variant in PHP. That is fine for a
 * handful of variants and degrades badly for a catalogue of thousands, but
 * replacing it with a real pivot table is a migration of live data and is out
 * of scope here - it is recorded in the handover notes instead.
 */
class VariantAttributes
{
    /**
     * Decode a variant's attributes_json into ['attrs' => [], 'ids' => []].
     *
     * Never throws: a malformed or NULL value yields an empty selection, which
     * is exactly what a variant that was never given attributes should mean.
     */
    public function read(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return ['attrs' => [], 'ids' => []];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return ['attrs' => [], 'ids' => []];
        }

        return [
            'attrs' => isset($decoded['attrs']) && is_array($decoded['attrs']) ? $decoded['attrs'] : [],
            'ids'   => isset($decoded['ids']) && is_array($decoded['ids']) ? $decoded['ids'] : [],
        ];
    }

    /**
     * Build the JSON payload from a submitted form.
     *
     * Expects the convention the product create/edit screen already uses:
     * attribute_ids[<attribute_slug>] = <attribute_value_id>
     *
     * The human label and the code (the hex colour a swatch needs) are looked
     * up server side rather than trusted from the form, so a hand-crafted POST
     * cannot invent a colour value.
     *
     * @return string|null  null when nothing was selected, which keeps the
     *                      column NULL and matches how an unconfigured variant
     *                      has always looked to the storefront.
     */
    public function buildFromRequest(Request $request): ?string
    {
        $submitted = $request->input('attribute_ids', []);

        if (!is_array($submitted) || $submitted === []) {
            return null;
        }

        $attrs = [];
        $ids   = [];

        foreach ($submitted as $slug => $valueId) {
            $valueId = (int) $valueId;

            if ($valueId <= 0) {
                continue;
            }

            $value = AttributeValue::with('attribute')->find($valueId);

            if (!$value || !$value->attribute) {
                continue;
            }

            $key = (string) ($value->attribute->slug ?: $value->attribute->name);

            $ids[$key]   = (string) $value->id;
            $attrs[$key] = (string) $value->value;

            // The hex code drives the storefront swatch
            // (FrontendController tests it with /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i).
            if (filled($value->code)) {
                $attrs[$key . '_code'] = (string) $value->code;
            }
        }

        if ($attrs === []) {
            return null;
        }

        return (string) json_encode([
            'attrs' => $attrs,
            'ids'   => $ids,
        ]);
    }

    /**
     * Attributes, with their values, for rendering a selection form.
     *
     * Only "selectable" kinds are offered: the seeded Weight Unit / Volume Unit
     * rows are system attributes used for shipping maths and are not things an
     * admin picks per variant.
     */
    public function selectableAttributes(): array
    {
        return Attribute::with(['values' => function ($query) {
            $query->where('status', 1)->orderBy('sort_order')->orderBy('id');
        }])
            ->where('status', 1)
            ->get()
            ->filter(function (Attribute $attribute) {
                if ($attribute->values->isEmpty()) {
                    return false;
                }

                $kind = strtolower((string) ($attribute->kind ?: 'selectable'));

                return !in_array($kind, ['system', 'unit'], true);
            })
            ->values()
            ->all();
    }

    /**
     * Convenience for a view: the variant's current selection, keyed by slug.
     *
     * @return array<string, int>  attribute slug => chosen attribute_value id
     */
    public function selectionOf(?string $json): array
    {
        return $this->read($json)['ids'];
    }
}