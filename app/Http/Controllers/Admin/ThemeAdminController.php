<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ThemeAdminController extends Controller
{
    public function edit()
    {
        $settings = [
            'site_name' => Setting::get('site_name', 'Lumens'),
            'primary_color' => Setting::get('primary_color', '#203749'),
            'primary_dark_color' => Setting::get('primary_dark_color', '#203749'),
            'accent_color' => Setting::get('accent_color', '#FFAE00'),
            'home_hero_title' => Setting::get('home_hero_title', 'Iluminando con calidad'),
            'home_hero_subtitle' => Setting::get('home_hero_subtitle', 'Soluciones LED certificadas para proyectos comerciales, industriales y residenciales.'),
        ];

        return view('admin.theme.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_dark_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'home_hero_title' => ['required', 'string', 'max:120'],
            'home_hero_subtitle' => ['required', 'string', 'max:220'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, $key === 'site_name' ? 'general' : 'theme');
        }

        return redirect()
            ->route('admin.theme.edit')
            ->with('success', 'Tema actualizado.');
    }
}
