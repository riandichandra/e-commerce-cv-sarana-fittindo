<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductBrand;
use Illuminate\Validation\Rule;

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
        $pagePath = 'ADMIN/PRODUCTS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Create Product';
        $categories = ProductCategory::active()->get();
        $brands = ProductBrand::active()->get();

        return view('admin.products.create', compact('pagePath', 'pageName', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'name' => 'required|string|max:200|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|in:0,1',
            'weight' => 'required|numeric|min:0',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'primary_image' => 'nullable|integer|min:0',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['specifications'] = $this->formatSpecifications($request->input('specifications'));
        unset($validated['images'], $validated['primary_image']);

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === (int) $request->input('primary_image', 0),
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
        $pagePath = 'ADMIN/PRODUCTS/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Product';
        $categories = ProductCategory::active()->get();
        $brands = ProductBrand::active()->get();
        $product->load(['category', 'brand', 'images']);

        return view('admin.products.edit', compact('pagePath', 'pageName', 'product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'name' => ['required', 'string', 'max:200', Rule::unique('products', 'name')->ignore($product->id)],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|in:0,1',
            'weight' => 'required|numeric|min:0',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'primary_image' => 'nullable|integer|min:0',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['specifications'] = $this->formatSpecifications($request->input('specifications'));
        unset($validated['images'], $validated['primary_image']);

        $product->update($validated);

        if ($request->hasFile('images')) {
            $product->images()->update(['is_primary' => false]);

            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === (int) $request->input('primary_image', 0),
                    'sort_order' => $product->images()->count() + $index,
                ]);
            }
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    private function formatSpecifications(?string $specifications): ?array
    {
        if (!$specifications) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', $specifications))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
