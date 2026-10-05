<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::active()
            ->withCount(['products' => fn ($q) => $q->visibleOnWeb()])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $brands]);
    }
}
