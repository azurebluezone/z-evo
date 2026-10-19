<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EditorUploadController extends Controller
{
    public function quill(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        $file = $request->file('image');
        $fileName = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

        $dir = public_path('uploads/editor');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file->move($dir, $fileName);

        $url = asset('uploads/editor/' . $fileName);

        return response()->json([
            'url' => $url,
        ]);
    }
}
