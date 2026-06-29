<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;

class CategoryController extends Controller
{
    public function show(ProductCategory $category)
    {
        abort_unless($category->is_active, 404);

        $products = Product::visibleToCustomers()
            ->with(['category', 'brand', 'images'])
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);

        return view('pelanggan.categories.show', compact('category', 'products'));
    }
}
