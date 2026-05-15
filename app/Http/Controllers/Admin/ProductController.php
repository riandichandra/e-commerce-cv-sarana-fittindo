<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductBrand;

class ProductController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Products';

        $products = Product::with(['category', 'brand'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('pagePath', 'pageName', 'products'));
    }

    public function create()
    {
        $categories = ProductCategory::active()->get();
        $brands = ProductBrand::active()->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|min:0',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'images' => 'required|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'primary_image' => 'required|integer|min:0',
        ]);

        // Create product
        $product = Product::create($validated);

        // Upload images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === (int)$request->primary_image,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::active()->get();
        $brands = ProductBrand::active()->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|min:0',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
