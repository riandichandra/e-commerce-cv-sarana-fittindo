<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::active()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->paginate(12);

        $categories = ProductCategory::active()->get();

        return view('pelanggan.products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images']);

        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('pelanggan.products.show', compact('product', 'relatedProducts'));
    }
}
