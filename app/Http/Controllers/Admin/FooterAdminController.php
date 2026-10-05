<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class FooterAdminController extends Controller
{
    public function edit()
    {
        $settings = [
            'footer_description' => Setting::get('footer_description', 'Iluminación comercial e industrial con tecnología de punta.'),
            'footer_email' => Setting::get('footer_email', 'ventas@lumens.local'),
            'footer_phone' => Setting::get('footer_phone', '+503 2222 3333'),
            'footer_location' => Setting::get('footer_location', 'San Salvador, El Salvador'),
            'footer_weekday_hours' => Setting::get('footer_weekday_hours', 'Lun-Vie: 8:00am - 5:00pm'),
            'footer_saturday_hours' => Setting::get('footer_saturday_hours', 'Sáb: 9:00am - 1:00pm'),
        ];

        return view('admin.footer.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'footer_description' => ['required', 'string', 'max:240'],
            'footer_email' => ['nullable', 'email', 'max:120'],
            'footer_phone' => ['nullable', 'string', 'max:80'],
            'footer_location' => ['nullable', 'string', 'max:160'],
            'footer_weekday_hours' => ['nullable', 'string', 'max:120'],
            'footer_saturday_hours' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, 'footer');
        }

        return redirect()
            ->route('admin.footer.edit')
            ->with('success', 'La información del pie de página fue actualizada.');
    }
}
