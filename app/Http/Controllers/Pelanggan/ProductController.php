<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::active()
            ->with(['category', 'brand', 'images'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($query) => $query->where('slug', $request->string('category')->toString()));
            })
            ->when($request->filled('price_range'), function ($query) use ($request) {
                match ($request->string('price_range')->toString()) {
                    'under_100k' => $query->where('price', '<', 100000),
                    '100k_500k' => $query->whereBetween('price', [100000, 500000]),
                    'above_500k' => $query->where('price', '>', 500000),
                    default => null,
                };
            })
            ->when($request->string('sort')->toString() === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($request->string('sort')->toString() === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($request->string('sort')->toString() === 'popular', fn ($query) => $query->orderByDesc('is_featured')->latest())
            ->when(! in_array($request->string('sort')->toString(), ['price_asc', 'price_desc', 'popular'], true), fn ($query) => $query->latest())
            ->paginate(12)
            ->appends($request->query());

        $categories = ProductCategory::active()->get();

        return view('pelanggan.products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images']);

        $relatedProducts = Product::active()
            ->with(['category', 'brand', 'images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('pelanggan.products.show', compact('product', 'relatedProducts'));
    }
}
