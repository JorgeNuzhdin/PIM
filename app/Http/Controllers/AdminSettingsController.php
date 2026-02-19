<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'access_years' => 'required|integer|min:1|max:10',
        ]);

        Setting::set('access_years', $request->access_years);

        return back()->with('success', 'Configuración guardada.');
    }
}
