<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = DB::table('attributes')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('backEnd.attributes.index', compact('attributes'));
    }

    public function create(Request $request)
    {
        return view('backEnd.attributes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:attributes,slug',
            'status' => 'nullable|in:0,1',
        ]);

        $name = trim($request->name);
        $slug = $request->slug ? trim($request->slug) : Str::slug($name);

        DB::table('attributes')->insert([
            'name' => $name,
            'slug' => $slug,
            'kind' => 'custom',
            'status' => $request->status !== null ? (int) $request->status : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created successfully');
    }

    public function edit(Request $request, $id)
    {
        $attribute = DB::table('attributes')->where('id', (int) $id)->first();
        if (!$attribute) {
            abort(404);
        }

        return view('backEnd.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, $id)
    {
        $attributeId = (int) $id;

        $attribute = DB::table('attributes')->where('id', $attributeId)->first();
        if (!$attribute) {
            abort(404);
        }

        if (($attribute->kind ?? 'custom') === 'system') {
            return back()->with('error', 'System attributes cannot be edited');
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:attributes,slug,' . $attributeId,
            'status' => 'nullable|in:0,1',
        ]);

        $name = trim($request->name);
        $slug = $request->slug ? trim($request->slug) : Str::slug($name);

        DB::table('attributes')->where('id', $attributeId)->update([
            'name' => $name,
            'slug' => $slug,
            'status' => $request->status !== null ? (int) $request->status : (int) ($attribute->status ?? 1),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully');
    }

    public function values(Request $request, $id)
    {
        $attributeId = (int) $id;
        $attribute = DB::table('attributes')->where('id', $attributeId)->first();
        if (!$attribute) {
            abort(404);
        }

        $values = DB::table('attribute_values')
            ->where('attribute_id', $attributeId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('backEnd.attributes.values', compact('attribute', 'values'));
    }

    public function valuesStore(Request $request, $id)
    {
        $attributeId = (int) $id;
        $attribute = DB::table('attributes')->where('id', $attributeId)->first();
        if (!$attribute) {
            abort(404);
        }

        $request->validate([
            'value' => 'required|string|max:120',
            'code' => 'nullable|string|max:60',
            'multiplier' => 'nullable|integer|min:1',
            'base_unit' => 'nullable|string|max:20',
            'status' => 'nullable|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::table('attribute_values')->insert([
            'attribute_id' => $attributeId,
            'value' => trim($request->value),
            'code' => $request->code ? trim($request->code) : null,
            'multiplier' => $request->multiplier !== null ? (int) $request->multiplier : null,
            'base_unit' => $request->base_unit ? trim($request->base_unit) : null,
            'kind' => 'custom',
            'status' => $request->status !== null ? (int) $request->status : 1,
            'sort_order' => $request->sort_order !== null ? (int) $request->sort_order : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.attributes.values', $attributeId)->with('success', 'Attribute value added');
    }

    public function destroy(Request $request, $id)
    {
        $attributeId = (int) $id;
        $attribute = DB::table('attributes')->where('id', $attributeId)->first();
        if (!$attribute) {
            abort(404);
        }

        if (($attribute->kind ?? 'custom') === 'system') {
            return back()->with('error', 'System attributes cannot be deleted');
        }

        // Delete associated attribute values first
        DB::table('attribute_values')->where('attribute_id', $attributeId)->delete();
        // Delete attribute
        DB::table('attributes')->where('id', $attributeId)->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully');
    }

    public function valuesDestroy(Request $request, $attributeId, $valueId)
    {
        $attrId = (int) $attributeId;
        $valId = (int) $valueId;

        $attribute = DB::table('attributes')->where('id', $attrId)->first();
        if (!$attribute) {
            abort(404);
        }

        $value = DB::table('attribute_values')->where('id', $valId)->where('attribute_id', $attrId)->first();
        if (!$value) {
            abort(404);
        }

        if (($value->kind ?? 'custom') === 'system') {
            return back()->with('error', 'System attribute values cannot be deleted');
        }

        DB::table('attribute_values')->where('id', $valId)->delete();

        return redirect()->route('admin.attributes.values', $attrId)->with('success', 'Attribute value deleted successfully');
    }
}
