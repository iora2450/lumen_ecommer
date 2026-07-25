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
            'primary_dark_color' => Setting::get('primary_dark_color', '#1a2c3a'),
            'accent_color' => Setting::get('accent_color', '#FFAE00'),
            'footer_email' => Setting::get('footer_email', 'ventas@lumens.local'),
            'footer_phone' => Setting::get('footer_phone', '+503 2222 3333'),
            'footer_location' => Setting::get('footer_location', 'San Salvador, El Salvador'),
            'home_hero_title' => Setting::get('home_hero_title', 'Iluminación que transforma tus espacios'),
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
            'footer_email' => ['nullable', 'string', 'max:120'],
            'footer_phone' => ['nullable', 'string', 'max:80'],
            'footer_location' => ['nullable', 'string', 'max:120'],
            'home_hero_title' => ['required', 'string', 'max:120'],
            'home_hero_subtitle' => ['required', 'string', 'max:220'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, in_array($key, ['site_name', 'footer_email', 'footer_phone', 'footer_location'], true) ? 'general' : 'theme');
        }

        return redirect()
            ->route('admin.theme.edit')
            ->with('success', 'Tema actualizado.');
    }
}
