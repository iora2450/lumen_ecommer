<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CouponAdminController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.edit', [
            'coupon' => new Coupon([
                'discount_type' => Coupon::TYPE_PERCENTAGE,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validatedData($request));

        return redirect()->route('admin.coupons.index')->with('success', 'Cupón creado correctamente.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validatedData($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', 'Cupón actualizado correctamente.');
    }

    public function destroy(Coupon $coupon)
    {
        DB::transaction(function () use ($coupon) {
            $coupon->quotes()->update(['coupon_id' => null]);
            $coupon->delete();
        });

        return redirect()->route('admin.coupons.index')->with('success', 'Cupón eliminado.');
    }

    private function validatedData(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon),
            ],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in([Coupon::TYPE_PERCENTAGE, Coupon::TYPE_FIXED])],
            'discount_value' => [
                'required',
                'numeric',
                'gt:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->input('discount_type') === Coupon::TYPE_PERCENTAGE && (float) $value > 100) {
                        $fail('El porcentaje de descuento no puede ser mayor a 100.');
                    }
                },
            ],
            'minimum_subtotal' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'ends_at.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['minimum_subtotal'] = $data['minimum_subtotal'] ?? null;
        $data['usage_limit'] = $data['usage_limit'] ?? null;

        return $data;
    }
}
