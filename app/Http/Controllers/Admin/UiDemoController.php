<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UiDemoController extends Controller
{
    public function index(Request $request)
    {
        return view('backEnd.demo.ui-demo');
    }
}
