<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Produk';

        $products = Product::with(['category', 'brand'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $availableProducts = Product::where('status', Product::STATUS_AVAILABLE)->count();
        $outOfStockProducts = Product::where('stock', 0)->count();

        return view('admin.products.index', compact(
            'pagePath',
            'pageName',
            'products',
            'totalProducts',
            'activeProducts',
            'availableProducts',
            'outOfStockProducts',
        ));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PRODUCTS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Tambah Produk';
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
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|min:1',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications_text' => 'nullable|string|max:10000',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'primary_image' => 'nullable|integer|min:0',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['specifications'] = $this->normalizeSpecifications($validated['specifications_text'] ?? null);
        unset($validated['specifications_text']);
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
        $pageName = 'Edit Produk';
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
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|min:1',
            'thickness' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:100',
            'specifications_text' => 'nullable|string|max:10000',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'primary_image' => 'nullable|integer|min:0',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['specifications'] = $this->normalizeSpecifications($validated['specifications_text'] ?? null);
        unset($validated['specifications_text']);
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

    private function normalizeSpecifications(?string $specifications): ?array
    {
        $lines = collect(preg_split('/\R/', (string) $specifications))
            ->map(fn (string $line) => trim($line))
            ->filter();

        if ($lines->isEmpty()) {
            return null;
        }

        $normalized = [];

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$label, $value] = array_map('trim', explode(':', $line, 2));

                if ($label !== '' && $value !== '') {
                    $normalized[$label] = $value;

                    continue;
                }
            }

            $normalized[] = $line;
        }

        return $normalized;
    }
}
