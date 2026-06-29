<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;

class BrandController extends Controller
{
    public function show(ProductBrand $brand)
    {
        abort_unless($brand->is_active, 404);

        $products = Product::visibleToCustomers()
            ->with(['category', 'brand', 'images'])
            ->where('brand_id', $brand->id)
            ->latest()
            ->paginate(12);

        return view('pelanggan.brands.show', compact('brand', 'products'));
    }
}
